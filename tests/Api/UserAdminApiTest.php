<?php

namespace App\Tests\Api;

use App\Tests\Support\ApiTestCase;

class UserAdminApiTest extends ApiTestCase
{
    public function testResidentCannotCreateUser(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->post('/api/v1/users', [
            'full_name' => 'New Person',
            'email' => 'new-person@test.local',
            'password' => 'Password123!',
        ], $this->actingAs($resident)), 'FORBIDDEN', 403);
    }

    public function testAdminCanCreateUser(): void
    {
        $admin = $this->seedAdmin();
        $data = $this->assertOk($this->post('/api/v1/users', [
            'full_name' => 'Liza Reyes',
            'email' => 'liza@test.local',
            'password' => 'Password123!',
            'role' => 'admin',
            'phone_number' => '09170001111',
            'address' => 'Mati City',
        ], $this->actingAs($admin)), 201);
        $this->assertSame('Liza Reyes', $data['user']['full_name']);
        $this->assertSame('liza@test.local', $data['user']['email']);
        $this->assertSame('admin', $data['user']['role']);
        $this->assertSame('active', $data['user']['account_status']);
        $this->assertArrayNotHasKey('password_hash', $data['user']);
    }

    public function testCreateRejectsDuplicateEmail(): void
    {
        $admin = $this->seedAdmin();
        $this->seedResident(['email' => 'taken@test.local']);
        $this->assertError($this->post('/api/v1/users', [
            'full_name' => 'Copy',
            'email' => 'taken@test.local',
            'password' => 'Password123!',
        ], $this->actingAs($admin)), 'EMAIL_TAKEN', 409);
    }

    public function testCreateRejectsShortPassword(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/users', [
            'full_name' => 'Short Pass',
            'email' => 'short@test.local',
            'password' => 'abc',
        ], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testAdminCanSearchUsers(): void
    {
        $admin = $this->seedAdmin();
        $this->seedResident(['email' => 'findme@test.local', 'full_name' => 'Find Me']);
        $this->seedResident(['email' => 'other@test.local', 'full_name' => 'Other']);
        $response = $this->get('/api/v1/users', ['q' => 'findme'], $this->actingAs($admin));
        $this->assertOk($response);
        $emails = array_column($response['body']['data'], 'email');
        $this->assertContains('findme@test.local', $emails);
        $this->assertNotContains('other@test.local', $emails);
    }

    public function testAdminCanChangeAnotherUsersEmail(): void
    {
        $admin = $this->seedAdmin();
        $resident = $this->seedResident();
        $data = $this->assertOk($this->patch(
            '/api/v1/users/' . $resident['id'],
            ['email' => 'renamed@test.local'],
            $this->actingAs($admin)
        ));
        $this->assertSame('renamed@test.local', $data['user']['email']);
    }

    public function testAdminCanResetAnotherUsersPassword(): void
    {
        $admin = $this->seedAdmin();
        $resident = $this->seedResident();
        $this->assertOk($this->patch(
            '/api/v1/users/' . $resident['id'],
            ['password' => 'NewPass123!'],
            $this->actingAs($admin)
        ));
        $this->assertOk($this->post('/api/v1/auth/login', [
            'email' => $resident['email'],
            'password' => 'NewPass123!',
        ]));
    }

    public function testAdminCanChangeAnotherAdminWhenAnotherRemains(): void
    {
        $admin = $this->seedAdmin();
        $other = $this->seedAdmin();
        $data = $this->assertOk($this->patch(
            '/api/v1/users/' . $other['id'],
            ['role' => 'rescuer', 'account_status' => 'active'],
            $this->actingAs($admin)
        ));
        $this->assertSame('rescuer', $data['user']['role']);
    }

    public function testAdminCanDeleteUnusedUser(): void
    {
        $admin = $this->seedAdmin();
        $resident = $this->seedResident();
        $data = $this->assertOk($this->delete('/api/v1/users/' . $resident['id'], $this->actingAs($admin)));
        $this->assertTrue($data['deleted']);
        $this->assertError(
            $this->get('/api/v1/users/' . $resident['id'], [], $this->actingAs($admin)),
            'NOT_FOUND',
            404
        );
    }

    public function testAdminCannotDeleteSelf(): void
    {
        $admin = $this->seedAdmin();
        $this->seedAdmin();
        $this->assertError(
            $this->delete('/api/v1/users/' . $admin['id'], $this->actingAs($admin)),
            'FORBIDDEN',
            403
        );
    }

    public function testAdminCannotDeleteLastActiveAdmin(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError(
            $this->delete('/api/v1/users/' . $admin['id'], $this->actingAs($admin)),
            'FORBIDDEN',
            403
        );
    }

    public function testDeleteBlockedWhenUserHasReports(): void
    {
        $admin = $this->seedAdmin();
        $resident = $this->seedResident();
        $this->seedReport(['resident_id' => $resident['id']]);
        $this->assertError(
            $this->delete('/api/v1/users/' . $resident['id'], $this->actingAs($admin)),
            'CONFLICT',
            409
        );
    }

    public function testResidentCannotDeleteUser(): void
    {
        $resident = $this->seedResident();
        $other = $this->seedResident();
        $this->assertError(
            $this->delete('/api/v1/users/' . $other['id'], $this->actingAs($resident)),
            'FORBIDDEN',
            403
        );
    }
}
