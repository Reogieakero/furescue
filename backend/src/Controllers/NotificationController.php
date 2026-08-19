<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

class NotificationController extends AbstractController
{
    public function index(Request $req): void
    {
        $repo = $this->repo('notifications', ['id','user_id','type','message','related_type','related_id','is_read','created_at']);
        $filters = ['user_id' => $req->user['id']];
        if (isset($req->query['is_read'])) {
            $filters['is_read'] = $req->query['is_read'] === 'true' || $req->query['is_read'] === '1' ? 1 : 0;
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters, 'created_at', 'DESC');
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function markRead(Request $req): void
    {
        $repo = $this->repo('notifications');
        $note = $repo->find($req->params['id']);
        if (!$note) {
            Response::error('NOT_FOUND', 'Notification not found', 404);
            return;
        }
        if ($note['user_id'] !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your notification', 403);
            return;
        }
        $repo->update($note['id'], ['is_read' => true]);
        Response::success(['notification' => $repo->find($note['id'])]);
    }

    public function markAllRead(Request $req): void
    {
        $stmt = $this->pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ? AND is_read = FALSE");
        $stmt->execute([$req->user['id']]);
        Response::success(['updated' => $stmt->rowCount()]);
    }
}
