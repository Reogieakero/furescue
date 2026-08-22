<?php

namespace App\Http\Routes;

use App\Controllers\AuthController;
use App\Http\Request;
use App\Http\Router;

class AuthRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $jwt = $d['jwt'];
        $password = $d['password'];
        $google = $d['google'];

        $router->add('POST', '/api/v1/auth/register', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->register($r));
        $router->add('POST', '/api/v1/auth/login', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->login($r));
        $router->add('POST', '/api/v1/auth/google', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->google($r));
        $router->add('POST', '/api/v1/auth/refresh', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->refresh($r));
    }
}
