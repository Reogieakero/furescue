<?php

namespace App\Tests;

use App\Services\UserProfileUpload;
use PHPUnit\Framework\TestCase;

class UserProfileUploadTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'furescue-profile-' . bin2hex(random_bytes(4));
        mkdir($this->root, 0755, true);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->root);
    }

    public function testAcceptsAllowedPhotoTypes(): void
    {
        foreach (['shot.jpg' => 'image/jpeg', 'shot.jpeg' => 'image/jpeg', 'shot.png' => 'image/png', 'shot.webp' => 'image/webp'] as $name => $mime) {
            $this->assertNull(
                UserProfileUpload::validate($this->file($name, $mime, 1200), false),
                $name
            );
        }
    }

    public function testRejectsGifPhoto(): void
    {
        $err = UserProfileUpload::validate($this->file('face.gif', 'image/gif', 800), false);
        $this->assertNotNull($err);
        $this->assertStringContainsString('JPG', $err);
    }

    public function testRejectsOversizedPhoto(): void
    {
        $err = UserProfileUpload::validate(
            $this->file('huge.png', 'image/png', UserProfileUpload::MAX_BYTES + 1),
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('5 MB', $err);
    }

    public function testOwnedUrlOnlyMatchesLocalUserUploads(): void
    {
        $this->assertTrue(UserProfileUpload::isOwnedUrl('/uploads/users/29cad7f9-987b-491d-8adc-bee601b0a1de.png'));
        $this->assertFalse(UserProfileUpload::isOwnedUrl('/uploads/animals/29cad7f9-987b-491d-8adc-bee601b0a1de.png'));
        $this->assertFalse(UserProfileUpload::isOwnedUrl('/uploads/users/../demo/avatar.png'));
        $this->assertFalse(UserProfileUpload::isOwnedUrl('/uploads/users/nested/x.png'));
        $this->assertFalse(UserProfileUpload::isOwnedUrl('https://cdn.example/avatar.png'));
        $this->assertFalse(UserProfileUpload::isOwnedUrl('/uploads/foo.png'));
    }

    public function testAllowedUrlAcceptsHttpsAndOwnedPaths(): void
    {
        $this->assertTrue(UserProfileUpload::isAllowedUrl(null));
        $this->assertTrue(UserProfileUpload::isAllowedUrl(''));
        $this->assertTrue(UserProfileUpload::isAllowedUrl('https://lh3.googleusercontent.com/a/photo'));
        $this->assertTrue(UserProfileUpload::isAllowedUrl('/uploads/users/29cad7f9-987b-491d-8adc-bee601b0a1de.jpg'));
        $this->assertFalse(UserProfileUpload::isAllowedUrl('javascript:alert(1)'));
        $this->assertFalse(UserProfileUpload::isAllowedUrl('data:image/png;base64,aaa'));
        $this->assertFalse(UserProfileUpload::isAllowedUrl('/uploads/animals/29cad7f9-987b-491d-8adc-bee601b0a1de.jpg'));
        $this->assertFalse(UserProfileUpload::isAllowedUrl('not-a-url'));
    }

    public function testStoreAndDeleteOwnedFile(): void
    {
        $tmp = $this->root . DIRECTORY_SEPARATOR . 'src.png';
        file_put_contents($tmp, $this->pngBytes());
        $upload = new UserProfileUpload($this->root, true);
        $url = $upload->store([
            'name' => 'avatar.png',
            'type' => 'image/png',
            'tmp_name' => $tmp,
            'error' => UPLOAD_ERR_OK,
            'size' => filesize($tmp),
        ]);
        $this->assertTrue(UserProfileUpload::isOwnedUrl($url));
        $this->assertFileExists($this->root . '/uploads/users/' . basename($url));
        $upload->deleteOwned($url);
        $this->assertFileDoesNotExist($this->root . '/uploads/users/' . basename($url));
    }

    private function file(string $name, string $type, int $size): array
    {
        return [
            'name' => $name,
            'type' => $type,
            'tmp_name' => '',
            'error' => UPLOAD_ERR_OK,
            'size' => $size,
        ];
    }

    private function pngBytes(): string
    {
        return (string) base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==', true);
    }

    private function removeDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($it as $file) {
            $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
        }
        @rmdir($dir);
    }
}
