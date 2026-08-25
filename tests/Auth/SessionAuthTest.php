<?php

namespace App\Tests\Auth;

use App\Auth\SessionAuth;
use PHPUnit\Framework\TestCase;

class SessionAuthTest extends TestCase
{
    public function testHomePathByRole(): void
    {
        $this->assertSame('/admin/', SessionAuth::homePath('admin'));
        $this->assertSame('/reports/', SessionAuth::homePath('resident'));
        $this->assertSame('/reports/', SessionAuth::homePath('rescuer'));
        $this->assertSame('/reports/', SessionAuth::homePath('RESCUER'));
        $this->assertSame('/index.php', SessionAuth::homePath(''));
        $this->assertSame('/index.php', SessionAuth::homePath('unknown'));
    }

    public function testHomeLabelByRole(): void
    {
        $this->assertSame('Dashboard', SessionAuth::homeLabel('admin'));
        $this->assertSame('My Reports', SessionAuth::homeLabel('resident'));
        $this->assertSame('My Reports', SessionAuth::homeLabel('rescuer'));
        $this->assertSame('Home', SessionAuth::homeLabel(''));
    }
}
