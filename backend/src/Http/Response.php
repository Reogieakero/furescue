<?php

namespace App\Http;

class Response
{
    public static bool $sent = false;

    public static function json(array $payload, int $status = 200): void
    {
        self::$sent = true;
        if (!headers_sent()) {
            http_response_code($status);
            header('Content-Type: application/json; charset=utf-8');
            header('Access-Control-Allow-Origin: *');
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
            header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Device-Key');
        }
        echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public static function success($data = null, int $status = 200, ?array $meta = null): void
    {
        $body = ['success' => true, 'data' => $data, 'error' => null];
        if ($meta !== null) {
            $body['meta'] = $meta;
        }
        self::json($body, $status);
    }

    public static function error(string $code, string $message, int $status = 400, $data = null): void
    {
        self::json([
            'success' => false,
            'data' => $data,
            'error' => ['code' => $code, 'message' => $message],
        ], $status);
    }

    public static function paginated($items, array $meta, int $status = 200): void
    {
        self::json([
            'success' => true,
            'data' => $items,
            'meta' => $meta,
            'error' => null,
        ], $status);
    }
}
