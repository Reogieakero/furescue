<?php

namespace App\Http\Routes;

use App\Controllers\AdoptionController;
use App\Http\Request;
use App\Http\Router;
use App\Middleware\PermissionMiddleware;

class AdoptionRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];

        $router->add('POST', '/api/v1/adoptions', fn(Request $r) => (new AdoptionController($pdo))->apply($r), [$authMw]);
        $router->add('GET', '/api/v1/adoptions', fn(Request $r) => (new AdoptionController($pdo))->index($r), [$authMw]);
        $router->add('GET', '/api/v1/adoptions/{id}', fn(Request $r) => (new AdoptionController($pdo))->show($r), [$authMw]);
        $router->add('POST', '/api/v1/adoptions/{id}/cancel', fn(Request $r) => (new AdoptionController($pdo))->cancel($r), [$authMw]);
        $router->add('POST', '/api/v1/adoptions/{id}/approve', fn(Request $r) => (new AdoptionController($pdo))->review($r, 'approved'), [$authMw, new PermissionMiddleware('adoptions.approve')]);
        $router->add('POST', '/api/v1/adoptions/{id}/reject', fn(Request $r) => (new AdoptionController($pdo))->review($r, 'rejected'), [$authMw, new PermissionMiddleware('adoptions.reject')]);
        $router->add('POST', '/api/v1/adoptions/{id}/complete', fn(Request $r) => (new AdoptionController($pdo))->complete($r), [$authMw, new PermissionMiddleware('adoptions.complete')]);
    }
}
