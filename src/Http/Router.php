<?php

namespace App\Http;

class Router
{

    private array $routes = [];

    private array $fallback = [];

    public function add(string $method, string $pattern, callable $handler, array $middleware = []): void
    {
        $regex = $this->compile($pattern);
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'regex' => $regex,
            'handler' => $handler,
            'middleware' => $middleware,
        ];
    }

    private function compile(string $pattern): string
    {
        $pattern = '/' . ltrim($pattern, '/');
        $regex = preg_replace_callback('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', function ($m) {
            return '(?P<' . $m[1] . '>[^/]+)';
        }, $pattern);
        return '#^' . $regex . '$#';
    }

    public function dispatch(Request $request): void
    {
        if ($request->method === 'OPTIONS') {
            Response::success(null);
            return;
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method) {
                continue;
            }
            if (!preg_match($route['regex'], $request->path, $matches)) {
                continue;
            }

            $params = [];
            foreach ($matches as $k => $v) {
                if (is_string($k)) {
                    $params[$k] = urldecode($v);
                }
            }
            $request->params = $params;

            foreach ($route['middleware'] as $mw) {
                $result = $mw($request);
                if ($result instanceof Response || Response::$sent) {
                    return;
                }
            }

            ($route['handler'])($request);
            return;
        }

        Response::error('NOT_FOUND', 'Route not found: ' . $request->method . ' ' . $request->path, 404);
    }
}
