<?php

declare(strict_types=1);

function dash_esc(mixed $v): string
{
    return htmlspecialchars((string) ($v ?? ''), ENT_QUOTES, 'UTF-8');
}

function dash_classify_report_type(mixed $description): string
{
    $t = strtolower((string) $description);
    if (preg_match('/\b(abuse|neglect|beaten|starved|cruel)\b/', $t)) {
        return 'abuse';
    }
    if (preg_match('/\b(injur|wound|hurt|bleed|sick|hit.?and.?run|broken)\b/', $t)) {
        return 'injured';
    }
    if (preg_match('/\b(stray|roaming|loose|abandoned|wandering)\b/', $t)) {
        return 'stray';
    }
    return 'other';
}

function dash_report_type_label(mixed $description): string
{
    $kind = dash_classify_report_type($description);
    $t = (string) $description;
    $species = preg_match('/\bcat\b/i', $t) ? 'Cat' : (preg_match('/\bdog\b/i', $t) ? 'Dog' : 'Animal');
    return match ($kind) {
        'stray' => "Stray {$species}",
        'injured' => "Injured {$species}",
        'abuse' => 'Abuse/Neglect',
        default => $species === 'Animal' ? 'Others' : $species,
    };
}

function dash_display_status(array $report): string
{
    $cs = (string) ($report['case_status'] ?? '');
    if ($cs === 'resolved') {
        return 'resolved';
    }
    if ($cs === 'in_progress') {
        return 'in_progress';
    }
    $st = (string) ($report['status'] ?? '');
    if ($st === 'verified') {
        return 'verified';
    }
    if ($st === 'dismissed') {
        return 'dismissed';
    }
    return 'pending';
}

function dash_status_meta(string $key): array
{
    return match ($key) {
        'verified' => ['label' => 'Verified', 'cls' => 'dash-pill dash-pill--verified'],
        'in_progress' => ['label' => 'In Progress', 'cls' => 'dash-pill dash-pill--progress'],
        'resolved' => ['label' => 'Resolved', 'cls' => 'dash-pill dash-pill--resolved'],
        'dismissed' => ['label' => 'Dismissed', 'cls' => 'dash-pill dash-pill--muted'],
        default => ['label' => 'Pending', 'cls' => 'dash-pill dash-pill--pending'],
    };
}

function dash_format_report_id(mixed $id, mixed $createdAt = null): string
{
    $year = $createdAt ? (int) date('Y', strtotime((string) $createdAt) ?: time()) : (int) date('Y');
    $s = strtoupper(substr(str_replace('-', '', (string) $id), -6));
    return '#RPT-' . $year . '-' . str_pad($s, 6, '0', STR_PAD_LEFT);
}

function dash_format_datetime(mixed $value): string
{
    if (!$value) {
        return '—';
    }
    $ts = strtotime((string) $value);
    if ($ts === false) {
        return '—';
    }
    return date('M j, Y g:i A', $ts);
}

function dash_days_until(?string $value): ?int
{
    if ($value === null || $value === '') {
        return null;
    }
    $parsed = strtotime($value);
    if ($parsed === false) {
        return null;
    }
    $ts = mktime(0, 0, 0, (int) date('n', $parsed), (int) date('j', $parsed), (int) date('Y', $parsed));
    $today = mktime(0, 0, 0);
    return (int) round(($ts - $today) / 86400);
}

function dash_first_photo(mixed $urls): string
{
    if (is_array($urls) && $urls) {
        return (string) $urls[0];
    }
    if (is_string($urls) && $urls !== '') {
        $dec = json_decode($urls, true);
        if (is_array($dec) && $dec) {
            return (string) $dec[0];
        }
        if (str_starts_with($urls, '/') || str_starts_with($urls, 'http')) {
            return $urls;
        }
    }
    return '';
}

