<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

class ElearningController extends AbstractController
{
    public function modules(Request $req): void
    {
        $repo = $this->repo('elearning_modules', ['id','title','category','published_status','created_at']);
        $filters = [];
        if ($req->user['role'] === 'resident') {
            $filters['published_status'] = 'published';
        } elseif (!empty($req->query['published_status'])) {
            $filters['published_status'] = $req->query['published_status'];
        }
        if (!empty($req->query['category'])) {
            $filters['category'] = $req->query['category'];
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function module(Request $req): void
    {
        $module = $this->repo('elearning_modules')->find($req->params['id']);
        if (!$module) {
            Response::error('NOT_FOUND', 'Module not found', 404);
            return;
        }
        if ($module['published_status'] !== 'published' && !in_array('elearning.write', $req->permissions, true)) {
            Response::error('FORBIDDEN', 'Module not published', 403);
            return;
        }
        Response::success(['module' => $module]);
    }

    public function createModule(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('title')->string(150)
            ->required('category')->in('category', ['dog_behavior','cat_behavior','basic_training','general_care'])
            ->required('content_body')->string(20000)
            ->optional('published_status')->in('published_status', ['draft','published']);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $id = $this->repo('elearning_modules')->create([
            'id' => Database::uuidV4(),
            'title' => $req->body['title'],
            'category' => $req->body['category'],
            'content_body' => $req->body['content_body'],
            'published_status' => $req->body['published_status'] ?? 'draft',
            'created_by' => $req->user['id'],
        ]);
        Response::success(['module' => $this->repo('elearning_modules')->find($id)], 201);
    }

    public function updateModule(Request $req): void
    {
        $repo = $this->repo('elearning_modules');
        $module = $repo->find($req->params['id']);
        if (!$module) {
            Response::error('NOT_FOUND', 'Module not found', 404);
            return;
        }
        $allowed = ['title','category','content_body','published_status'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $req->body)) {
                $data[$f] = $req->body[$f];
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No fields provided', 400);
            return;
        }
        $repo->update($module['id'], $data);
        Response::success(['module' => $repo->find($module['id'])]);
    }

    public function progress(Request $req): void
    {
        $rows = $this->repo('elearning_progress')->all(['resident_id' => $req->user['id']], 'completed_at', 'DESC');
        Response::success(['progress' => $rows]);
    }

    public function upsertProgress(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('module_id')->string(36)
            ->required('status')->in('status', ['not_started','in_progress','completed']);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $repo = $this->repo('elearning_progress');
        $existing = $repo->findByComposite(['resident_id','module_id'], [$req->user['id'], $req->body['module_id']]);
        $completedAt = $req->body['status'] === 'completed' ? date('Y-m-d H:i:s') : null;
        if ($existing) {
            $repo->update($existing['id'], ['status' => $req->body['status'], 'completed_at' => $completedAt]);
            $row = $repo->find($existing['id']);
        } else {
            $id = $repo->create([
                'id' => Database::uuidV4(),
                'resident_id' => $req->user['id'],
                'module_id' => $req->body['module_id'],
                'status' => $req->body['status'],
                'completed_at' => $completedAt,
            ]);
            $row = $repo->find($id);
        }
        Response::success(['progress' => $row]);
    }
}
