<?php

namespace App\Services;

use App\Database;

class UserProfileUpload
{
    public const PUBLIC_PREFIX = '/uploads/users/';
    public const MAX_BYTES = AnimalAssetUpload::PHOTO_MAX_BYTES;

    private string $publicRoot;
    private bool $allowLocalCopy;

    public function __construct(?string $publicRoot = null, ?bool $allowLocalCopy = null)
    {
        $configured = Database::env('UPLOAD_PUBLIC_ROOT', '');
        $this->publicRoot = $publicRoot
            ?? ($configured !== '' && $configured !== null ? (string) $configured : dirname(__DIR__, 2) . '/public');
        $this->allowLocalCopy = $allowLocalCopy ?? ((string) Database::env('APP_TESTING', '') === '1');
    }

    public static function rules(): array
    {
        return AnimalAssetUpload::photoRules();
    }

    public function requiresUploadedFile(): bool
    {
        return !$this->allowLocalCopy;
    }

    public static function validate(array $file, bool $requireUploaded = true): ?string
    {
        return AnimalAssetUpload::validate($file, self::rules(), self::MAX_BYTES, $requireUploaded);
    }

    public static function isOwnedUrl(string $url): bool
    {
        if (!str_starts_with($url, self::PUBLIC_PREFIX)) {
            return false;
        }
        $rest = substr($url, strlen(self::PUBLIC_PREFIX));
        if ($rest === '' || str_contains($rest, '..') || str_contains($rest, '/') || str_contains($rest, '\\')) {
            return false;
        }
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}\.[a-z0-9]+$/i', $rest);
    }

    public static function isAllowedUrl(?string $url): bool
    {
        if ($url === null) {
            return true;
        }
        $url = trim($url);
        if ($url === '') {
            return true;
        }
        if (strlen($url) > 2000) {
            return false;
        }
        if (self::isOwnedUrl($url)) {
            return true;
        }
        if (preg_match('/[\s<>"\']/', $url)) {
            return false;
        }
        $parts = parse_url($url);
        if (!is_array($parts) || empty($parts['scheme']) || empty($parts['host'])) {
            return false;
        }
        $scheme = strtolower((string) $parts['scheme']);
        return $scheme === 'http' || $scheme === 'https';
    }

    public function store(array $file): string
    {
        $ext = AnimalAssetUpload::extension((string) $file['name']);
        $dir = $this->usersDir();
        $stored = Database::uuidV4() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        $tmp = (string) ($file['tmp_name'] ?? '');
        if (!$this->persist($tmp, $dest)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }
        return self::PUBLIC_PREFIX . $stored;
    }

    public function deleteOwned(string $url): void
    {
        if (!self::isOwnedUrl($url)) {
            return;
        }
        $path = $this->usersDir() . DIRECTORY_SEPARATOR . basename($url);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function usersDir(): string
    {
        $dir = $this->publicRoot . '/uploads/users';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload directory.');
        }
        return $dir;
    }

    private function persist(string $tmp, string $dest): bool
    {
        if ($tmp !== '' && is_uploaded_file($tmp)) {
            return move_uploaded_file($tmp, $dest);
        }
        if ($this->allowLocalCopy && $tmp !== '' && is_file($tmp)) {
            return copy($tmp, $dest);
        }
        return false;
    }
}