function dash_density_summary(array $points): array
{
    $cells = [];
    foreach ($points as $p) {
        if (!isset($p['latitude'], $p['longitude'])) {
            continue;
        }
        $key = number_format((float) $p['latitude'], 2, '.', '') . ',' . number_format((float) $p['longitude'], 2, '.', '');
        $cells[$key] = ($cells[$key] ?? 0) + 1;
    }
    $high = 0;
    $moderate = 0;
    $low = 0;
    foreach ($cells as $n) {
        if ($n >= 5) {
            $high++;
        } elseif ($n >= 2) {
            $moderate++;
        } else {
            $low++;
        }
    }
    return ['high' => $high, 'moderate' => $moderate, 'low' => $low];
}

function dash_category_breakdown(array $reports): array
{
    $counts = ['stray' => 0, 'injured' => 0, 'abuse' => 0, 'other' => 0];
    foreach ($reports as $r) {
        $key = dash_classify_report_type($r['animal_description'] ?? '');
        $counts[$key] = ($counts[$key] ?? 0) + 1;
    }
    $total = max(1, array_sum($counts));
    $labels = [
        'stray' => 'Stray Animal',
        'injured' => 'Injured Animal',
        'abuse' => 'Abuse/Neglect',
        'other' => 'Others',
    ];
    $out = [];
    foreach ($labels as $key => $label) {
        $out[] = [
            'key' => $key,
            'label' => $label,
            'count' => $counts[$key],
            'pct' => (int) round(($counts[$key] / $total) * 100),
        ];
    }
    return $out;
}

function dash_health_overview(array $records): array
{
    $healthy = 0;
    $attention = 0;
    $treatment = 0;
    $recovered = 0;
    $upToDate = 0;
    $dueSoon = 0;
    $overdue = 0;
    $none = 0;
    $reminders = [];
    $checkups = [];

    foreach ($records as $r) {
        $stage = (string) ($r['treatmentStage'] ?? $r['treatment_stage'] ?? 'none');
        $health = (string) ($r['healthStatus'] ?? $r['health_status'] ?? 'healthy');
        if ($stage === 'ongoing') {
            $treatment++;
            $healthLabel = 'Under Treatment';
            $healthKey = 'treatment';
        } elseif ($stage === 'completed') {
            $recovered++;
            $healthLabel = 'Recovered';
            $healthKey = 'recovered';
        } elseif ($health === 'not_healthy') {
            $attention++;
            $healthLabel = 'Needs Attention';
            $healthKey = 'attention';
        } else {
            $healthy++;
            $healthLabel = 'Healthy';
            $healthKey = 'healthy';
        }

        $vax = (string) ($r['vaccinationStatus'] ?? $r['vaccination_status'] ?? 'none');
        $vaxDays = dash_days_until($r['vaccinationExpiry'] ?? $r['vaccination_expiry'] ?? null);
        if ($vax === 'none' || $vax === '') {
            $none++;
        } elseif ($vaxDays !== null && $vaxDays < 0) {
            $overdue++;
        } elseif ($vaxDays !== null && $vaxDays <= 14) {
            $dueSoon++;
        } elseif ($vax === 'complete') {
            $upToDate++;
        } else {
            $dueSoon++;
        }

        $due = $r['nextCheckupDue'] ?? $r['next_checkup_due'] ?? null;
        $dueDays = dash_days_until(is_string($due) ? $due : null);
        if ($dueDays !== null && $dueDays >= 0 && $dueDays <= 21) {
            $reminders[] = [
                'label' => 'Check-up',
                'detail' => 'Due in ' . $dueDays . ' day' . ($dueDays === 1 ? '' : 's'),
                'days' => $dueDays,
            ];
        }
        if ($vaxDays !== null && $vaxDays >= 0 && $vaxDays <= 21) {
            $reminders[] = [
                'label' => 'Vaccine booster',
                'detail' => 'Due in ' . $vaxDays . ' day' . ($vaxDays === 1 ? '' : 's'),
                'days' => $vaxDays,
            ];
        }

        $last = $r['lastCheckupDate'] ?? $r['last_checkup_date'] ?? null;
        if ($last) {
            $photo = dash_first_photo($r['photo_urls'] ?? $r['photoUrls'] ?? null);
            $checkups[] = [
                'name' => ($r['animalName'] ?? $r['name'] ?? null) ?: 'Unnamed',
                'meta' => trim(($r['species'] ?? '') . ' · ' . dash_format_datetime((string) $last)),
                'photo' => $photo,
                'status' => $healthLabel,
                'statusKey' => $healthKey,
                'animalId' => (string) ($r['animalId'] ?? $r['id'] ?? ''),
                'sort' => strtotime((string) $last) ?: 0,
            ];
        }
    }

    usort($reminders, static fn($a, $b) => $a['days'] <=> $b['days']);
    $grouped = [];
    foreach ($reminders as $item) {
        $key = $item['label'] . '|' . $item['days'];
        if (!isset($grouped[$key])) {
            $grouped[$key] = $item + ['count' => 0];
        }
        $grouped[$key]['count']++;
    }
    $reminders = array_slice(array_values($grouped), 0, 3);

    usort($checkups, static fn($a, $b) => $b['sort'] <=> $a['sort']);
    $checkups = array_slice($checkups, 0, 4);

    $total = max(1, count($records));
    $vax = [
        ['key' => 'up', 'label' => 'Up to date', 'count' => $upToDate, 'pct' => (int) round(($upToDate / $total) * 100)],
        ['key' => 'soon', 'label' => 'Due Soon', 'count' => $dueSoon, 'pct' => (int) round(($dueSoon / $total) * 100)],
        ['key' => 'over', 'label' => 'Overdue', 'count' => $overdue, 'pct' => (int) round(($overdue / $total) * 100)],
        ['key' => 'none', 'label' => 'Not Yet Vaccinated', 'count' => $none, 'pct' => (int) round(($none / $total) * 100)],
    ];

    return [
        'summary' => [
            ['key' => 'healthy', 'label' => 'Healthy', 'count' => $healthy, 'icon' => 'heart'],
            ['key' => 'attention', 'label' => 'Needs Attention', 'count' => $attention, 'icon' => 'alert-triangle'],
            ['key' => 'treatment', 'label' => 'Under Treatment', 'count' => $treatment, 'icon' => 'syringe'],
            ['key' => 'recovered', 'label' => 'Recovered', 'count' => $recovered, 'icon' => 'badge-check'],
        ],
        'totalAnimals' => count($records),
        'vax' => $vax,
        'reminders' => $reminders,
        'checkups' => $checkups,
    ];
}

