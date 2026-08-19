<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

class AnimalController extends AbstractController
{
    public function index(Request $req): void
    {
        $repo = $this->repo('animals', [
            'id','name','species','breed_type','sex','age_estimate','color_markings',
            'adoption_status','source','created_at'
        ]);
        $filters = [];
        foreach (['species','breed_type','sex','adoption_status','source'] as $f) {
            if (!empty($req->query[$f])) {
                $filters[$f] = $req->query[$f];
            }
        }
        $result = $repo->paginate($this->page($req), $this->perPage($req), $filters);
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }

    public function show(Request $req): void
    {
        $repo = $this->repo('animals');
        $animal = $repo->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $animal['medical'] = $this->repo('animal_medical_records')->findBy('animal_id', $animal['id']);
        $animal['field_status'] = $this->repo('animal_field_status')->all(['animal_id' => $animal['id']], 'logged_at', 'DESC');
        Response::success(['animal' => $animal]);
    }

    public function create(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('species')->in('species', ['dog','cat'])
            ->required('breed_type')->in('breed_type', ['aspin','puspin'])
            ->required('sex')->in('sex', ['male','female'])
            ->optional('name')->string(100)
            ->optional('adoption_status')->in('adoption_status', ['not_listed','available','pending','adopted'])
            ->optional('source')->in('source', ['rescued_case','resident_listing'])
            ->optional('description')->string(2000);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $data = [
            'id' => Database::uuidV4(),
            'species' => $req->body['species'],
            'breed_type' => $req->body['breed_type'],
            'sex' => $req->body['sex'],
            'name' => $req->body['name'] ?? null,
            'age_estimate' => $req->body['age_estimate'] ?? null,
            'color_markings' => $req->body['color_markings'] ?? null,
            'description' => $req->body['description'] ?? null,
            'photo_urls' => isset($req->body['photo_urls']) ? json_encode($req->body['photo_urls']) : null,
            'model_3d_url' => $req->body['model_3d_url'] ?? null,
            'photo_360_set' => isset($req->body['photo_360_set']) ? json_encode($req->body['photo_360_set']) : null,
            'adoption_status' => $req->body['adoption_status'] ?? 'not_listed',
            'source' => $req->body['source'] ?? 'rescued_case',
            'created_by' => $req->user['id'],
        ];
        $id = $this->repo('animals')->create($data);
        Response::success(['animal' => $this->repo('animals')->find($id)], 201);
    }

    public function update(Request $req): void
    {
        $repo = $this->repo('animals');
        $animal = $repo->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $allowed = ['name','age_estimate','color_markings','description','photo_urls','model_3d_url','photo_360_set','adoption_status'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $req->body)) {
                $val = $req->body[$f];
                if (in_array($f, ['photo_urls','photo_360_set'], true) && is_array($val)) {
                    $val = json_encode($val);
                }
                $data[$f] = $val;
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No updatable fields', 400);
            return;
        }
        $repo->update($animal['id'], $data);
        Response::success(['animal' => $repo->find($animal['id'])]);
    }

    public function logFieldStatus(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('rescue_status')->in('rescue_status', ['rescued','not_rescued'])
            ->required('health_status')->in('health_status', ['healthy','not_healthy'])
            ->optional('case_id')->string(36);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $animal = $this->repo('animals')->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $id = $this->repo('animal_field_status')->create([
            'id' => Database::uuidV4(),
            'animal_id' => $animal['id'],
            'case_id' => $req->body['case_id'] ?? null,
            'rescue_status' => $req->body['rescue_status'],
            'health_status' => $req->body['health_status'],
            'logged_by' => $req->user['id'],
        ]);
        Response::success(['field_status' => $this->repo('animal_field_status')->find($id)], 201);
    }

    public function fieldStatusHistory(Request $req): void
    {
        $rows = $this->repo('animal_field_status')->all(['animal_id' => $req->params['id']], 'logged_at', 'DESC');
        Response::success(['field_status' => $rows]);
    }
}
