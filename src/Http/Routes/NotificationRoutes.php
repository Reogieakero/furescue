<?php

namespace App\Http\Routes;

use App\Controllers\NotificationController;
use App\Http\Request;
use App\Http\Router;

class NotificationRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];

        $router->add('GET', '/api/v1/notifications', fn(Request $r) => (new NotificationController($pdo))->index($r), [$authMw]);
        $router->add('PATCH', '/api/v1/notifications/{id}/read', fn(Request $r) => (new NotificationController($pdo))->markRead($r), [$authMw]);
        $router->add('POST', '/api/v1/notifications/read-all', fn(Request $r) => (new NotificationController($pdo))->markAllRead($r), [$authMw]);
    }
}
