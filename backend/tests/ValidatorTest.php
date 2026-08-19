<?php

namespace App\Tests;

use App\Validation\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testRequiredDetectsMissing(): void
    {
        $v = new Validator([]);
        $v->required('email');
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('email', $v->errors());
    }

    public function testEmailRejectsBadValue(): void
    {
        $v = new Validator(['email' => 'not-an-email']);
        $v->required('email')->email();
        $this->assertFalse($v->passes());
    }

    public function testEmailAcceptsGoodValue(): void
    {
        $v = new Validator(['email' => 'juan@example.com']);
        $v->required('email')->email();
        $this->assertTrue($v->passes());
    }

    public function testInRejectsUnknownEnum(): void
    {
        $v = new Validator(['role' => 'wizard']);
        $v->required('role')->in('role', ['resident', 'rescuer', 'admin']);
        $this->assertFalse($v->passes());
    }

    public function testLatitudeOutOfRange(): void
    {
        $v = new Validator(['lat' => 200]);
        $v->required('lat')->latitude('lat');
        $this->assertFalse($v->passes());
    }

    public function testPassesWhenAllValid(): void
    {
        $v = new Validator(['email' => 'a@b.com', 'role' => 'admin']);
        $v->required('email')->email();
        $v->required('role')->in('role', ['resident', 'rescuer', 'admin']);
        $this->assertTrue($v->passes());
    }
}
