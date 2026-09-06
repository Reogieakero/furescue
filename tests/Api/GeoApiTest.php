<?php

namespace App\Tests\Api;

use App\Tests\Support\ApiTestCase;

class GeoApiTest extends ApiTestCase
{
    public function testReverseRequiresAuth(): void
    {
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lat' => (string) self::MATI_LAT,
            'lng' => (string) self::MATI_LNG,
        ]), 'UNAUTHENTICATED', 401);
    }

    public function testReverseRejectsMissingLng(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lat' => (string) self::MATI_LAT,
        ], $this->actingAs($resident)), 'INVALID_COORDS', 400);
    }

    public function testReverseRejectsMissingLat(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lng' => (string) self::MATI_LNG,
        ], $this->actingAs($resident)), 'INVALID_COORDS', 400);
    }

    public function testReverseRejectsAbcLatitude(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lat' => 'abc',
            'lng' => (string) self::MATI_LNG,
        ], $this->actingAs($resident)), 'INVALID_COORDS', 400);
    }

    public function testReverseRejectsAbcLongitude(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lat' => (string) self::MATI_LAT,
            'lng' => 'abc',
        ], $this->actingAs($resident)), 'INVALID_COORDS', 400);
    }

    public function testReverseHappyPathReturnsEnvelope(): void
    {
        $resident = $this->seedResident();
        $data = $this->assertOk($this->get('/api/v1/geo/reverse', [
            'lat' => (string) self::MATI_LAT,
            'lng' => (string) self::MATI_LNG,
        ], $this->actingAs($resident)));
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('full', $data);
    }

    public function testReverseGarbageToken(): void
    {
        $this->assertError($this->get('/api/v1/geo/reverse', [
            'lat' => '1',
            'lng' => '1',
        ], 'nope'), 'UNAUTHENTICATED', 401);
    }

    public function testReverseEmptyQuery(): void
    {
        $resident = $this->seedResident();
        $this->assertError(
            $this->get('/api/v1/geo/reverse', [], $this->actingAs($resident)),
            'INVALID_COORDS',
            400
        );
    }
}
