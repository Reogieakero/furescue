<?php

namespace App\Http\Routes;

use App\Http\Request;
use App\Http\Response;
use App\Http\Router;

class GeoRoutes
{
    public static function register(Router $router, array $d): void
    {
        $geo = $d['geo'];
        $authMw = $d['authMw'];

        $router->add('GET', '/api/v1/geo/reverse', function (Request $r) use ($geo) {
            $lat = $r->query['lat'] ?? null;
            $lng = $r->query['lng'] ?? null;
            if (!is_numeric($lat) || !is_numeric($lng)) {
                Response::error('INVALID_COORDS', 'lat and lng query parameters are required', 400);
                return;
            }
            $result = $geo->reverseGeocode((float) $lat, (float) $lng);
            Response::success($result ?? ['name' => null, 'road' => null, 'full' => null]);
        }, [$authMw]);
    }
}
