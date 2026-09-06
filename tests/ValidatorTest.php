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

    public function testInAcceptsAllowedRelatedType(): void
    {
        $v = new Validator(['related_type' => 'adoption']);
        $v->required('related_type')->in('related_type', ['report', 'case', 'adoption']);
        $this->assertTrue($v->passes());
    }

    public function testInRejectsUnknownRelatedType(): void
    {
        $v = new Validator(['related_type' => 'listing']);
        $v->required('related_type')->in('related_type', ['report', 'case', 'adoption']);
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

    public function testStringLastFieldFormEnforcesMaxLength(): void
    {
        $v = new Validator(['animal_description' => str_repeat('x', 21)]);
        $v->required('animal_description')->string(20);
        $this->assertFalse($v->passes());
        $this->assertArrayHasKey('animal_description', $v->errors());
    }

    public function testStringExplicitFieldFormRejectsNonString(): void
    {
        $v = new Validator(['full_name' => 123]);
        $v->required('full_name')->string('full_name', 150);
        $this->assertFalse($v->passes());
        $this->assertSame('must be a string', $v->errors()['full_name']);
    }

    public function testRequiredRejectsWhitespaceOnly(): void
    {
        $v = new Validator(['full_name' => '   ']);
        $v->required('full_name')->string('full_name', 150);
        $this->assertFalse($v->passes());
    }

    public function testMinLenLastFieldForm(): void
    {
        $v = new Validator(['password' => 'abc']);
        $v->required('password')->minLen(8);
        $this->assertFalse($v->passes());
    }

    public function testNumericRejectsNonNumeric(): void
    {
        $v = new Validator(['heart_rate_bpm' => 'hot']);
        $v->required('heart_rate_bpm')->numeric();
        $this->assertFalse($v->passes());
    }

    public function testNumericAcceptsNumericString(): void
    {
        $v = new Validator(['heart_rate_bpm' => '80']);
        $v->required('heart_rate_bpm')->numeric('heart_rate_bpm');
        $this->assertTrue($v->passes());
    }

    public function testBooleanRejectsGarbage(): void
    {
        $v = new Validator(['neutered' => 'maybe']);
        $v->optional('neutered')->boolean();
        $this->assertFalse($v->passes());
    }

    public function testBooleanAcceptsTrueFalseTokens(): void
    {
        $v = new Validator(['neutered' => 'true']);
        $v->optional('neutered')->boolean('neutered');
        $this->assertTrue($v->passes());
    }

    public function testUuidRejectsNonCanonicalId(): void
    {
        $v = new Validator(['animal_id' => 'animal-1']);
        $v->required('animal_id')->uuid();
        $this->assertFalse($v->passes());
    }

    public function testUuidAcceptsCanonicalHex(): void
    {
        $v = new Validator(['animal_id' => 'aaaaaaaa-bbbb-cccc-dddd-eeeeeeeeeeee']);
        $v->required('animal_id')->uuid('animal_id');
        $this->assertTrue($v->passes());
    }

    public function testLatitudeRejectsNonNumericBeforeRangeCast(): void
    {
        $v = new Validator(['lat' => 'abc']);
        $v->required('lat')->latitude();
        $this->assertFalse($v->passes());
        $this->assertSame('must be numeric', $v->errors()['lat']);
    }

    public function testLongitudeRejectsNonNumericBeforeRangeCast(): void
    {
        $v = new Validator(['lng' => 'abc']);
        $v->required('lng')->longitude();
        $this->assertFalse($v->passes());
        $this->assertSame('must be numeric', $v->errors()['lng']);
    }

    public function testStringOrStringListRejectsObjectAndNumbers(): void
    {
        $objects = new Validator(['photo_urls' => ['url' => 'x']]);
        $objects->optional('photo_urls')->stringOrStringList('photo_urls', 4000);
        $this->assertFalse($objects->passes());

        $numbers = new Validator(['photo_urls' => [1, 2]]);
        $numbers->optional('photo_urls')->stringOrStringList('photo_urls', 4000);
        $this->assertFalse($numbers->passes());
    }

    public function testMinRejectsNegativeHeartRate(): void
    {
        $v = new Validator(['heart_rate_bpm' => -5]);
        $v->required('heart_rate_bpm')->numeric('heart_rate_bpm')->min('heart_rate_bpm', 1);
        $this->assertFalse($v->passes());
    }
}
