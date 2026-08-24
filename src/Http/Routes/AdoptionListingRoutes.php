<?php

namespace App\Http\Routes;

use App\Controllers\AdoptionListingController;
use App\Http\Request;
use App\Http\Router;
use App\Middleware\PermissionMiddleware;

class AdoptionListingRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];

        $router->add('POST', '/api/v1/adoption-listings', fn(Request $r) => (new AdoptionListingController($pdo))->create($r), [$authMw, new PermissionMiddleware('adoptions.listings.create')]);
        $router->add('GET', '/api/v1/adoption-listings', fn(Request $r) => (new AdoptionListingController($pdo))->index($r), [$authMw]);
        $router->add('GET', '/api/v1/adoption-listings/{id}', fn(Request $r) => (new AdoptionListingController($pdo))->show($r), [$authMw]);
        $router->add('POST', '/api/v1/adoption-listings/{id}/approve', fn(Request $r) => (new AdoptionListingController($pdo))->review($r, 'approved'), [$authMw, new PermissionMiddleware('adoptions.listings.approve')]);
        $router->add('POST', '/api/v1/adoption-listings/{id}/reject', fn(Request $r) => (new AdoptionListingController($pdo))->review($r, 'rejected'), [$authMw, new PermissionMiddleware('adoptions.listings.reject')]);
    }
}
