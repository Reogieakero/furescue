<?php

namespace App\Http\Routes;

use App\Controllers\AnimalController;
use App\Controllers\AnimalMedicalController;
use App\Controllers\DocumentsController;
use App\Controllers\HealthController;
use App\Controllers\VitalsController;
use App\Http\Request;
use App\Http\Router;

class AnimalRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];
        $adminMw = $d['adminMw'];
        $staffMw = $d['staffMw'];

        $router->add('GET', '/api/v1/animals', fn(Request $r) => (new AnimalController($pdo))->index($r), [$authMw]);
        $router->add('GET', '/api/v1/animals/{id}', fn(Request $r) => (new AnimalController($pdo))->show($r), [$authMw]);
        $router->add('POST', '/api/v1/animals', fn(Request $r) => (new AnimalController($pdo))->create($r), [$authMw, $adminMw]);
        $router->add('PATCH', '/api/v1/animals/{id}', fn(Request $r) => (new AnimalController($pdo))->update($r), [$authMw, $adminMw]);
        $router->add('DELETE', '/api/v1/animals/{id}', fn(Request $r) => (new AnimalController($pdo))->delete($r), [$authMw, $adminMw]);
        $router->add('POST', '/api/v1/animals/{id}/field-status', fn(Request $r) => (new AnimalController($pdo))->logFieldStatus($r), [$authMw, $staffMw]);
        $router->add('GET', '/api/v1/animals/{id}/field-status', fn(Request $r) => (new AnimalController($pdo))->fieldStatusHistory($r), [$authMw]);

        $router->add('GET', '/api/v1/animals/{id}/medical', fn(Request $r) => (new AnimalMedicalController($pdo))->show($r), [$authMw, $adminMw]);
        $router->add('PUT', '/api/v1/animals/{id}/medical', fn(Request $r) => (new AnimalMedicalController($pdo))->upsert($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/animals/{id}/health-record', fn(Request $r) => (new HealthController($pdo))->record($r), [$authMw, $adminMw]);

        $router->add('POST', '/api/v1/vitals', fn(Request $r) => (new VitalsController($pdo))->ingest($r));
        $router->add('GET', '/api/v1/animals/{id}/vitals', fn(Request $r) => (new VitalsController($pdo))->list($r), [$authMw, $staffMw]);
        $router->add('POST', '/api/v1/animals/{id}/vitals', fn(Request $r) => (new VitalsController($pdo))->create($r), [$authMw, $adminMw]);

        $router->add('POST', '/api/v1/animals/{id}/documents', fn(Request $r) => (new DocumentsController($pdo))->create($r), [$authMw, $adminMw]);
        $router->add('PATCH', '/api/v1/documents/{id}', fn(Request $r) => (new DocumentsController($pdo))->update($r), [$authMw, $adminMw]);
        $router->add('DELETE', '/api/v1/documents/{id}', fn(Request $r) => (new DocumentsController($pdo))->delete($r), [$authMw, $adminMw]);
    }
}
