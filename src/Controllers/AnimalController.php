<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\AnimalRepository;
use PDO;

class AnimalController extends AbstractController
{
    private AnimalRepository $animals;

    public function __construct(PDO $pdo)
    {
        parent::__construct($pdo);
        $this->animals = new AnimalRepository($pdo);
    }

    public function index(Request $req): void
    {
        $where = "WHERE deleted_at IS NULL";
        $params = [];
        foreach (['species', 'breed_type', 'sex', 'adoption_status', 'source'] as $f) {
            if (!empty($req->query[$f])) {
                $where .= " AND {$f} = ?";
                $params[] = $req->query[$f];
            }
        }
        $page = $this->page($req);
        $perPage = $this->perPage($req);
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM animals {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();
        $offset = ($page - 1) * $perPage;
        $stmt = $this->pdo->prepare(
            "SELECT id,name,species,breed_type,sex,age_estimate,birth_date,color_markings,photo_urls,adoption_status,source,created_at
             FROM animals {$where} ORDER BY created_at DESC LIMIT " . (int) $perPage . " OFFSET " . (int) $offset
        );
        $stmt->execute($params);
        $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        Response::paginated($items, $this->meta($page, $perPage, $total));
    }

    public function show(Request $req): void
    {
        $animal = $this->animals->findActive($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $data = $animal->toArray();
        $data['medical'] = $this->repo('animal_medical_records')->findBy('animal_id', $animal->id());
        $data['field_status'] = $this->repo('animal_field_status')->all(['animal_id' => $animal->id()], 'logged_at', 'DESC');
        Response::success(['animal' => $data]);
    }

    public function create(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('species')->in('species', ['dog', 'cat'])
            ->required('breed_type')->in('breed_type', ['aspin', 'puspin'])
            ->required('sex')->in('sex', ['male', 'female'])
            ->optional('name')->string(100)
            ->optional('adoption_status')->in('adoption_status', ['not_listed', 'available', 'pending', 'adopted'])
            ->optional('source')->in('source', ['rescued_case', 'resident_listing'])
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
            'birth_date' => $req->body['birth_date'] ?? null,
            'color_markings' => $req->body['color_markings'] ?? null,
            'description' => $req->body['description'] ?? null,
            'photo_urls' => isset($req->body['photo_urls']) ? json_encode($req->body['photo_urls']) : null,
            'model_3d_url' => $req->body['model_3d_url'] ?? null,
            'photo_360_set' => isset($req->body['photo_360_set']) ? json_encode($req->body['photo_360_set']) : null,
            'adoption_status' => $req->body['adoption_status'] ?? 'not_listed',
            'source' => $req->body['source'] ?? 'rescued_case',
            'created_by' => $req->user['id'],
        ];
        $id = $this->animals->create($data);
        $created = $this->animals->find($id);
        Response::success(['animal' => $created->toArray()], 201);
    }

    public function update(Request $req): void
    {
        $animal = $this->animals->findActive($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $allowed = ['name', 'age_estimate', 'birth_date', 'color_markings', 'description', 'photo_urls', 'model_3d_url', 'photo_360_set', 'adoption_status'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $req->body)) {
                $val = $req->body[$f];
                if (in_array($f, ['photo_urls', 'photo_360_set'], true) && is_array($val)) {
                    $val = json_encode($val);
                }
                $data[$f] = $val;
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No updatable fields', 400);
            return;
        }
        $this->animals->update($animal->id(), $data);
        $updated = $this->animals->find($animal->id());
        Response::success(['animal' => $updated->toArray()]);
    }

    public function delete(Request $req): void
    {
        $animal = $this->animals->findActive($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $this->animals->softDelete($animal->id());
        Response::success(['animal' => $animal->toArray()]);
    }

    public function logFieldStatus(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('rescue_status')->in('rescue_status', ['rescued', 'not_rescued'])
            ->required('health_status')->in('health_status', ['healthy', 'not_healthy'])
            ->optional('case_id')->string(36);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $animal = $this->animals->findActive($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $id = $this->repo('animal_field_status')->create([
            'id' => Database::uuidV4(),
            'animal_id' => $animal->id(),
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
