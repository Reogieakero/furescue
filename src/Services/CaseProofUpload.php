<?php

namespace App\Services;

use App\Database;

class CaseProofUpload
{
    public const MAX_BYTES = 5 * 1024 * 1024;
    public const MAX_FILES = 10;
    public const PUBLIC_PREFIX = '/uploads/cases/';

    private const FILE_KEYS = ['files', 'files[]', 'photos', 'proof', 'file'];

    private string $publicRoot;

    public function __construct(?string $publicRoot = null)
    {
        $this->publicRoot = $publicRoot ?? dirname(__DIR__, 2) . '/public';
    }

    public static function collect(array $filesGlobal): array
    {
        $out = [];
        foreach (self::FILE_KEYS as $key) {
            if (!isset($filesGlobal[$key])) {
                continue;
            }
            $out = array_merge($out, AnimalAssetUpload::normalizeFiles($filesGlobal[$key]));
        }
        return $out;
    }

    public function store(array $file): string
    {
        $error = AnimalAssetUpload::validate($file, AnimalAssetUpload::photoRules(), self::MAX_BYTES);
        if ($error !== null) {
            throw new \InvalidArgumentException($error);
        }

        $ext = AnimalAssetUpload::extension((string) $file['name']);
        $relDir = date('Y') . '/' . date('m');
        $dir = $this->publicRoot . '/uploads/cases/' . $relDir;
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new \RuntimeException('Could not create the upload directory.');
        }

        $stored = Database::uuidV4() . '.' . $ext;
        $dest = $dir . DIRECTORY_SEPARATOR . $stored;
        if (!move_uploaded_file((string) $file['tmp_name'], $dest)) {
            throw new \RuntimeException("Could not save '{$file['name']}'.");
        }

        return self::PUBLIC_PREFIX . $relDir . '/' . $stored;
    }
}
