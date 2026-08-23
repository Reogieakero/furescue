<?php

namespace App\Http\Routes;

use App\Controllers\UserController;
use App\Http\Request;
use App\Http\Router;

class UserRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];
        $adminMw = $d['adminMw'];

        $router->add('GET', '/api/v1/users/me', fn(Request $r) => (new UserController($pdo))->me($r), [$authMw]);
        $router->add('GET', '/api/v1/users', fn(Request $r) => (new UserController($pdo))->index($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/users/{id}', fn(Request $r) => (new UserController($pdo))->show($r), [$authMw]);
        $router->add('PATCH', '/api/v1/users/{id}', fn(Request $r) => (new UserController($pdo))->update($r), [$authMw]);
        $router->add('POST', '/api/v1/admin/rescuers/{id}/approve', fn(Request $r) => (new UserController($pdo))->approveRescuer($r), [$authMw, $adminMw]);
        $router->add('POST', '/api/v1/admin/rescuers/{id}/reject', fn(Request $r) => (new UserController($pdo))->rejectRescuer($r), [$authMw, $adminMw]);
        $router->add('PATCH', '/api/v1/rescuers/{id}/duty', fn(Request $r) => (new UserController($pdo))->toggleDuty($r), [$authMw, $adminMw]);
    }
}
