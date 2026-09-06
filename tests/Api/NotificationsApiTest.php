<?php

namespace App\Tests\Api;

use App\Controllers\NotificationController;
use App\Tests\Support\ApiTestCase;

class NotificationsApiTest extends ApiTestCase
{
    public function testIndexReturnsOwnNotifications(): void
    {
        $user = $this->seedResident();
        $other = $this->seedResident();
        $this->seedNotification(['user_id' => $user['id']]);
        $this->seedNotification(['user_id' => $other['id']]);
        $response = $this->get('/api/v1/notifications', [], $this->actingAs($user));
        $this->assertOk($response);
        $this->assertSame(1, $response['body']['meta']['total']);
    }

    public function testIndexRejectsBadPage(): void
    {
        $user = $this->seedResident();
        $this->assertError(
            $this->get('/api/v1/notifications', ['page' => '0'], $this->actingAs($user)),
            'VALIDATION_ERROR',
            400
        );
    }

    public function testIndexRejectsBadPerPage(): void
    {
        $user = $this->seedResident();
        $this->assertError(
            $this->get('/api/v1/notifications', ['per_page' => '101'], $this->actingAs($user)),
            'VALIDATION_ERROR',
            400
        );
    }

    public function testCannotMarkAnotherUsersNotificationRead(): void
    {
        $owner = $this->seedResident();
        $other = $this->seedResident();
        $note = $this->seedNotification(['user_id' => $owner['id']]);
        $this->assertError(
            $this->patch('/api/v1/notifications/' . $note['id'] . '/read', [], $this->actingAs($other)),
            'FORBIDDEN',
            403
        );
    }

    public function testOwnerCanMarkRead(): void
    {
        $owner = $this->seedResident();
        $note = $this->seedNotification(['user_id' => $owner['id']]);
        $data = $this->assertOk($this->patch(
            '/api/v1/notifications/' . $note['id'] . '/read',
            [],
            $this->actingAs($owner)
        ));
        $this->assertTrue((bool) $data['notification']['is_read']);
    }

    public function testMarkReadUnknown(): void
    {
        $owner = $this->seedResident();
        $this->assertError(
            $this->patch('/api/v1/notifications/' . $this->uuid() . '/read', [], $this->actingAs($owner)),
            'NOT_FOUND',
            404
        );
    }

    public function testMarkAllRead(): void
    {
        $owner = $this->seedResident();
        $this->seedNotification(['user_id' => $owner['id']]);
        $this->seedNotification(['user_id' => $owner['id']]);
        $data = $this->assertOk($this->post('/api/v1/notifications/read-all', [], $this->actingAs($owner)));
        $this->assertGreaterThanOrEqual(2, $data['updated']);
    }

    public function testUnreadCount(): void
    {
        $owner = $this->seedResident();
        $this->seedNotification(['user_id' => $owner['id'], 'is_read' => 0]);
        $data = $this->assertOk($this->get('/api/v1/notifications/unread-count', [], $this->actingAs($owner)));
        $this->assertSame(1, $data['count']);
    }

    public function testResidentCannotBroadcast(): void
    {
        $resident = $this->seedResident();
        $this->assertError($this->post('/api/v1/admin/notifications', [
            'message' => 'Hello city',
        ], $this->actingAs($resident)), 'FORBIDDEN', 403);
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

    public function testAdminCanBroadcast(): void
    {
        $admin = $this->seedAdmin();
        $this->seedResident();
        $data = $this->assertOk($this->post('/api/v1/admin/notifications', [
            'message' => 'Shelter closed tomorrow',
            'targets' => ['role:resident'],
        ], $this->actingAs($admin)), 201);
        $this->assertGreaterThanOrEqual(1, $data['sent']);
    }

    public function testBroadcastAliasRouteWorks(): void
    {
        $admin = $this->seedAdmin();
        $this->assertOk($this->post('/api/v1/admin/notifications/broadcast', [
            'message' => 'Ping',
            'targets' => ['role:admin'],
        ], $this->actingAs($admin)), 201);
    }

    public function testBroadcastRejectsEmptyBody(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/admin/notifications', [], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testBroadcastRejectsNumericMessage(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/admin/notifications', [
            'message' => 12,
        ], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testBroadcastRejectsOverMaxMessage(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/admin/notifications', [
            'message' => str_repeat('x', 1001),
        ], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testBroadcastRejectsInvalidTarget(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/admin/notifications', [
            'message' => 'Hi',
            'targets' => ['role:wizard'],
        ], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testAdminCanListRecentBroadcasts(): void
    {
        $admin = $this->seedAdmin();
        $this->seedNotification(['user_id' => $admin['id'], 'type' => 'admin_announcement', 'message' => 'Town hall']);
        $data = $this->assertOk($this->get('/api/v1/admin/notifications/recent', [], $this->actingAs($admin)));
        $this->assertNotEmpty($data['broadcasts']);
    }

    public function testResidentCannotDeleteSomeoneElsesNotification(): void
    {
        $owner = $this->seedResident();
        $other = $this->seedResident();
        $note = $this->seedNotification(['user_id' => $owner['id']]);
        $this->assertError(
            $this->delete('/api/v1/admin/notifications/' . $note['id'], $this->actingAs($other)),
            'FORBIDDEN',
            403
        );
    }

    public function testAdminCanDeleteNotification(): void
    {
        $admin = $this->seedAdmin();
        $note = $this->seedNotification(['user_id' => $admin['id']]);
        $this->assertOk($this->delete('/api/v1/admin/notifications/' . $note['id'], $this->actingAs($admin)), 204);
    }

    public function testDeleteUnknownNotification(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError(
            $this->delete('/api/v1/admin/notifications/' . $this->uuid(), $this->actingAs($admin)),
            'NOT_FOUND',
            404
        );
    }

    public function testIndexRequiresAuth(): void
    {
        $this->assertError($this->get('/api/v1/notifications'), 'UNAUTHENTICATED', 401);
    }

    public function testStreamRejectsBadToken(): void
    {
        $this->assertError($this->get('/api/v1/notifications/stream', [], 'garbage'), 'UNAUTHENTICATED', 401);
    }

    public function testStreamDoesNotHoldBuiltInServer(): void
    {
        $this->assertFalse(NotificationController::holdConnectionOnSapi('cli-server'));
        $this->assertTrue(NotificationController::holdConnectionOnSapi('fpm-fcgi'));
        $this->assertTrue(NotificationController::holdConnectionOnSapi('cli'));
    }

    public function testBroadcastWhitespaceMessageFails(): void
    {
        $admin = $this->seedAdmin();
        $this->assertError($this->post('/api/v1/admin/notifications', [
            'message' => '   ',
        ], $this->actingAs($admin)), 'VALIDATION_ERROR', 400);
    }

    public function testUnreadCountRequiresAuth(): void
    {
        $this->assertError($this->get('/api/v1/notifications/unread-count'), 'UNAUTHENTICATED', 401);
    }
}
