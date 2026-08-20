<?php

namespace App\Controllers;

use App\Http\Request;
use App\Http\Response;

class AnalyticsController extends AbstractController
{
    public function overview(Request $req): void
    {
        $stats = [
            'reports' => $this->count('reports'),
            'reports_verified' => $this->countWhere("reports", "status = 'verified'"),
            'cases' => $this->count('cases'),
            'cases_resolved' => $this->countWhere("cases", "status = 'resolved'"),
            'animals' => $this->count('animals'),
            'animals_adopted' => $this->countWhere("animals", "adoption_status = 'adopted'"),
            'adoptions_pending' => $this->countWhere("adoptions", "status = 'pending'"),
            'adoptions_completed' => $this->countWhere("adoptions", "status = 'completed'"),
            'rescuers_on_duty' => $this->countWhere(
                "rescuer_duty_status d JOIN users u ON u.id = d.user_id",
                "d.status = 'on_duty' AND u.account_status = 'active' AND u.role = 'rescuer'"
            ),
            'residents' => $this->countWhere("users", "role = 'resident'"),
        ];
        Response::success(['stats' => $stats]);
    }

    public function adoptionTrends(Request $req): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT DATE(completed_at) AS day, COUNT(*) AS completed
             FROM adoptions WHERE status = 'completed' AND completed_at IS NOT NULL
             GROUP BY DATE(completed_at) ORDER BY day DESC LIMIT 30"
        );
        $stmt->execute();
        Response::success(['trends' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    public function healthUpdates(Request $req): void
    {
        $stmt = $this->pdo->prepare(
            "SELECT fs.id, fs.animal_id, fs.rescue_status, fs.health_status, fs.logged_at,
                    a.name AS animal_name, a.species, a.breed_type,
                    u.full_name AS logged_by_name
             FROM animal_field_status fs
             JOIN animals a ON a.id = fs.animal_id
             LEFT JOIN users u ON u.id = fs.logged_by
             ORDER BY fs.logged_at DESC
             LIMIT 50"
        );
        $stmt->execute();
        Response::success(['updates' => $stmt->fetchAll(\PDO::FETCH_ASSOC)]);
    }

    private function count(string $table): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }

    private function countWhere(string $table, string $where): int
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM {$table} WHERE {$where}");
        $stmt->execute();
        return (int) $stmt->fetchColumn();
    }
}
