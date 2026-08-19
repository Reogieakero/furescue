<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\Repository;
use App\Services\NotificationService;

class CaseController extends AbstractController
{
    public function index(Request $req): void
    {
        $repo = $this->repo('cases', ['id','report_id','assigned_rescuer_id','assigned_by','status','resolution_notes','created_at','updated_at']);
        $filters = [];
        if ($req->user['role'] === 'rescuer') {
            $filters['assigned_rescuer_id'] = $req->user['id'];
        }
        foreach (['status','assigned_rescuer_id'] as $f) {
            if (!empty($req->query[$f])) {
                $filters[$f] = $req->query[$f];
            }
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function show(Request $req): void
    {
        $repo = $this->repo('cases');
        $case = $repo->find($req->params['id']);
        if (!$case) {
            Response::error('NOT_FOUND', 'Case not found', 404);
            return;
        }
        Response::success(['case' => $case]);
    }

    public function assign(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('rescuer_id')->string(36);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $caseRepo = $this->repo('cases');
        $case = $caseRepo->find($req->params['id']);
        if (!$case) {
            Response::error('NOT_FOUND', 'Case not found', 404);
            return;
        }

        $rescuer = $this->repo('users')->find($req->body['rescuer_id']);
        if (!$rescuer || $rescuer['role'] !== 'rescuer' || $rescuer['account_status'] !== 'active') {
            Response::error('INVALID_RESCUER', 'Rescuer is not available', 422);
            return;
        }
        $duty = $this->repo('rescuer_duty_status')->findBy('user_id', $rescuer['id']);
        if (!$duty || $duty['status'] !== 'on_duty') {
            Response::error('RESCUER_OFF_DUTY', 'Rescuer is not on duty', 422);
            return;
        }

        $caseRepo->update($case['id'], [
            'assigned_rescuer_id' => $rescuer['id'],
            'assigned_by' => $req->user['id'],
            'status' => 'assigned',
        ]);
        $this->logActivity($case['id'], 'assigned', 'Assigned to rescuer ' . $rescuer['id'], $req->user['id'], $req->user['role']);

        $notif = new NotificationService($this->pdo);
        $notif->notify($rescuer['id'], 'case_assigned', 'A new case was assigned to you.', 'case', $case['id']);
        Response::success(['case' => $caseRepo->find($case['id'])]);
    }

    public function updateStatus(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('status')->in('status', ['assigned','in_progress','resolved']);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $caseRepo = $this->repo('cases');
        $case = $caseRepo->find($req->params['id']);
        if (!$case) {
            Response::error('NOT_FOUND', 'Case not found', 404);
            return;
        }
        if ($case['assigned_rescuer_id'] !== $req->user['id'] && $req->user['role'] !== 'admin') {
            
        }
        if ($req->user['role'] === 'rescuer' && $case['assigned_rescuer_id'] !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your case', 403);
            return;
        }

        $caseRepo->update($case['id'], ['status' => $req->body['status']]);
        $this->logActivity($case['id'], 'status_change', 'Status set to ' . $req->body['status'], $req->user['id'], $req->user['role']);

        if ($req->body['status'] === 'resolved') {
            $report = $this->repo('reports')->find($case['report_id']);
            if ($report) {
                $this->repo('reports')->update($report['id'], ['status' => 'verified']);
                $notif = new NotificationService($this->pdo);
                $notif->notify($report['resident_id'], 'case_resolved', 'Your reported case has been resolved.', 'case', $case['id']);
            }
        }
        Response::success(['case' => $caseRepo->find($case['id'])]);
    }

    public function activity(Request $req): void
    {
        $logRepo = $this->repo('case_activity_log', ['id','case_id','actor_id','actor_role','action','notes','created_at']);
        $rows = $logRepo->all(['case_id' => $req->params['id']], 'created_at', 'ASC');
        Response::success(['activity' => $rows]);
    }

    private function logActivity(string $caseId, string $action, ?string $notes, string $actorId, string $actorRole): void
    {
        $this->repo('case_activity_log')->create([
            'id' => Database::uuidV4(),
            'case_id' => $caseId,
            'actor_id' => $actorId,
            'actor_role' => $actorRole,
            'action' => $action,
            'notes' => $notes,
        ]);
    }
}
