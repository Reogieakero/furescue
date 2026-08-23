<?php

namespace App\Http\Routes;

use App\Controllers\CaseController;
use App\Http\Request;
use App\Http\Router;

class CaseRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];
        $adminMw = $d['adminMw'];
        $staffMw = $d['staffMw'];

        $router->add('GET', '/api/v1/cases', fn(Request $r) => (new CaseController($pdo))->index($r), [$authMw]);
        $router->add('GET', '/api/v1/cases/{id}', fn(Request $r) => (new CaseController($pdo))->show($r), [$authMw]);
        $router->add('GET', '/api/v1/cases/{id}/activity', fn(Request $r) => (new CaseController($pdo))->activity($r), [$authMw]);
        $router->add('POST', '/api/v1/cases/{id}/assign', fn(Request $r) => (new CaseController($pdo))->assign($r), [$authMw, $adminMw]);
        $router->add('PATCH', '/api/v1/cases/{id}/status', fn(Request $r) => (new CaseController($pdo))->updateStatus($r), [$authMw, $staffMw]);
        $router->add('POST', '/api/v1/cases/{id}/proof', fn(Request $r) => (new CaseController($pdo))->proof($r), [$authMw, $staffMw]);
    }
}
