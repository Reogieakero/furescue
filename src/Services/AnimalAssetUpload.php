<?php

namespace App\Services;

use App\Database;

class AnimalAssetUpload
{
    public const MODEL_MAX_BYTES = 20 * 1024 * 1024;
    public const PHOTO_MAX_BYTES = 5 * 1024 * 1024;
    public const PHOTO_MIN_COUNT = 4;
    public const PHOTO_MAX_COUNT = 36;
    public const PUBLIC_PREFIX = '/uploads/animals/';

    private const BLOCKED_MIME = [
        'application/x-msdownload',
        'application/x-dosexec',
        'application/x-executable',
        'application/x-msdos-program',
        'application/vnd.microsoft.portable-executable',
        'application/x-msi',
        'application/x-sh',
        'application/x-bat',
        'application/javascript',
        'text/html',
    ];

    private string $publicRoot;

    public function __construct(?string $publicRoot = null)
    {
        $this->publicRoot = $publicRoot ?? dirname(__DIR__, 2) . '/public';
    }

    public static function modelRules(): array
    {
        return [
            'glb' => ['model/gltf-binary', 'application/octet-stream', 'application/gltf-binary', 'model/gltf+binary'],
            'gltf' => ['model/gltf+json', 'application/json', 'text/plain', 'text/json', 'application/octet-stream'],
            'obj' => ['model/obj', 'text/plain', 'text/x-obj', 'application/octet-stream', 'application/object'],
        ];
    }

    public static function photoRules(): array
    {
        return [
            'jpg' => ['image/jpeg', 'image/jpg'],
            'jpeg' => ['image/jpeg', 'image/jpg'],
            'png' => ['image/png'],
            'webp' => ['image/webp'],
        ];
    }

    public static function extension(string $filename): string
    {
        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function detectMime(array $file): string
    {
        $tmp = (string) ($file['tmp_name'] ?? '');
        if ($tmp !== '' && is_file($tmp)) {
            if (class_exists(\finfo::class)) {
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $detected = $finfo->file($tmp);
                if (is_string($detected) && $detected !== '') {
                    return strtolower($detected);
                }
            }
            $sniffed = self::sniffMime($tmp);
            if ($sniffed !== '') {
                return $sniffed;
            }
        }
        return strtolower((string) ($file['type'] ?? ''));
    }

    public static function sniffMime(string $path): string
    {
        $fh = fopen($path, 'rb');
        if ($fh === false) {
            return '';
        }
        $head = (string) fread($fh, 16);
        fclose($fh);
        if ($head === '') {
            return '';
        }
        if (str_starts_with($head, 'MZ')) {
            return 'application/x-msdownload';
        }
        if (str_starts_with($head, "\x89PNG\r\n\x1a\n")) {
            return 'image/png';
        }
        if (str_starts_with($head, "\xFF\xD8\xFF")) {
            return 'image/jpeg';
        }
        if (str_starts_with($head, 'RIFF') && substr($head, 8, 4) === 'WEBP') {
            return 'image/webp';
        }
        if (str_starts_with($head, 'glTF')) {
            return 'model/gltf-binary';
        }
        $trim = ltrim($head);
        if (str_starts_with($trim, '{')) {
            return 'model/gltf+json';
        }
        if (str_starts_with($trim, 'v ') || str_starts_with($trim, 'o ') || str_starts_with($trim, '#')) {
            return 'model/obj';
        }
        return '';
    }

    public static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File exceeds the size limit.',
            UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'A file is required.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Could not save the uploaded file.',
            default => 'Invalid upload.',
        };
    }

    public static function validate(array $file, array $extToMimes, int $maxBytes, bool $requireUploaded = true): ?string
    {
        $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($error !== UPLOAD_ERR_OK) {
            return self::uploadErrorMessage($error);
        }
        $name = (string) ($file['name'] ?? '');
        $tmp = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($name === '' || $size <= 0) {
            return 'A file is required.';
        }
        if ($requireUploaded && ($tmp === '' || !is_uploaded_file($tmp))) {
            return 'Invalid upload.';
        }
        if ($size > $maxBytes) {
            $limitMb = (int) round($maxBytes / (1024 * 1024));
            return "'{$name}' exceeds the {$limitMb} MB size limit.";
        }
        $ext = self::extension($name);
        if ($ext === '' || !array_key_exists($ext, $extToMimes)) {
            return self::unsupportedMessage($extToMimes);
        }
        $mime = self::detectMime($file);
        if (in_array($mime, self::BLOCKED_MIME, true)) {
            return self::unsupportedMessage($extToMimes);
        }
        $allowed = $extToMimes[$ext];
        if ($mime !== '' && !in_array($mime, $allowed, true)) {
            return self::unsupportedMessage($extToMimes);
        }
        return null;
    }

    public static function validatePhotoCount(int $count): ?string
    {
        if ($count < self::PHOTO_MIN_COUNT || $count > self::PHOTO_MAX_COUNT) {
            return 'Upload between 4 and 36 photos (JPG, PNG, or WEBP, 5 MB each).';
        }
        return null;
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

    public static function decodeUrlList(mixed $value): array
    {
        if (is_array($value)) {
            $urls = $value;
        } elseif (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            $urls = is_array($decoded) ? $decoded : [];
        } else {
            $urls = [];
        }
        return array_values(array_filter($urls, static fn($u) => is_string($u) && $u !== ''));
    }

    public static function normalizeFiles(mixed $entry): array
    {
        if (!$entry || !is_array($entry) || !isset($entry['name'])) {
            return [];
        }
        if (!is_array($entry['name'])) {
            return [$entry];
        }
        $out = [];
        foreach (array_keys($entry['name']) as $i) {
            if (($entry['error'][$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $out[] = [
                'name' => (string) ($entry['name'][$i] ?? ''),
                'type' => (string) ($entry['type'][$i] ?? ''),
                'tmp_name' => (string) ($entry['tmp_name'][$i] ?? ''),
                'error' => (int) ($entry['error'][$i] ?? UPLOAD_ERR_NO_FILE),
                'size' => (int) ($entry['size'][$i] ?? 0),
            ];
        }
        return $out;
    }

    public function store(array $file): string
    {
        $ext = self::extension((string) $file['name']);
        $dir = $this->animalsDir();
        $stored = Database::uuidV4() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not save the uploaded file.');
        }
        return self::PUBLIC_PREFIX . $stored;
    }

    public function deleteOwned(string $url): void
    {
        if (!self::isOwnedUrl($url)) {
            return;
        }
        $path = $this->animalsDir() . DIRECTORY_SEPARATOR . basename($url);
        if (is_file($path)) {
            @unlink($path);
        }
    }

    public function deleteOwnedList(array $urls): void
    {
        foreach ($urls as $url) {
            if (is_string($url)) {
                $this->deleteOwned($url);
            }
        }
    }

    public function animalsDir(): string
    {
        $dir = $this->publicRoot . '/uploads/animals';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload directory.');
        }
        return $dir;
    }

    private static function unsupportedMessage(array $extToMimes): string
    {
        $exts = array_map('strtoupper', array_keys($extToMimes));
        $unique = [];
        foreach ($exts as $ext) {
            $label = $ext === 'JPEG' ? 'JPG' : $ext;
            $unique[$label] = $label;
        }
        return 'Unsupported file type. Allowed: ' . implode(', ', array_values($unique)) . '.';
    }
}
