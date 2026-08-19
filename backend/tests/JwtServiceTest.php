<?php

namespace App\Tests;

use App\Auth\JwtService;
use PHPUnit\Framework\TestCase;

class JwtServiceTest extends TestCase
{
    protected function setUp(): void
    {
        $_ENV['JWT_SECRET'] = 'unit_test_secret';
        $_ENV['JWT_REFRESH_SECRET'] = 'unit_test_refresh_secret';
        $_ENV['JWT_ALGO'] = 'HS256';
        $_ENV['JWT_TTL_MINUTES'] = '60';
        $_ENV['JWT_REFRESH_TTL_DAYS'] = '7';
    }

    public function testAccessTokenRoundTrips(): void
    {
        $jwt = new JwtService();
        $user = ['id' => 'user-123', 'role' => 'resident'];
        $token = $jwt->issueAccessToken($user);

        $payload = $jwt->verifyAccessToken($token);
        $this->assertNotNull($payload);
        $this->assertSame('user-123', $payload['sub']);
        $this->assertSame('resident', $payload['role']);
        $this->assertSame('access', $payload['type']);
    }

    public function testTamperedTokenFails(): void
    {
        $jwt = new JwtService();
        $token = $jwt->issueAccessToken(['id' => 'x', 'role' => 'admin']);
        $this->assertNull($jwt->verifyAccessToken($token . 'tamper'));
    }

    public function testRefreshTokenIsDistinctType(): void
    {
        $jwt = new JwtService();
        $refresh = $jwt->issueRefreshToken(['id' => 'user-123']);
        $this->assertNull($jwt->verifyAccessToken($refresh));
        $this->assertNotNull($jwt->verifyRefreshToken($refresh));
    }
}
