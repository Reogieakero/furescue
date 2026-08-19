<?php

namespace App\Middleware;

use App\Http\Request;
use App\Http\Response;


class RoleMiddleware
{
    
    private array $allowed;

    public function __construct(array $allowed)
    {
        $this->allowed = array_map('strtolower', $allowed);
    }

    public function __invoke(Request $request): ?Response
    {
        if ($request->user === null) {
            return Response::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        $role = strtolower($request->user['role'] ?? '');
        if (!in_array($role, $this->allowed, true)) {
            return Response::error('FORBIDDEN', 'Role not permitted: ' . $role, 403);
        }
        return null;
    }
}
