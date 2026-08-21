<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

class VitalsController extends AbstractController
{
    public function ingest(Request $req): void
    {
        $key = (string) Database::env('DEVICE_API_KEY', '');
        if ($key === '' || $req->header('x-device-key') !== $key) {
            Response::error('UNAUTHENTICATED', 'Invalid device key', 401);
            return;
        }
        $v = new \App\Validation\Validator($req->body);
        $v->required('animal_id')->string(36)
            ->required('heart_rate_bpm')->numeric('heart_rate_bpm');
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $animal = $this->repo('animals')->find($req->body['animal_id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $id = $this->repo('vitals_log')->create([
            'id' => Database::uuidV4(),
            'animal_id' => $req->body['animal_id'],
            'heart_rate_bpm' => (int) $req->body['heart_rate_bpm'],
            'source' => $req->body['source'] ?? 'iot_sensor',
        ]);
        Response::success(['vital' => $this->repo('vitals_log')->find($id)], 201);
    }

    public function create(Request $req): void
    {
        $animal = $this->repo('animals')->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $v = new \App\Validation\Validator($req->body);
        $v->required('heart_rate_bpm')->numeric('heart_rate_bpm');
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }
        $data = [
            'animal_id' => $req->params['id'],
            'heart_rate_bpm' => (int) $req->body['heart_rate_bpm'],
            'source' => 'manual',
        ];
        if (array_key_exists('respiratory_rate_bpm', $req->body) && $req->body['respiratory_rate_bpm'] !== null && $req->body['respiratory_rate_bpm'] !== '') {
            $data['respiratory_rate_bpm'] = (int) $req->body['respiratory_rate_bpm'];
        }
        $id = $this->repo('vitals_log')->create($data);
        Response::success(['vital' => $this->repo('vitals_log')->find($id)], 201);
    }

    public function list(Request $req): void
    {
        $animal = $this->repo('animals')->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $result = $this->repo('vitals_log')->paginate(
            $this->page($req), $this->perPage($req), ['animal_id' => $req->params['id']]
        );
        Response::paginated($result['items'], $this->meta($result['page'], $result['per_page'], $result['total']));
    }
}
