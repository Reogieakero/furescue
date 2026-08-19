<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\DedupService;
use App\Services\GeoService;
use App\Services\NotificationService;

class ReportController extends AbstractController
{
    private DedupService $dedup;
    private GeoService $geo;

    public function __construct(\PDO $pdo, DedupService $dedup, GeoService $geo)
    {
        parent::__construct($pdo);
        $this->dedup = $dedup;
        $this->geo = $geo;
    }

    public function create(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('animal_description')->string(2000)
            ->required('latitude')->latitude('latitude')
            ->required('longitude')->longitude('longitude')
            ->optional('address_text')->string(500)
            ->optional('photo_urls')->string(4000);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $lat = (float) $req->body['latitude'];
        $lng = (float) $req->body['longitude'];

        if (!$this->geo->inMatiBounds($lat, $lng)) {
            Response::error('OUT_OF_BOUNDS', 'Location is outside Mati City', 422);
            return;
        }

        $photoUrls = is_array($req->body['photo_urls'] ?? null)
            ? json_encode($req->body['photo_urls'])
            : ($req->body['photo_urls'] ?? null);

        $contentHash = DedupService::contentHash($req->body['animal_description'], $lat, $lng);
        $duplicateId = $this->dedup->findDuplicate($contentHash, $lat, $lng);

        $validationStatus = $duplicateId ? 'flagged_duplicate' : 'validated';
        $repo = $this->repo('reports', [
            'id','resident_id','animal_description','photo_urls','latitude','longitude','address_text',
            'content_hash','duplicate_of_report_id','validation_status','status','dismiss_reason',
            'verified_by','verified_at','created_at'
        ]);

        $id = $repo->create([
            'id' => Database::uuidV4(),
            'resident_id' => $req->user['id'],
            'animal_description' => $req->body['animal_description'],
            'photo_urls' => $photoUrls,
            'latitude' => $lat,
            'longitude' => $lng,
            'address_text' => $req->body['address_text'] ?? null,
            'content_hash' => $contentHash,
            'duplicate_of_report_id' => $duplicateId,
            'validation_status' => $validationStatus,
            'status' => 'pending_verification',
        ]);

        $this->notifyRole('admin', 'report_submitted', 'A new animal report was submitted.', 'report', $id);

        $report = $repo->find($id);
        Response::success(['report' => $report], $duplicateId ? 409 : 201);
    }

    public function index(Request $req): void
    {
        $repo = $this->repo('reports', [
            'id','resident_id','animal_description','photo_urls','latitude','longitude','address_text',
            'validation_status','status','duplicate_of_report_id','created_at'
        ]);
        $filters = [];
        if ($req->user['role'] === 'resident') {
            $filters['resident_id'] = $req->user['id'];
        }
        foreach (['status','validation_status'] as $f) {
            if (!empty($req->query[$f])) {
                $filters[$f] = $req->query[$f];
            }
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function mine(Request $req): void
    {
        $repo = $this->repo('reports');
        $result = $repo->paginate($this->page($req), $this->perPage($req), ['resident_id' => $req->user['id']]);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function show(Request $req): void
    {
        $repo = $this->repo('reports');
        $report = $repo->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        if ($req->user['role'] === 'resident' && $report['resident_id'] !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your report', 403);
            return;
        }
        Response::success(['report' => $report]);
    }

    public function verify(Request $req): void
    {
        $this->setReportStatus($req, 'verified', true);
    }

    public function dismiss(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('dismiss_reason')->string(500);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $repo = $this->repo('reports');
        $report = $repo->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        $repo->update($report['id'], [
            'status' => 'dismissed',
            'dismiss_reason' => $req->body['dismiss_reason'],
            'verified_by' => $req->user['id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $notif = new NotificationService($this->pdo);
        $notif->notify($report['resident_id'], 'report_dismissed', 'Your report was dismissed.', 'report', $report['id']);
        Response::success(['report' => $repo->find($report['id'])]);
    }

    private function setReportStatus(Request $req, string $status, bool $createCase): void
    {
        $repo = $this->repo('reports');
        $report = $repo->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        $repo->update($report['id'], [
            'status' => $status,
            'validation_status' => 'validated',
            'verified_by' => $req->user['id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $caseId = null;
        if ($createCase) {
            $caseRepo = $this->repo('cases', ['id','report_id','assigned_rescuer_id','assigned_by','status','resolution_notes','created_at']);
            $existing = $caseRepo->findBy('report_id', $report['id']);
            if (!$existing) {
                $caseId = $caseRepo->create([
                    'id' => Database::uuidV4(),
                    'report_id' => $report['id'],
                    'status' => 'assigned',
                ]);
            } else {
                $caseId = $existing['id'];
            }
        }

        $notif = new NotificationService($this->pdo);
        $notif->notify($report['resident_id'], 'report_verified', 'Your report was verified.', 'report', $report['id']);
        Response::success(['report' => $repo->find($report['id']), 'case_id' => $caseId]);
    }

    public function heatmap(Request $req): void
    {
        Response::success(['points' => $this->geo->heatmapPoints($req->query['status'] ?? null)]);
    }
}
