<?php

use App\Auth\GoogleAuthService;
use App\Auth\JwtService;
use App\Auth\PasswordService;
use App\Controllers\AdoptionController;
use App\Controllers\AdoptionListingController;
use App\Controllers\AnalyticsController;
use App\Controllers\AnimalController;
use App\Controllers\AnimalMedicalController;
use App\Controllers\AuthController;
use App\Controllers\CaseController;
use App\Controllers\ElearningController;
use App\Controllers\HealthController;
use App\Controllers\MessageController;
use App\Controllers\NotificationController;
use App\Controllers\ReportController;
use App\Controllers\UserController;
use App\Controllers\VitalsController;
use App\Controllers\DocumentsController;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\DedupService;
use App\Services\GeoService;
use App\Services\NotificationService;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if (is_string($uri) && str_starts_with($uri, '/uploads/')) {
    $file = __DIR__ . $uri;
    if (!is_file($file)) {
        $file = __DIR__ . '/../../public' . $uri;
    }
    if (is_file($file)) {
        $types = ['svg' => 'image/svg+xml', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($file));
        readfile($file);
        exit;
    }
}

set_error_handler(function ($no, $str, $file, $line) {
    if (!(error_reporting() & $no)) return false;
    Response::error('SERVER_ERROR', "{$str} in {$file}:{$line}", 500);
    exit;
});
set_exception_handler(function (\Throwable $e) {
    Response::error('SERVER_ERROR', $e->getMessage(), 500);
    exit;
});

$pdo = Database::connect();
$jwt = new JwtService();
$password = new PasswordService();
$google = new GoogleAuthService();
$dedup = new DedupService($pdo);
$geo = new GeoService();
$notif = new NotificationService($pdo);

$authMw = new AuthMiddleware($pdo, $jwt);
$adminMw = new RoleMiddleware(['admin']);
$staffMw = new RoleMiddleware(['rescuer', 'admin']);

$router = new Router();

$router->add('POST', '/api/v1/auth/register', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->register($r));
$router->add('POST', '/api/v1/auth/login', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->login($r));
$router->add('POST', '/api/v1/auth/google', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->google($r));
$router->add('POST', '/api/v1/auth/refresh', fn(Request $r) => (new AuthController($pdo, $jwt, $password, $google))->refresh($r));

