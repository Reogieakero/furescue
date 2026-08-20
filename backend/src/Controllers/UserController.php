<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\NotificationService;

class UserController extends AbstractController
{
    public function me(Request $req): void
    {
        Response::success(['user' => $req->user]);
    }

    public function index(Request $req): void
    {
        if (($req->query['role'] ?? null) === 'rescuer') {
            $this->indexRescuers($req);
            return;
        }

        $repo = $this->repo('users', ['id','full_name','email','role','account_status','phone_number','created_at']);
        $filters = [];
        if (!empty($req->query['role'])) {
            $filters['role'] = $req->query['role'];
        }
        if (!empty($req->query['account_status'])) {
            $filters['account_status'] = $req->query['account_status'];
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        $clean = array_map(function ($u) {
            unset($u['password_hash']);
            return $u;
        }, $result['items']);
        Response::paginated($clean, $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    private function indexRescuers(Request $req): void
    {
        $where = [];
        $params = [];
        if (!empty($req->query['account_status'])) {
            $where[] = 'u.account_status = ?';
            $params[] = $req->query['account_status'];
        }
        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $page = $this->page($req);
        $perPage = $this->perPage($req);
        $offset = ($page - 1) * $perPage;

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM users u {$whereSql}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $stmt = $this->pdo->prepare(
            "SELECT u.*, COALESCE(d.status, 'off_duty') AS duty_status
             FROM users u
             LEFT JOIN rescuer_duty_status d ON d.user_id = u.id
             {$whereSql}
             ORDER BY u.created_at DESC
             LIMIT ? OFFSET ?"
        );
        $stmt->execute(array_merge($params, [$perPage, $offset]));
        $clean = array_map(function ($u) {
            unset($u['password_hash']);
            return $u;
        }, $stmt->fetchAll(\PDO::FETCH_ASSOC));

        Response::paginated($clean, $this->meta($page, $perPage, $total));
    }

    public function show(Request $req): void
    {
        $repo = $this->repo('users');
        $user = $repo->find($req->params['id']);
        if (!$user) {
            Response::error('NOT_FOUND', 'User not found', 404);
            return;
        }
        unset($user['password_hash']);
        Response::success(['user' => $user]);
    }

    public function update(Request $req): void
    {
        $id = $req->params['id'];
        $repo = $this->repo('users');
        $user = $repo->find($id);
        if (!$user) {
            Response::error('NOT_FOUND', 'User not found', 404);
            return;
        }

        $isSelf = $req->user['id'] === $id;
        $isAdmin = $req->user['role'] === 'admin';
        if (!$isSelf && !$isAdmin) {
            Response::error('FORBIDDEN', 'Cannot update this user', 403);
            return;
        }

        $allowed = ['full_name','phone_number','address','profile_photo_url'];
        if ($isAdmin) {
            $allowed = array_merge($allowed, ['account_status','role']);
        }
        $data = [];
        foreach ($allowed as $field) {
            if (array_key_exists($field, $req->body)) {
                $data[$field] = $req->body[$field];
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No updatable fields provided', 400);
            return;
        }
        $repo->update($id, $data);
        $updated = $repo->find($id);
        unset($updated['password_hash']);
        Response::success(['user' => $updated]);
    }

    public function approveRescuer(Request $req): void
    {
        $this->resolveRescuer($req, 'approved');
    }

    public function rejectRescuer(Request $req): void
    {
        $this->resolveRescuer($req, 'rejected');
    }

    private function resolveRescuer(Request $req, string $decision): void
    {
        $id = $req->params['id'];
        $userRepo = $this->repo('users');
        $user = $userRepo->find($id);
        if (!$user || $user['role'] !== 'rescuer') {
            Response::error('NOT_FOUND', 'Rescuer not found', 404);
            return;
        }

        $approvalRepo = $this->repo('rescuer_approvals', ['id','user_id','reviewed_by','decision','remarks','reviewed_at']);
        $approvalRepo->create([
            'id' => Database::uuidV4(),
            'user_id' => $id,
            'reviewed_by' => $req->user['id'],
            'decision' => $decision,
            'remarks' => $req->body['remarks'] ?? null,
            'reviewed_at' => date('Y-m-d H:i:s'),
        ]);
        $userRepo->update($id, ['account_status' => $decision === 'approved' ? 'active' : 'rejected']);

        $notif = new NotificationService($this->pdo);
        $notif->notify(
            $id,
            'rescuer_' . $decision,
            'Your rescuer application was ' . $decision . '.',
            'user',
            $id
        );

        Response::success(['status' => $decision]);
    }
}
