<?php

namespace App\Controllers;

use App\Database;
use App\Http\Request;
use App\Http\Response;

class DocumentsController extends AbstractController
{
    private const ALLOWED_EXT = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'webp' => 'image/webp',
    ];

    private function uploadsDir(): string
    {
        $dir = __DIR__ . '/../../public/uploads'; // src/Controllers → public/uploads
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        return $dir;
    }

    public function create(Request $req): void
    {
        $animalId = $req->params['id'] ?? '';
        $animal = $this->repo('animals')->find($animalId);
        if (!$animal) {
            Response::error('NOT_FOUND', 'Animal not found', 404);
            return;
        }

        $post = $_POST;
        $file = $_FILES['file'] ?? null;

        $name = trim((string) ($post['name'] ?? ''));
        $docType = trim((string) ($post['doc_type'] ?? '')) ?: null;
        $meta = trim((string) ($post['meta'] ?? '')) ?: null;

        if ($file && $file['error'] === UPLOAD_ERR_OK && $file['size'] > 0) {
            if ($name === '') {
                $name = pathinfo($file['name'], PATHINFO_FILENAME);
            }
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!array_key_exists($ext, self::ALLOWED_EXT)) {
                Response::error('VALIDATION_ERROR', 'Unsupported file type. Allowed: PDF, JPG, PNG, GIF, WEBP.', 400);
                return;
            }
            if (!is_uploaded_file($file['tmp_name'])) {
                Response::error('VALIDATION_ERROR', 'Invalid upload.', 400);
                return;
            }
            $dir = $this->uploadsDir();
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $stored = Database::uuidV4() . '.' . $ext;
            $dest = $dir . '/' . $stored;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                Response::error('SERVER_ERROR', 'Could not save the uploaded file.', 500);
                return;
            }
            $fileUrl = '/uploads/' . $stored;
        } else {
            Response::error('VALIDATION_ERROR', 'A PDF or image file is required.', 400);
            return;
        }

        $id = $this->repo('animal_documents')->create([
            'animal_id' => $animalId,
            'name' => $name,
            'doc_type' => $docType,
            'file_url' => $fileUrl,
            'meta' => $meta,
            'uploaded_by' => $req->user['id'] ?? null,
        ]);

        Response::success(['document' => $this->repo('animal_documents')->find($id)], 201);
    }

    public function update(Request $req): void
    {
        $id = $req->params['id'] ?? '';
        $repo = $this->repo('animal_documents');
        $doc = $repo->find($id);
        if (!$doc) {
            Response::error('NOT_FOUND', 'Document not found', 404);
            return;
        }

        $data = [];
        foreach (['name', 'doc_type', 'meta'] as $f) {
            if (array_key_exists($f, $req->body)) {
                $v = $req->body[$f];
                $data[$f] = $v === '' ? null : (string) $v;
            }
        }
        if (empty($data)) {
            Response::error('VALIDATION_ERROR', 'No fields provided', 400);
            return;
        }
        $repo->update($id, $data);
        Response::success(['document' => $repo->find($id)]);
    }

    public function delete(Request $req): void
    {
        $id = $req->params['id'] ?? '';
        $repo = $this->repo('animal_documents');
        $doc = $repo->find($id);
        if (!$doc) {
            Response::error('NOT_FOUND', 'Document not found', 404);
            return;
        }
        if (!empty($doc['file_url']) && str_starts_with($doc['file_url'], '/uploads/')) {
            $path = $this->uploadsDir() . '/' . basename($doc['file_url']);
            if (is_file($path)) {
                @unlink($path);
            }
        }
        $repo->delete($id);
        Response::success(['deleted' => true]);
    }
}
