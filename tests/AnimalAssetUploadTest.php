<?php

namespace App\Tests;

use App\Services\AnimalAssetUpload;
use PHPUnit\Framework\TestCase;

class AnimalAssetUploadTest extends TestCase
{
    public function testRejectsDisallowedModelExtension(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('malware.exe', 'application/x-msdownload', 1024),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('Unsupported file type', $err);
        $this->assertStringContainsString('GLB', $err);
    }

    public function testRejectsBlockedMimeEvenWithGlbName(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('sneaky.glb', 'application/x-msdownload', 2048),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('Unsupported file type', $err);
    }

    public function testAcceptsGlbWithBinaryMime(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('profile.glb', 'model/gltf-binary', 4096),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        );
        $this->assertNull($err);
    }

    public function testAcceptsObjAndGltf(): void
    {
        $this->assertNull(AnimalAssetUpload::validate(
            $this->file('mesh.obj', 'text/plain', 512),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        ));
        $this->assertNull(AnimalAssetUpload::validate(
            $this->file('mesh.gltf', 'model/gltf+json', 512),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        ));
    }

    public function testRejectsOversizedModel(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('huge.glb', 'model/gltf-binary', AnimalAssetUpload::MODEL_MAX_BYTES + 1),
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('20 MB', $err);
        $this->assertStringContainsString('huge.glb', $err);
    }

    public function testRejectsOversizedPhoto(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('frame.jpg', 'image/jpeg', AnimalAssetUpload::PHOTO_MAX_BYTES + 1),
            AnimalAssetUpload::photoRules(),
            AnimalAssetUpload::PHOTO_MAX_BYTES,
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('5 MB', $err);
    }

    public function testAcceptsAllowedPhotoTypes(): void
    {
        foreach (['shot.jpg' => 'image/jpeg', 'shot.jpeg' => 'image/jpeg', 'shot.png' => 'image/png', 'shot.webp' => 'image/webp'] as $name => $mime) {
            $this->assertNull(
                AnimalAssetUpload::validate(
                    $this->file($name, $mime, 1200),
                    AnimalAssetUpload::photoRules(),
                    AnimalAssetUpload::PHOTO_MAX_BYTES,
                    false
                ),
                $name
            );
        }
    }

    public function testRejectsGifPhoto(): void
    {
        $err = AnimalAssetUpload::validate(
            $this->file('frame.gif', 'image/gif', 800),
            AnimalAssetUpload::photoRules(),
            AnimalAssetUpload::PHOTO_MAX_BYTES,
            false
        );
        $this->assertNotNull($err);
        $this->assertStringContainsString('JPG', $err);
    }

    public function testPhotoCountBounds(): void
    {
        $this->assertNotNull(AnimalAssetUpload::validatePhotoCount(0));
        $this->assertNotNull(AnimalAssetUpload::validatePhotoCount(3));
        $this->assertNull(AnimalAssetUpload::validatePhotoCount(4));
        $this->assertNull(AnimalAssetUpload::validatePhotoCount(36));
        $this->assertNotNull(AnimalAssetUpload::validatePhotoCount(37));
    }

    public function testSniffsMzHeaderEvenWhenClientMimeLies(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'mz');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, "MZ\0\0not-a-model");
        try {
            $err = AnimalAssetUpload::validate(
                [
                    'name' => 'sneaky.glb',
                    'type' => 'model/gltf-binary',
                    'tmp_name' => $tmp,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmp),
                ],
                AnimalAssetUpload::modelRules(),
                AnimalAssetUpload::MODEL_MAX_BYTES,
                false
            );
            $this->assertNotNull($err);
            $this->assertStringContainsString('Unsupported file type', $err);
        } finally {
            @unlink($tmp);
        }
    }

    public function testSniffsGlbMagic(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'glb');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, 'glTF' . str_repeat("\0", 12));
        try {
            $this->assertSame('model/gltf-binary', AnimalAssetUpload::sniffMime($tmp));
        } finally {
            @unlink($tmp);
        }
    }

    public function testIsUploadedFileRejectedForLocalTemp(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'glb');
        $this->assertNotFalse($tmp);
        file_put_contents($tmp, 'not-a-real-upload');
        try {
            $err = AnimalAssetUpload::validate(
                [
                    'name' => 'profile.glb',
                    'type' => 'model/gltf-binary',
                    'tmp_name' => $tmp,
                    'error' => UPLOAD_ERR_OK,
                    'size' => filesize($tmp),
                ],
                AnimalAssetUpload::modelRules(),
                AnimalAssetUpload::MODEL_MAX_BYTES,
                true
            );
            $this->assertSame('Invalid upload.', $err);
        } finally {
            @unlink($tmp);
        }
    }

    public function testOwnedUrlOnlyMatchesLocalAnimalUploads(): void
    {
        $this->assertTrue(AnimalAssetUpload::isOwnedUrl('/uploads/animals/29cad7f9-987b-491d-8adc-bee601b0a1de.glb'));
        $this->assertFalse(AnimalAssetUpload::isOwnedUrl('/uploads/demo/animal-profile.glb'));
        $this->assertFalse(AnimalAssetUpload::isOwnedUrl('/uploads/animals/../demo/animal-profile.glb'));
        $this->assertFalse(AnimalAssetUpload::isOwnedUrl('/uploads/animals/nested/x.glb'));
        $this->assertFalse(AnimalAssetUpload::isOwnedUrl('https://cdn.example/model.glb'));
        $this->assertFalse(AnimalAssetUpload::isOwnedUrl('/uploads/foo.glb'));
    }

    public function testDecodeUrlList(): void
    {
        $this->assertSame(['/a.jpg', '/b.jpg'], AnimalAssetUpload::decodeUrlList('["/a.jpg","/b.jpg"]'));
        $this->assertSame(['/a.jpg'], AnimalAssetUpload::decodeUrlList(['/a.jpg', '', 3]));
        $this->assertSame([], AnimalAssetUpload::decodeUrlList(null));
        $this->assertSame([], AnimalAssetUpload::decodeUrlList('not-json'));
    }

    public function testNormalizeFilesHandlesSingleAndMulti(): void
    {
        $single = AnimalAssetUpload::normalizeFiles([
            'name' => 'a.jpg',
            'type' => 'image/jpeg',
            'tmp_name' => '/tmp/a',
            'error' => UPLOAD_ERR_OK,
            'size' => 10,
        ]);
        $this->assertCount(1, $single);
        $this->assertSame('a.jpg', $single[0]['name']);

        $multi = AnimalAssetUpload::normalizeFiles([
            'name' => ['a.jpg', 'b.png'],
            'type' => ['image/jpeg', 'image/png'],
            'tmp_name' => ['/tmp/a', '/tmp/b'],
            'error' => [UPLOAD_ERR_OK, UPLOAD_ERR_NO_FILE],
            'size' => [10, 0],
        ]);
        $this->assertCount(1, $multi);
        $this->assertSame('a.jpg', $multi[0]['name']);
    }

    public function testIniSizeMapsToVisibleError(): void
    {
        $err = AnimalAssetUpload::validate(
            ['name' => 'big.glb', 'type' => 'model/gltf-binary', 'tmp_name' => '', 'error' => UPLOAD_ERR_INI_SIZE, 'size' => 0],
            AnimalAssetUpload::modelRules(),
            AnimalAssetUpload::MODEL_MAX_BYTES,
            false
        );
        $this->assertSame('File exceeds the size limit.', $err);
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
}