function dash_report_trend(PDO $pdo): array
{
    $stmt = $pdo->query(
        "SELECT DATE_FORMAT(created_at, '%Y-%m') AS ym, COUNT(*) AS c
         FROM reports
         WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 5 MONTH), '%Y-%m-01')
         GROUP BY ym
         ORDER BY ym"
    );
    $map = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $map[(string) $row['ym']] = (int) $row['c'];
    }
    $out = [];
    for ($i = 5; $i >= 0; $i--) {
        $dt = new DateTimeImmutable("first day of -{$i} months");
        $ym = $dt->format('Y-m');
        $out[] = ['month' => $dt->format('M'), 'count' => $map[$ym] ?? 0];
    }
    return $out;
}

function dash_status_pill(string $key): string
{
    $meta = dash_status_meta($key);
    return '<span class="' . dash_esc($meta['cls']) . '">' . dash_esc($meta['label']) . '</span>';
}

function dash_trend_label(int $n): array
{
    if ($n === 0) {
        return ['text' => 'No change today', 'tone' => 'neutral'];
    }
    if ($n > 0) {
        return ['text' => '+' . $n . ' Today', 'tone' => 'up'];
    }
    return ['text' => $n . ' Today', 'tone' => 'down'];
}

function dash_health_pill(string $key, string $label): string
{
    $cls = match ($key) {
        'attention' => 'dash-pill dash-pill--pending',
        'treatment' => 'dash-pill dash-pill--care',
        'recovered' => 'dash-pill dash-pill--progress',
        default => 'dash-pill dash-pill--resolved',
    };
    return '<span class="' . $cls . '">' . dash_esc($label) . '</span>';
}
