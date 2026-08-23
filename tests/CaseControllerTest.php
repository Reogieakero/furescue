<?php

namespace App\Tests;

require_once __DIR__ . '/Support/SqliteTestDatabase.php';
require_once __DIR__ . '/Support/InteractsWithHttp.php';

use App\Controllers\CaseController;
use App\Database;
use App\Repositories\ReportRepository;
use App\Services\DedupService;
use App\Tests\Support\InteractsWithHttp;
use App\Tests\Support\SqliteTestDatabase;
use PDO;
use PHPUnit\Framework\TestCase;

class CaseControllerTest extends TestCase
{
    use InteractsWithHttp;

    private PDO $pdo;

    protected function setUp(): void
    {
        if (!in_array('sqlite', PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('pdo_sqlite driver is not available.');
        }
        SqliteTestDatabase::env();
        $this->pdo = SqliteTestDatabase::create();
    }

    public function testAssignRejectsOffDutyRescuer(): void
    {
        $caseId = $this->seedOpenCase();
        $this->seedUser('rescuer-1', 'rescuer');
        $this->seedDuty('rescuer-1', 'off_duty');

        $response = $this->assign($caseId, 'rescuer-1');

        $this->assertFalse($response['body']['success']);
        $this->assertSame('RESCUER_OFF_DUTY', $response['body']['error']['code']);
        $case = $this->caseRow($caseId);
        $this->assertNull($case['assigned_rescuer_id']);
    }

    public function testAssignSucceedsForOnDutyRescuerAndNotifiesThem(): void
    {
        $caseId = $this->seedOpenCase();
        $this->seedUser('rescuer-1', 'rescuer');
        $this->seedDuty('rescuer-1', 'on_duty');

        $response = $this->assign($caseId, 'rescuer-1');

        $this->assertTrue($response['body']['success']);
        $case = $this->caseRow($caseId);
        $this->assertSame('assigned', $case['status']);
        $this->assertSame('rescuer-1', $case['assigned_rescuer_id']);

        $notifications = (int) $this->countWhere(
            'notifications',
            'user_id = ? AND type = ?',
            ['rescuer-1', 'case_assigned']
        );
        $this->assertSame(1, $notifications);

        $activity = (int) $this->countWhere('case_activity_log', 'case_id = ? AND action = ?', [$caseId, 'assigned']);
        $this->assertSame(1, $activity);
    }

    public function testAssignRejectsNonRescuerUser(): void
    {
        $caseId = $this->seedOpenCase();
        $this->seedUser('resident-1', 'resident');

        $response = $this->assign($caseId, 'resident-1');

        $this->assertFalse($response['body']['success']);
        $this->assertSame('INVALID_RESCUER', $response['body']['error']['code']);
    }

    public function testAssignRejectsInactiveRescuerEvenWhenOnDuty(): void
    {
        $caseId = $this->seedOpenCase();
        $this->seedUser('rescuer-pending', 'rescuer', 'pending');
        $this->seedDuty('rescuer-pending', 'on_duty');

        $response = $this->assign($caseId, 'rescuer-pending');

        $this->assertFalse($response['body']['success']);
        $this->assertSame('INVALID_RESCUER', $response['body']['error']['code']);
    }

    public function testStatusTransitionsEndWithResolvedReportAndResidentNotification(): void
    {
        ['case_id' => $caseId] = $this->seedAssignedCase();

        $inProgress = $this->updateStatus($caseId, 'in_progress');
        $resolved = $this->updateStatus($caseId, 'resolved');

        $this->assertTrue($inProgress['body']['success']);
        $this->assertTrue($resolved['body']['success']);
        $this->assertSame('resolved', $this->caseRow($caseId)['status']);

        $report = $this->reportRow($this->caseRow($caseId)['report_id']);
        $this->assertSame('verified', $report['status']);

        $notified = (int) $this->countWhere(
            'notifications',
            'user_id = ? AND type = ?',
            ['resident-owner', 'case_resolved']
        );
        $this->assertSame(1, $notified);
    }

    public function testUpdateStatusRejectsInvalidStatusValue(): void
    {
        ['case_id' => $caseId] = $this->seedAssignedCase();

        $response = $this->updateStatus($caseId, 'closed');

        $this->assertFalse($response['body']['success']);
        $this->assertSame('VALIDATION_ERROR', $response['body']['error']['code']);
    }

    public function testRescuerCannotUpdateCaseAssignedToAnotherRescuer(): void
    {
        ['case_id' => $caseId] = $this->seedAssignedCase();
        $this->seedUser('rescuer-other', 'rescuer');

        $request = $this->makeRequest('PATCH', "/api/v1/cases/{$caseId}/status", [
            'status' => 'in_progress',
        ], [], [], ['id' => 'rescuer-other', 'role' => 'rescuer']);

        $response = $this->observe(fn () => $this->controller()->updateStatus($request));

        $this->assertFalse($response['body']['success']);
        $this->assertSame('FORBIDDEN', $response['body']['error']['code']);
    }

    public function testAdminCanUpdateAnyCaseStatus(): void
    {
        ['case_id' => $caseId] = $this->seedAssignedCase();

        $response = $this->updateStatus($caseId, 'in_progress', ['id' => 'admin-1', 'role' => 'admin']);

        $this->assertTrue($response['body']['success']);
        $this->assertSame('in_progress', $this->caseRow($caseId)['status']);
    }

    private function controller(): CaseController
    {
        return new CaseController($this->pdo);
    }

    private function assign(string $caseId, string $rescuerId): array
    {
        $request = $this->makeRequest('POST', "/api/v1/cases/{$caseId}/assign", [
            'rescuer_id' => $rescuerId,
        ], [], [], ['id' => 'admin-1', 'role' => 'admin']);

        return $this->observe(fn () => $this->controller()->assign($request));
    }

    private function updateStatus(string $caseId, string $status, ?array $user = null): array
    {
        $user ??= ['id' => 'rescuer-1', 'role' => 'rescuer'];
        $request = $this->makeRequest('PATCH', "/api/v1/cases/{$caseId}/status", [
            'status' => $status,
        ], [], [], $user);

        return $this->observe(fn () => $this->controller()->updateStatus($request));
    }

    private function seedUser(string $id, string $role, string $accountStatus = 'active'): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (id, full_name, email, role, account_status) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([$id, ucfirst($role) . ' ' . $id, $id . '@test.local', $role, $accountStatus]);
    }

