<?php

namespace App\Tests\Api;

use App\Tests\Support\ApiTestCase;

class SharedAuthApiTest extends ApiTestCase
{
    public function testGatedRouteWithoutLoginIsUnauthenticated(): void
    {
        $this->assertError($this->get('/api/v1/users/me'), 'UNAUTHENTICATED', 401);
    }

    public function testGatedRouteWithGarbageTokenIsUnauthenticated(): void
    {
        $this->assertError($this->get('/api/v1/users/me', [], 'not-a-jwt'), 'UNAUTHENTICATED', 401);
    }

    public function testGatedRouteWithExpiredTokenIsUnauthenticated(): void
    {
        $user = $this->seedResident();
        $this->assertError($this->get('/api/v1/users/me', [], $this->expiredToken($user)), 'UNAUTHENTICATED', 401);
    }

    public function testMalformedJsonIsRejectedBeforeAuth(): void
    {
        $this->assertError($this->postInvalidJson('/api/v1/auth/login'), 'INVALID_JSON', 400);
    }

    public function testEmptyBodyOnWriteFailsValidation(): void
    {
        $this->assertError($this->post('/api/v1/auth/register', []), 'VALIDATION_ERROR', 400);
    }

    public function testRefreshRejectsAccessToken(): void
    {
        $user = $this->seedResident();
        $this->assertError(
            $this->post('/api/v1/auth/refresh', ['refresh_token' => $this->actingAs($user)]),
            'INVALID_REFRESH_TOKEN',
            401
        );
    }

    public function testVitalsIngestRejectsMissingDeviceKey(): void
    {
        $animal = $this->seedAnimal();
        $this->assertError(
            $this->post('/api/v1/vitals', ['animal_id' => $animal['id'], 'heart_rate_bpm' => 80]),
            'UNAUTHENTICATED',
            401
        );
    }

    public function testResidentCannotOpenAnalyticsExport(): void
    {
        $resident = $this->seedResident();
        $this->assertError(
            $this->get('/api/v1/analytics/overview/export', [], $this->actingAs($resident)),
            'FORBIDDEN',
            403
        );
    }

    public function testResidentCannotOpenRecentBroadcasts(): void
    {
        $resident = $this->seedResident();
        $this->assertError(
            $this->get('/api/v1/admin/notifications/recent', [], $this->actingAs($resident)),
            'FORBIDDEN',
            403
        );
    }
}
