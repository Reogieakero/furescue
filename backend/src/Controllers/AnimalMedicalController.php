<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

class AnimalMedicalController extends AbstractController
{
    public function show(Request $req): void
    {
        $animal = $this->repo('animals')->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $record = $this->repo('animal_medical_records')->findBy('animal_id', $req->params['id']);
        Response::success(['medical' => $record]);
    }

    public function upsert(Request $req): void
    {
        $animal = $this->repo('animals')->find($req->params['id']);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }
        $allowed = ['medical_history_notes','vaccination_status','vaccination_details','last_checkup_date'];
        $data = [];
        foreach ($allowed as $f) {
            if (array_key_exists($f, $req->body)) {
                $val = $req->body[$f];
                if ($f === 'vaccination_details' && is_array($val)) {
                    $val = json_encode($val);
                }
                $data[$f] = $val;
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No fields provided', 400);
            return;
        }

        $repo = $this->repo('animal_medical_records');
        $existing = $repo->findBy('animal_id', $req->params['id']);
        if ($existing) {
            $repo->update($existing['id'], array_merge($data, ['updated_by' => $req->user['id']]));
            $record = $repo->find($existing['id']);
        } else {
            $id = $repo->create(array_merge($data, [
                'id' => Database::uuidV4(),
                'animal_id' => $req->params['id'],
                'updated_by' => $req->user['id'],
            ]));
            $record = $repo->find($id);
        }
        Response::success(['medical' => $record]);
    }
}
