<?php

use App\Auth\GoogleAuthService;
use App\Auth\JwtService;
use App\Auth\PasswordService;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Http\Router;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\DedupService;
use App\Services\GeoService;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

$uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
if ($uri === '/' || $uri === '/index.html' || $uri === '/index.php') {
    require __DIR__ . '/includes/homepage.php';
    exit;
}
$docRoot = realpath(__DIR__);
$requestPath = realpath(__DIR__ . rawurldecode((string) $uri));
if ($docRoot !== false && $requestPath !== false && str_starts_with($requestPath, $docRoot . DIRECTORY_SEPARATOR) && is_file($requestPath)) {
    return false;
}
if (
    $docRoot !== false
    && $requestPath !== false
    && str_starts_with($requestPath, $docRoot . DIRECTORY_SEPARATOR)
    && is_dir($requestPath)
    && is_file($requestPath . DIRECTORY_SEPARATOR . 'index.php')
) {
    if (!str_ends_with((string) $uri, '/')) {
        $query = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_QUERY);
        header('Location: ' . rtrim((string) $uri, '/') . '/' . ($query !== null ? '?' . $query : ''), true, 302);
        exit;
    }
    return false;
}
if (is_string($uri) && str_starts_with($uri, '/uploads/')) {
    $file = __DIR__ . $uri;
    if (is_file($file)) {
        $types = ['svg' => 'image/svg+xml', 'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp', 'mp4' => 'video/mp4', 'webm' => 'video/webm'];
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

$authMw = new AuthMiddleware($pdo, $jwt);
$adminMw = new RoleMiddleware(['admin']);
$staffMw = new RoleMiddleware(['rescuer', 'admin']);

$deps = [
    'pdo' => $pdo,
    'jwt' => $jwt,
    'password' => $password,
    'google' => $google,
    'dedup' => $dedup,
    'geo' => $geo,
    'authMw' => $authMw,
    'adminMw' => $adminMw,
    'staffMw' => $staffMw,
];

$router = new Router();

App\Http\RouteLoader::register($router, $deps);

$request = new Request();
$router->dispatch($request);
