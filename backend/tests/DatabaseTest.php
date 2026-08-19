<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use App\Database;

class DatabaseTest extends TestCase
{
    public function testUuidV4Format(): void
    {
        $uuid = Database::uuidV4();
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/',
            $uuid,
            'uuidV4() must return a valid UUIDv4 string'
        );
    }
}
