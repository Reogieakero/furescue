<?php

namespace App\Http\Routes;

use App\Controllers\AnalyticsController;
use App\Controllers\HealthController;
use App\Http\Request;
use App\Http\Router;
use App\Middleware\PermissionMiddleware;

class AnalyticsRoutes
{
    public static function register(Router $router, array $d): void
    {
        $pdo = $d['pdo'];
        $authMw = $d['authMw'];

        $router->add('GET', '/api/v1/analytics/overview', fn(Request $r) => (new AnalyticsController($pdo))->overview($r), [$authMw, new PermissionMiddleware('analytics.read')]);
        $router->add('GET', '/api/v1/analytics/overview/export', fn(Request $r) => (new AnalyticsController($pdo))->exportOverview($r), [$authMw, new PermissionMiddleware('analytics.export')]);
        $router->add('GET', '/api/v1/analytics/adoption-trends', fn(Request $r) => (new AnalyticsController($pdo))->adoptionTrends($r), [$authMw, new PermissionMiddleware('analytics.read')]);
        $router->add('GET', '/api/v1/analytics/adoption-trends/export', fn(Request $r) => (new AnalyticsController($pdo))->exportAdoptionTrends($r), [$authMw, new PermissionMiddleware('analytics.export')]);
        $router->add('GET', '/api/v1/health/updates', fn(Request $r) => (new AnalyticsController($pdo))->healthUpdates($r), [$authMw, new PermissionMiddleware('health.read')]);
        $router->add('GET', '/api/v1/health/updates/export', fn(Request $r) => (new AnalyticsController($pdo))->exportHealthUpdates($r), [$authMw, new PermissionMiddleware('health.export')]);
        $router->add('GET', '/api/v1/health/records', fn(Request $r) => (new HealthController($pdo))->records($r), [$authMw, new PermissionMiddleware('health.read')]);
        $router->add('GET', '/api/v1/health/activity', fn(Request $r) => (new HealthController($pdo))->activity($r), [$authMw, new PermissionMiddleware('health.read')]);
    }
}
