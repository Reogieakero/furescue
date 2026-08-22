<?php

namespace App\Tests\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    private const ROW = [
        'id' => 'user-123',
        'full_name' => 'Juan Dela Cruz',
        'email' => 'juan@example.com',
        'password_hash' => '$2y$10$abcdefghijklmnopqrstuv',
        'auth_provider' => 'native',
        'google_id' => null,
        'phone_number' => '+63 900 000 0000',
        'address' => 'Mati City',
        'role' => 'resident',
        'account_status' => 'active',
        'profile_photo_url' => null,
        'created_at' => '2026-01-01 08:00:00',
        'updated_at' => '2026-01-02 09:30:00',
    ];

    public function testFromRowRoundTripsCoreFields(): void
    {
        $user = User::fromRow(self::ROW);

        $this->assertSame('user-123', $user->toArray()['id']);
        $this->assertSame('juan@example.com', $user->toArray()['email']);
        $this->assertSame('resident', $user->toArray()['role']);
    }

    public function testToArrayOmitsPasswordHash(): void
    {
        $data = User::fromRow(self::ROW)->toArray();

        $this->assertArrayNotHasKey('password_hash', $data);
    }

    public function testPasswordHashGetterExposesInternalHash(): void
    {
        $user = User::fromRow(self::ROW);

        $this->assertSame('$2y$10$abcdefghijklmnopqrstuv', $user->passwordHash());
    }

    public function testToArrayMatchesRowMinusPasswordHash(): void
    {
        $expected = self::ROW;
        unset($expected['password_hash']);

        $this->assertSame($expected, User::fromRow(self::ROW)->toArray());
    }
}
