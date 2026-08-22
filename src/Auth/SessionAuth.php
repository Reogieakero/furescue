<?php

namespace App\Auth;

use App\Entity\User;
use App\Repositories\UserRepository;
use PDO;

final class SessionAuth
{
    public const ERR_INVALID_CREDENTIALS = 'invalid_credentials';
    public const ERR_ACCOUNT_PENDING = 'account_pending';

    public static string $lastError = '';

    public static function attemptLogin(string $email, string $password, PDO $pdo): ?User
    {
        self::$lastError = '';

        $users = new UserRepository($pdo);
        $user = $users->findByEmail(strtolower(trim($email)));

        if ($user === null || !(new PasswordService())->verify($password, (string) $user->passwordHash())) {
            self::$lastError = self::ERR_INVALID_CREDENTIALS;
            return null;
        }

        if ($user->accountStatus() !== 'active') {
            self::$lastError = self::ERR_ACCOUNT_PENDING;
            return null;
        }

        self::start();
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => $user->id(),
            'email' => $user->email(),
            'role' => $user->role(),
        ];

        return $user;
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user(): ?array
    {
        self::start();
        return isset($_SESSION['user']) && is_array($_SESSION['user']) ? $_SESSION['user'] : null;
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }
}
