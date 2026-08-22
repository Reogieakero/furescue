<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
    header('Location: /auth/login.php');
    exit;
}

if (!empty($requiredRole)) {
    $allowed = array_map('strtolower', is_array($requiredRole) ? $requiredRole : [$requiredRole]);
    $currentRole = strtolower((string) ($_SESSION['user']['role'] ?? ''));
    if (!in_array($currentRole, $allowed, true)) {
        header('Location: /index.php');
        exit;
    }
}
