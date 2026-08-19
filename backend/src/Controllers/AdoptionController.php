<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\NotificationService;

class AdoptionController extends AbstractController
{
    public function apply(Request $req): void
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
        if ($animal['adoption_status'] !== 'available') {
            Response::error('NOT_ADOPTABLE', 'Animal is not available for adoption', 409);
            return;
        }
        $id = $this->repo('adoptions')->create([
            'id' => Database::uuidV4(),
            'animal_id' => $req->body['animal_id'],
            'applicant_id' => $req->user['id'],
            'status' => 'pending',
        ]);
        $this->notifyRole('admin', 'adoption_applied', 'A new adoption application was submitted.', 'adoption', $id);
        Response::success(['adoption' => $this->repo('adoptions')->find($id)], 201);
    }

    public function index(Request $req): void
    {
        $repo = $this->repo('adoptions');
        $filters = [];
        if ($req->user['role'] === 'resident') {
            $filters['applicant_id'] = $req->user['id'];
        }
        if (!empty($req->query['status'])) {
            $filters['status'] = $req->query['status'];
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function show(Request $req): void
    {
        $adoption = $this->repo('adoptions')->find($req->params['id']);
        if (!$adoption) {
            Response::error('NOT_FOUND', 'Adoption not found', 404);
            return;
        }
        if ($req->user['role'] === 'resident' && $adoption['applicant_id'] !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your application', 403);
            return;
        }
        Response::success(['adoption' => $adoption]);
    }

    public function review(Request $req, string $decision): void
    {
        $v = new \App\Validation\Validator($req->body);
        if ($decision === 'rejected') {
            $v->required('rejection_reason')->string(500);
        }
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $repo = $this->repo('adoptions');
        $adoption = $repo->find($req->params['id']);
        if (!$adoption) {
            Response::error('NOT_FOUND', 'Adoption not found', 404);
            return;
        }
        if ($adoption['status'] !== 'pending') {
            Response::error('ALREADY_REVIEWED', 'Application already reviewed', 409);
            return;
        }

        $data = [
            'status' => $decision === 'approved' ? 'approved' : 'rejected',
            'reviewed_by' => $req->user['id'],
            'reviewed_at' => date('Y-m-d H:i:s'),
        ];
        if ($decision === 'rejected') {
            $data['rejection_reason'] = $req->body['rejection_reason'];
        }
        $repo->update($adoption['id'], $data);

        if ($decision === 'approved') {
            $this->repo('animals')->update($adoption['animal_id'], ['adoption_status' => 'adopted']);
        }

        $notif = new NotificationService($this->pdo);
        $notif->notify($adoption['applicant_id'], 'adoption_' . $decision, 'Your adoption application was ' . $decision . '.', 'adoption', $adoption['id']);
        Response::success(['adoption' => $repo->find($adoption['id'])]);
    }

    public function complete(Request $req): void
    {
        $repo = $this->repo('adoptions');
        $adoption = $repo->find($req->params['id']);
        if (!$adoption) {
            Response::error('NOT_FOUND', 'Adoption not found', 404);
            return;
        }
        if ($adoption['status'] !== 'approved') {
            Response::error('INVALID_STATE', 'Adoption must be approved first', 409);
            return;
        }
        $repo->update($adoption['id'], ['status' => 'completed', 'completed_at' => date('Y-m-d H:i:s')]);
        $notif = new NotificationService($this->pdo);
        $notif->notify($adoption['applicant_id'], 'adoption_completed', 'Your adoption is complete. Thank you!', 'adoption', $adoption['id']);
        Response::success(['adoption' => $repo->find($adoption['id'])]);
    }
}
