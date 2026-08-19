<?php

namespace App\Auth;

use App\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;


class JwtService
{
    private string $secret;
    private string $refreshSecret;
    private string $algo;
    private int $ttlMinutes;
    private int $refreshTtlDays;

    public function __construct()
    {
        $this->secret = (string) Database::env('JWT_SECRET', 'change_me');
        $this->refreshSecret = (string) Database::env('JWT_REFRESH_SECRET', 'change_me');
        $this->algo = (string) Database::env('JWT_ALGO', 'HS256');
        $this->ttlMinutes = (int) Database::env('JWT_TTL_MINUTES', 60);
        $this->refreshTtlDays = (int) Database::env('JWT_REFRESH_TTL_DAYS', 7);
    }

    
    public function issueAccessToken(array $user): string
    {
        $now = time();
        $payload = [
            'iss' => 'furescue',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($this->ttlMinutes * 60),
            'sub' => $user['id'],
            'role' => $user['role'],
            'type' => 'access',
        ];
        return JWT::encode($payload, $this->secret, $this->algo);
    }

    
    public function issueRefreshToken(array $user): string
    {
        $now = time();
        $payload = [
            'iss' => 'furescue',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + ($this->refreshTtlDays * 86400),
            'sub' => $user['id'],
            'type' => 'refresh',
        ];
        return JWT::encode($payload, $this->refreshSecret, $this->algo);
    }

    
    public function verifyAccessToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algo));
        } catch (ExpiredException | SignatureInvalidException | BeforeValidException | \UnexpectedValueException $e) {
            return null;
        }
        $arr = (array) $decoded;
        if (($arr['type'] ?? null) !== 'access') {
            return null;
        }
        return $arr;
    }

    
    public function verifyRefreshToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->refreshSecret, $this->algo));
        } catch (ExpiredException | SignatureInvalidException | BeforeValidException | \UnexpectedValueException $e) {
            return null;
        }
        $arr = (array) $decoded;
        if (($arr['type'] ?? null) !== 'refresh') {
            return null;
        }
        return $arr;
    }
}
