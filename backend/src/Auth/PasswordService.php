<?php

namespace App\Auth;


class PasswordService
{
    private const ALGO = PASSWORD_ARGON2ID;

    public function hash(string $plain): string
    {
        $hash = password_hash($plain, self::ALGO);
        if ($hash === false) {
            throw new \RuntimeException('Password hashing failed (Argon2id unavailable).');
        }
        return $hash;
    }

    public function verify(string $plain, string $hash): bool
    {
        if (empty($hash)) {
            return false;
        }
        return password_verify($plain, $hash);
    }

    public function needsRehash(string $hash): bool
    {
        return password_needs_rehash($hash, self::ALGO);
    }
}
