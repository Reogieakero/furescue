<?php

namespace App\Http\Routes;

use App\Controllers\AnalyticsController;
use App\Controllers\HealthController;
use App\Http\Request;
use App\Http\Router;

class AnalyticsRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];
        $adminMw = $d['adminMw'];

        $router->add('GET', '/api/v1/analytics/overview', fn(Request $r) => (new AnalyticsController($pdo))->overview($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/analytics/adoption-trends', fn(Request $r) => (new AnalyticsController($pdo))->adoptionTrends($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/health/updates', fn(Request $r) => (new AnalyticsController($pdo))->healthUpdates($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/health/records', fn(Request $r) => (new HealthController($pdo))->records($r), [$authMw, $adminMw]);
        $router->add('GET', '/api/v1/health/activity', fn(Request $r) => (new HealthController($pdo))->activity($r), [$authMw, $adminMw]);
    }
}
