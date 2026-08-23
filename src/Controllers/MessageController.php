<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Services\NotificationService;

class MessageController extends AbstractController
{
    public function threads(Request $req): void
    {
        $me = $req->user['id'];
        $sql = "SELECT m.related_type, m.related_id,
                       (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END) AS other_user_id,
                       u.full_name AS other_user_name,
                       m.message_text AS last_message,
                       m.sent_at AS last_sent_at
                FROM messages m
                JOIN (
                    SELECT related_type, related_id, MAX(sent_at) AS max_sent
                    FROM messages
                    WHERE sender_id = ? OR receiver_id = ?
                    GROUP BY related_type, related_id
                ) l ON l.related_type = m.related_type
                   AND l.related_id = m.related_id
                   AND m.sent_at = l.max_sent
                JOIN users u ON u.id = (CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END)
                WHERE m.sender_id = ? OR m.receiver_id = ?
                ORDER BY m.sent_at DESC
                LIMIT 100";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$me, $me, $me, $me, $me, $me]);
        $threads = [];
        foreach ($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $key = $row['related_type'] . '|' . $row['related_id'];
            if (isset($threads[$key])) {
                continue;
            }
            $threads[$key] = $row;
        }
        $threads = array_values($threads);

        $unreadStmt = $this->pdo->prepare(
            "SELECT related_type, related_id, COUNT(*) AS unread_count
             FROM messages
             WHERE receiver_id = ? AND read_at IS NULL
             GROUP BY related_type, related_id"
        );
        $unreadStmt->execute([$me]);
        $unread = [];
        foreach ($unreadStmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $unread[$row['related_type'] . '|' . $row['related_id']] = (int) $row['unread_count'];
        }

        foreach ($threads as &$thread) {
            $key = $thread['related_type'] . '|' . $thread['related_id'];
            $thread['unread_count'] = $unread[$key] ?? 0;
        }
        unset($thread);
        Response::success(['threads' => array_values($threads)]);
    }

    public function send(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('receiver_id')->string(36)
            ->required('related_type')->in(['report','case','adoption'])
            ->required('related_id')->string(36)
            ->required('message_text')->string(4000);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        if ($req->body['receiver_id'] === $req->user['id']) {
            Response::error('INVALID_RECEIVER', 'Cannot message yourself', 422);
            return;
        }
        $id = $this->repo('messages')->create([
            'id' => Database::uuidV4(),
            'sender_id' => $req->user['id'],
            'receiver_id' => $req->body['receiver_id'],
            'related_type' => $req->body['related_type'],
            'related_id' => $req->body['related_id'],
            'message_text' => $req->body['message_text'],
        ]);

        $notif = new NotificationService($this->pdo);
        $notif->notify($req->body['receiver_id'], 'new_message', 'You have a new message.', $req->body['related_type'], $req->body['related_id']);
        Response::success(['message' => $this->repo('messages')->find($id)], 201);
    }

    public function thread(Request $req): void
    {
        $v = new \App\Validation\Validator($req->query);
        $v->required('related_type')->in('related_type', ['report','case','adoption'])
            ->required('related_id')->string(36);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $rows = $this->repo('messages')->all(
            ['related_type' => $req->query['related_type'], 'related_id' => $req->query['related_id']],
            'sent_at', 'ASC'
        );
        Response::success(['messages' => $rows]);
    }

    public function markRead(Request $req): void
    {
        $repo = $this->repo('messages');
        $msg = $repo->find($req->params['id']);
        if (!$msg) {
            Response::error('NOT_FOUND', 'Message not found', 404);
            return;
        }
        if ($msg['receiver_id'] !== $req->user['id']) {
            Response::error('FORBIDDEN', 'Not your message', 403);
            return;
        }
        $repo->update($msg['id'], ['read_at' => date('Y-m-d H:i:s')]);
        Response::success(['message' => $repo->find($msg['id'])]);
    }
}
