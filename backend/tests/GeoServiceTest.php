<?php

namespace App\Tests;

use App\Services\GeoService;
use PHPUnit\Framework\TestCase;

class GeoServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['MATI_LAT_MIN'] = '6.89';
        $_ENV['MATI_LAT_MAX'] = '7.01';
        $_ENV['MATI_LNG_MIN'] = '126.13';
        $_ENV['MATI_LNG_MAX'] = '126.27';
    }

    public function testInsideMatiBounds(): void
    {
        $geo = new \App\Services\GeoService();
        $this->assertTrue($geo->inMatiBounds(6.95, 126.22));
    }

    public function testOutsideMatiBounds(): void
    {
        $geo = new \App\Services\GeoService();
        $this->assertFalse($geo->inMatiBounds(10.0, 126.22));
        $this->assertFalse($geo->inMatiBounds(6.95, 130.0));
    }
}
