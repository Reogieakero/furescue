<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\CaseRepository;
use App\Repositories\ReportRepository;
use App\Services\DedupService;
use App\Services\GeoService;
use App\Services\NotificationService;
use PDO;

class ReportController extends AbstractController
{
    private DedupService $dedup;
    private GeoService $geo;
    private ReportRepository $reports;
    private CaseRepository $cases;

    public function __construct(PDO $pdo, DedupService $dedup, GeoService $geo)
    {
        parent::__construct($pdo);
        $this->dedup = $dedup;
        $this->geo = $geo;
        $this->reports = new ReportRepository($pdo);
        $this->cases = new CaseRepository($pdo);
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

        $id = $this->reports->create([
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

        $report = $this->reports->find($id);
        Response::success(['report' => $report->toArray()], $duplicateId ? 409 : 201);
    }

    public function index(Request $req): void
    {
        $filters = [];
        if ($req->user['role'] === 'resident') {
            $filters['resident_id'] = $req->user['id'];
        }
        foreach (['status','validation_status'] as $f) {
            if (!empty($req->query[$f])) {
                $filters[$f] = $req->query[$f];
            }
        }
        $result = $this->reports->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated(
            array_map(fn($r) => $r->toArray(), $result['items']),
            $this->meta($result['page'], $result['per_page'], $result['total'])
        );
    }

    public function mine(Request $req): void
    {
        $result = $this->reports->paginate($this->page($req), $this->perPage($req), ['resident_id' => $req->user['id']]);
        Response::paginated(
            array_map(fn($r) => $r->toArray(), $result['items']),
            $this->meta($result['page'], $result['per_page'], $result['total'])
        );
    }

    public function show(Request $req): void
    {
        $report = $this->reports->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        if ($req->user['role'] === 'resident' && $report->residentId() !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your report', 403);
            return;
        }
        Response::success(['report' => $report->toArray()]);
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
        $report = $this->reports->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        $this->reports->update($report->id(), [
            'status' => 'dismissed',
            'dismiss_reason' => $req->body['dismiss_reason'],
            'verified_by' => $req->user['id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $notif = new NotificationService($this->pdo);
        $notif->notify($report->residentId(), 'report_dismissed', 'Your report was dismissed.', 'report', $report->id());
        Response::success(['report' => $this->reports->find($report->id())->toArray()]);
    }

    private function setReportStatus(Request $req, string $status, bool $createCase): void
    {
        $report = $this->reports->find($req->params['id']);
        if (!$report) {
            Response::error('NOT_FOUND', 'Report not found', 404);
            return;
        }
        $this->reports->update($report->id(), [
            'status' => $status,
            'validation_status' => 'validated',
            'verified_by' => $req->user['id'],
            'verified_at' => date('Y-m-d H:i:s'),
        ]);

        $caseId = null;
        if ($createCase) {
            $existing = $this->cases->findByReportId($report->id());
            if (!$existing) {
                $caseId = $this->cases->create([
                    'id' => Database::uuidV4(),
                    'report_id' => $report->id(),
                    'status' => 'open',
                ]);
            } else {
                $caseId = $existing->id();
            }
        }

        $notif = new NotificationService($this->pdo);
        $notif->notify($report->residentId(), 'report_verified', 'Your report was verified.', 'report', $report->id());
        Response::success(['report' => $this->reports->find($report->id())->toArray(), 'case_id' => $caseId]);
    }

    public function heatmap(Request $req): void
    {
        Response::success(['points' => $this->geo->heatmapPoints($req->query['status'] ?? null)]);
    }
}