$router->add('GET', '/api/v1/users/me', fn(Request $r) => (new UserController($pdo))->me($r), [$authMw]);
$router->add('GET', '/api/v1/users', fn(Request $r) => (new UserController($pdo))->index($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/users/{id}', fn(Request $r) => (new UserController($pdo))->show($r), [$authMw]);
$router->add('PATCH', '/api/v1/users/{id}', fn(Request $r) => (new UserController($pdo))->update($r), [$authMw]);
$router->add('POST', '/api/v1/admin/rescuers/{id}/approve', fn(Request $r) => (new UserController($pdo))->approveRescuer($r), [$authMw, $adminMw]);
$router->add('POST', '/api/v1/admin/rescuers/{id}/reject', fn(Request $r) => (new UserController($pdo))->rejectRescuer($r), [$authMw, $adminMw]);

$router->add('POST', '/api/v1/reports', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->create($r), [$authMw]);
$router->add('GET', '/api/v1/reports/me', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->mine($r), [$authMw]);
$router->add('GET', '/api/v1/reports/map/heatmap', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->heatmap($r), [$authMw]);
$router->add('GET', '/api/v1/reports', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->index($r), [$authMw]);
$router->add('GET', '/api/v1/reports/{id}', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->show($r), [$authMw]);
$router->add('POST', '/api/v1/reports/{id}/verify', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->verify($r), [$authMw, $adminMw]);
$router->add('POST', '/api/v1/reports/{id}/dismiss', fn(Request $r) => (new ReportController($pdo, $dedup, $geo))->dismiss($r), [$authMw, $adminMw]);

$router->add('GET', '/api/v1/cases', fn(Request $r) => (new CaseController($pdo))->index($r), [$authMw]);
$router->add('GET', '/api/v1/cases/{id}', fn(Request $r) => (new CaseController($pdo))->show($r), [$authMw]);
$router->add('GET', '/api/v1/cases/{id}/activity', fn(Request $r) => (new CaseController($pdo))->activity($r), [$authMw]);
$router->add('POST', '/api/v1/cases/{id}/assign', fn(Request $r) => (new CaseController($pdo))->assign($r), [$authMw, $adminMw]);
$router->add('PATCH', '/api/v1/cases/{id}/status', fn(Request $r) => (new CaseController($pdo))->updateStatus($r), [$authMw, $staffMw]);

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

$router->add('POST', '/api/v1/adoption-listings', fn(Request $r) => (new AdoptionListingController($pdo))->create($r), [$authMw]);
$router->add('GET', '/api/v1/adoption-listings', fn(Request $r) => (new AdoptionListingController($pdo))->index($r), [$authMw]);
$router->add('GET', '/api/v1/adoption-listings/{id}', fn(Request $r) => (new AdoptionListingController($pdo))->show($r), [$authMw]);
$router->add('POST', '/api/v1/adoption-listings/{id}/approve', fn(Request $r) => (new AdoptionListingController($pdo))->review($r, 'approved'), [$authMw, $adminMw]);
$router->add('POST', '/api/v1/adoption-listings/{id}/reject', fn(Request $r) => (new AdoptionListingController($pdo))->review($r, 'rejected'), [$authMw, $adminMw]);

$router->add('POST', '/api/v1/adoptions', fn(Request $r) => (new AdoptionController($pdo))->apply($r), [$authMw]);
$router->add('GET', '/api/v1/adoptions', fn(Request $r) => (new AdoptionController($pdo))->index($r), [$authMw]);
$router->add('GET', '/api/v1/adoptions/{id}', fn(Request $r) => (new AdoptionController($pdo))->show($r), [$authMw]);
$router->add('POST', '/api/v1/adoptions/{id}/approve', fn(Request $r) => (new AdoptionController($pdo))->review($r, 'approved'), [$authMw, $adminMw]);
$router->add('POST', '/api/v1/adoptions/{id}/reject', fn(Request $r) => (new AdoptionController($pdo))->review($r, 'rejected'), [$authMw, $adminMw]);
$router->add('POST', '/api/v1/adoptions/{id}/complete', fn(Request $r) => (new AdoptionController($pdo))->complete($r), [$authMw, $adminMw]);

$router->add('POST', '/api/v1/messages', fn(Request $r) => (new MessageController($pdo))->send($r), [$authMw]);
$router->add('GET', '/api/v1/messages', fn(Request $r) => (new MessageController($pdo))->thread($r), [$authMw]);
$router->add('PATCH', '/api/v1/messages/{id}/read', fn(Request $r) => (new MessageController($pdo))->markRead($r), [$authMw]);

$router->add('GET', '/api/v1/notifications', fn(Request $r) => (new NotificationController($pdo))->index($r), [$authMw]);
$router->add('PATCH', '/api/v1/notifications/{id}/read', fn(Request $r) => (new NotificationController($pdo))->markRead($r), [$authMw]);
$router->add('POST', '/api/v1/notifications/read-all', fn(Request $r) => (new NotificationController($pdo))->markAllRead($r), [$authMw]);

$router->add('GET', '/api/v1/elearning/modules', fn(Request $r) => (new ElearningController($pdo))->modules($r), [$authMw]);
$router->add('GET', '/api/v1/elearning/modules/{id}', fn(Request $r) => (new ElearningController($pdo))->module($r), [$authMw]);
$router->add('POST', '/api/v1/elearning/modules', fn(Request $r) => (new ElearningController($pdo))->createModule($r), [$authMw, $adminMw]);
$router->add('PATCH', '/api/v1/elearning/modules/{id}', fn(Request $r) => (new ElearningController($pdo))->updateModule($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/elearning/progress', fn(Request $r) => (new ElearningController($pdo))->progress($r), [$authMw]);
$router->add('POST', '/api/v1/elearning/progress', fn(Request $r) => (new ElearningController($pdo))->upsertProgress($r), [$authMw]);

$router->add('GET', '/api/v1/analytics/overview', fn(Request $r) => (new AnalyticsController($pdo))->overview($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/analytics/adoption-trends', fn(Request $r) => (new AnalyticsController($pdo))->adoptionTrends($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/health/updates', fn(Request $r) => (new AnalyticsController($pdo))->healthUpdates($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/health/records', fn(Request $r) => (new HealthController($pdo))->records($r), [$authMw, $adminMw]);
$router->add('GET', '/api/v1/health/activity', fn(Request $r) => (new HealthController($pdo))->activity($r), [$authMw, $adminMw]);

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

$request = new Request();
$router->dispatch($request);
