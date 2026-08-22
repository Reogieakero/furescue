<?php

namespace App\Http;

class Request
{
    public string $method;
    public string $path;
    public array $query;
    public array $body;
    public array $headers;
    public ?array $user = null;
    public array $params = [];

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = $this->resolvePath();
        $this->query = $_GET;
        $this->headers = $this->resolveHeaders();
        $this->body = $this->resolveBody();
    }

    private function resolvePath(): string
    {
        $uri = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $script = dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        if ($script !== '/' && str_starts_with($uri, $script)) {
            $uri = substr($uri, strlen($script));
        }
        return '/' . ltrim($uri, '/');
    }

    private function resolveHeaders(): array
    {
        $headers = [];
        foreach ($_SERVER as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }
        return $headers;
    }

    private function resolveBody(): array
    {
        if ($this->method === 'GET' || $this->method === 'DELETE') {
            return [];
        }
        $raw = (string) (file_get_contents('php://input') ?: '');
        if ($raw === '') {
            return [];
        }
        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            return [];
        }
        return $decoded;
    }

    public function header(string $name, $default = null)
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    public function bearerToken(): ?string
    {
        $auth = $this->header('authorization');
        if ($auth && preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return $m[1];
        }
        return null;
    }

    public function input(string $key, $default = null)
    {
        return $this->body[$key] ?? $this->query[$key] ?? $default;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }
}