    private function seedDuty(string $userId, string $status): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO rescuer_duty_status (id, user_id, status) VALUES (?, ?, ?)"
        );
        $stmt->execute([Database::uuidV4(), $userId, $status]);
    }

    private function seedReport(string $reportId, string $residentId): void
    {
        $repo = new ReportRepository($this->pdo);
        $repo->create([
            'id' => $reportId,
            'resident_id' => $residentId,
            'animal_description' => 'Injured dog',
            'latitude' => 6.9554,
            'longitude' => 126.2131,
            'content_hash' => DedupService::contentHash('Injured dog', 6.9554, 126.2131),
            'validation_status' => 'validated',
            'status' => 'pending_verification',
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    private function insertCase(string $caseId, string $reportId, string $status, ?string $assignedRescuerId = null): void
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO cases (id, report_id, assigned_rescuer_id, status) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$caseId, $reportId, $assignedRescuerId, $status]);
    }

    private function seedOpenCase(): string
    {
        $this->seedUser('admin-1', 'admin');
        $this->seedReport('report-for-open-case', 'resident-owner');
        $this->insertCase('case-open', 'report-for-open-case', 'open');
        return 'case-open';
    }

    private function seedAssignedCase(): array
    {
        $this->seedUser('admin-1', 'admin');
        $this->seedUser('rescuer-1', 'rescuer');
        $this->seedDuty('rescuer-1', 'on_duty');
        $this->seedReport('report-for-assigned-case', 'resident-owner');
        $this->insertCase('case-assigned', 'report-for-assigned-case', 'assigned', 'rescuer-1');
        return ['case_id' => 'case-assigned', 'report_id' => 'report-for-assigned-case'];
    }

    private function caseRow(string $caseId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM cases WHERE id = ?');
        $stmt->execute([$caseId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function reportRow(string $reportId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM reports WHERE id = ?');
        $stmt->execute([$reportId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    private function countWhere(string $table, string $where, array $params): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
}
