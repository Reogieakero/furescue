<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\AdoptionEligibilityService;
use App\Services\NotificationService;
use PDO;

class AdoptionListingController extends AbstractController
{
    private AdoptionEligibilityService $eligibility;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->eligibility = new AdoptionEligibilityService($pdo);
    }

    public function create(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('animal_id')->string(36);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $animal = $this->repo('animals')->find($req->body['animal_id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        if (!$this->eligibility->isEligible($req->body['animal_id'])) {
            $this->rejectNotHealthReady();
            return;
        }
        $id = $this->repo('adoption_listings')->create([
            'id' => Database::uuidV4(),
            'animal_id' => $req->body['animal_id'],
            'posted_by' => $req->user['id'],
            'status' => 'pending_review',
        ]);
        $this->notifyRole('admin', 'listing_submitted', 'A new adoption listing needs review.', 'adoption_listing', $id);
        Response::success(['listing' => $this->repo('adoption_listings')->find($id)], 201);
    }

    public function index(Request $req): void
    {
        $repo = $this->repo('adoption_listings');
        $filters = [];
        if (!in_array('adoptions.read', $req->permissions, true)) {
            $filters['posted_by'] = $req->user['id'];
        }
        if (!empty($req->query['status'])) {
            $filters['status'] = $req->query['status'];
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function show(Request $req): void
    {
        $listing = $this->repo('adoption_listings')->find($req->params['id']);
        if (!$listing) {
            Response::error('NOT_FOUND', 'Listing not found', 404);
            return;
        }
        Response::success(['listing' => $listing]);
    }

    public function review(Request $req, string $decision): void
    {
        $v = new \App\Validation\Validator($req->body);
        if ($decision === 'rejected') {
            $v->required('review_notes')->string(500);
        }
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $repo = $this->repo('adoption_listings');
        $listing = $repo->find($req->params['id']);
        if (!$listing) {
            Response::error('NOT_FOUND', 'Listing not found', 404);
            return;
        }
        if ($decision === 'approved' && !$this->eligibility->isEligible($listing['animal_id'])) {
            $this->rejectNotHealthReady();
            return;
        }
        $repo->update($listing['id'], [
            'status' => $decision === 'approved' ? 'approved' : 'rejected',
            'reviewed_by' => $req->user['id'],
            'review_notes' => $req->body['review_notes'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);

        if ($decision === 'approved') {
            $this->repo('animals')->update($listing['animal_id'], ['adoption_status' => 'available']);
        }

        $notif = new NotificationService($this->pdo);
        $notif->notify($listing['posted_by'], 'listing_' . $decision, 'Your adoption listing was ' . $decision . '.', 'adoption_listing', $listing['id']);
        Response::success(['listing' => $repo->find($listing['id'])]);
    }

    private function rejectNotHealthReady(): void
    {
        Response::error(
            AdoptionEligibilityService::ERROR_CODE,
            AdoptionEligibilityService::ERROR_MESSAGE,
            409
        );
    }
}
