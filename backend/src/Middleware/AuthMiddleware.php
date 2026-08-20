<?php

namespace App\Middleware;

use App\Auth\JwtService;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use PDO;

class AuthMiddleware
{
    private PDO $pdo;
    private JwtService $jwt;

    public function __construct(PDO $pdo, JwtService $jwt)
    {
        $this->pdo = $pdo;
        $this->jwt = $jwt;
    }

    public function __invoke(Request $request): ?Response
    {
        $token = $request->bearerToken();
        if ($token === null) {
            return Response::error('UNAUTHENTICATED', 'Missing bearer token', 401);
        }

        $payload = $this->jwt->verifyAccessToken($token);
        if ($payload === null) {
            return Response::error('UNAUTHENTICATED', 'Invalid or expired token', 401);
        }

        $stmt = $this->pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$payload['sub']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user) {
            return Response::error('UNAUTHENTICATED', 'User not found', 401);
        }

        if ($user['account_status'] !== 'active') {
            return Response::error(
                'ACCOUNT_PENDING',
                'Account is not active (status: ' . $user['account_status'] . ')',
                403
            );
        }

        unset($user['password_hash']);
        $request->user = $user;
        return null;
    }
}
