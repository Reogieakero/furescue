<?php

namespace App\Controllers;

use App\Auth\GoogleAuthService;
use App\Auth\JwtService;
use App\Auth\PasswordService;
use App\Database;
use App\Http\Request;
use App\Http\Response;
use App\Repositories\Repository;
use PDO;

class AuthController extends AbstractController
{
    private JwtService $jwt;
    private PasswordService $password;
    private GoogleAuthService $google;

    public function __construct(PDO $pdo, JwtService $jwt, PasswordService $password, GoogleAuthService $google)
    {
        parent::__construct($pdo);
        $this->jwt = $jwt;
        $this->password = $password;
        $this->google = $google;
    }

    public function register(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('full_name')->string(150)
            ->required('email')->email()
            ->required('password')->minLen(8)
            ->optional('role')->in('role', ['resident', 'rescuer'])
            ->optional('phone_number')->string(20)
            ->optional('address')->string(1000);

        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $email = strtolower(trim($req->body['email']));
        $repo = $this->repo('users', ['id','full_name','email','role','account_status','auth_provider','phone_number','address','profile_photo_url','created_at']);

        if ($repo->findBy('email', $email)) {
            Response::error('EMAIL_TAKEN', 'Email already registered', 409);
            return;
        }

        $role = $req->body['role'] ?? 'resident';
        $accountStatus = $role === 'rescuer' ? 'pending' : 'active';

        $id = $repo->create([
            'id' => Database::uuidV4(),
            'full_name' => trim($req->body['full_name']),
            'email' => $email,
            'password_hash' => $this->password->hash($req->body['password']),
            'auth_provider' => 'native',
            'phone_number' => $req->body['phone_number'] ?? null,
            'address' => $req->body['address'] ?? null,
            'role' => $role,
            'account_status' => $accountStatus,
        ]);

        $user = $repo->find($id);
        unset($user['password_hash']);

        $tokens = $this->issueTokens($user);
        Response::success(['user' => $user, 'tokens' => $tokens], 201);
    }

    public function login(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('email')->email()->required('password')->string(255);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $repo = $this->repo('users');
        $user = $repo->findBy('email', strtolower(trim($req->body['email'])));
        if (!$user || !$this->password->verify($req->body['password'], (string) $user['password_hash'])) {
            Response::error('INVALID_CREDENTIALS', 'Email or password is incorrect', 401);
            return;
        }
        if ($user['account_status'] !== 'active') {
            Response::error('ACCOUNT_PENDING', 'Account is not active (status: ' . $user['account_status'] . ')', 403);
            return;
        }

        unset($user['password_hash']);
        $tokens = $this->issueTokens($user);
        Response::success(['user' => $user, 'tokens' => $tokens]);
    }

    public function google(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('id_token')->string(2000);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $payload = $this->google->verifyIdToken($req->body['id_token']);
        if ($payload === null) {
            Response::error('GOOGLE_AUTH_FAILED', 'Google ID token verification failed', 401);
            return;
        }

        $email = strtolower(trim((string) ($payload['email'] ?? '')));
        if ($email === '') {
            Response::error('GOOGLE_AUTH_FAILED', 'Google token missing email', 401);
            return;
        }

        $repo = $this->repo('users');
        $user = $repo->findBy('email', $email);
        if (!$user) {
            $user = $repo->findBy('google_id', $payload['sub'] ?? '');
        }

        if (!$user) {
            $id = $repo->create([
                'id' => Database::uuidV4(),
                'full_name' => $payload['name'] ?? 'Google User',
                'email' => $email,
                'password_hash' => null,
                'auth_provider' => 'google',
                'google_id' => $payload['sub'] ?? null,
                'profile_photo_url' => $payload['picture'] ?? null,
                'role' => 'resident',
                'account_status' => 'active',
            ]);
            $user = $repo->find($id);
        }

        if ($user['account_status'] !== 'active') {
            Response::error('ACCOUNT_PENDING', 'Account is not active', 403);
            return;
        }

        unset($user['password_hash']);
        $tokens = $this->issueTokens($user);
        Response::success(['user' => $user, 'tokens' => $tokens]);
    }

    public function refresh(Request $req): void
    {
        $v = new \App\Validation\Validator($req->body);
        $v->required('refresh_token')->string(1000);
        if (!$v->passes()) {
            Response::error('VALIDATION_ERROR', $v->firstError(), 400);
            return;
        }

        $payload = $this->jwt->verifyRefreshToken($req->body['refresh_token']);
        if ($payload === null) {
            Response::error('INVALID_REFRESH_TOKEN', 'Refresh token invalid or expired', 401);
            return;
        }

        $repo = $this->repo('users');
        $user = $repo->find($payload['sub']);
        if (!$user || $user['account_status'] !== 'active') {
            Response::error('INVALID_REFRESH_TOKEN', 'User not available', 401);
            return;
        }

        unset($user['password_hash']);
        Response::success([
            'access_token' => $this->jwt->issueAccessToken($user),
            'user' => $user,
        ]);
    }

    private function issueTokens(array $user): array
    {
        return [
            'access_token' => $this->jwt->issueAccessToken($user),
            'refresh_token' => $this->jwt->issueRefreshToken($user),
            'token_type' => 'Bearer',
        ];
    }
}
