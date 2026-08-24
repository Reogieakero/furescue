<?php

namespace App\Middleware;

use App\Auth\Permissions;
use App\Http\Request;
use App\Http\Response;

class PermissionMiddleware
{
    private string $permission;

    public function __construct(string $permission)
    {
        $this->permission = $permission;
    }

    public function __invoke(Request $request): ?Response
    {
        if ($request->user === null) {
            return Response::error('UNAUTHENTICATED', 'Authentication required', 401);
        }
        if (!Permissions::has($request->permissions, $this->permission)) {
            return Response::error('FORBIDDEN', 'Permission not permitted: ' . $this->permission, 403);
        }
        return null;
    }
}
