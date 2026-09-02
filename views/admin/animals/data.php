<?php

declare(strict_types=1);

use App\Database;

require __DIR__ . '/../../../vendor/autoload.php';

Dotenv\Dotenv::createImmutable(dirname(__DIR__, 3))->safeLoad();

$requiredRole = 'admin';
require_once dirname(__DIR__, 3) . '/views/path.php';
require views_path('components/guard.php');

require views_path('components/admin-ui-helpers.php');

$pdo = Database::connect();
$uid = (string) $_SESSION['user']['id'];

// Mirrors AnimalController::index (GET /api/v1/animals?per_page=100 → page 1)
// serialization exactly: raw rows, created_at DESC, deleted excluded.
$stmt = $pdo->prepare(
    "SELECT id,name,species,breed_type,sex,age_estimate,birth_date,color_markings,photo_urls,model_3d_url,photo_360_set,adoption_status,source,created_at
     FROM animals WHERE deleted_at IS NULL ORDER BY created_at DESC LIMIT 100 OFFSET 0"
);
$stmt->execute();
$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

// Mirrors HealthController::records → fetchMedicalAnimalIds():
// set of animalIds having any row in animal_medical_records.
$medStmt = $pdo->prepare('SELECT DISTINCT animal_id FROM animal_medical_records');
$medStmt->execute();
$medSet = array_flip($medStmt->fetchAll(\PDO::FETCH_COLUMN));

const ANIMAL_STATUS_LABELS = [
    'not_listed' => 'Not listed',
    'available' => 'Available',
    'pending' => 'Pending',
    'adopted' => 'Adopted',
];

const ANIMAL_STATUS_TONES = [
    'Available' => 'stamp--accent',
    'Pending' => 'stamp--muted',
    'Adopted' => 'stamp--jungle',
    'Not listed' => 'stamp--muted',
];

$firstPhoto = static function (mixed $urls): ?string {
    if (is_array($urls)) {
        return $urls !== [] ? (string) $urls[0] : null;
    }
    if (is_string($urls) && $urls !== '') {
        $dec = json_decode($urls, true);
        if (is_array($dec) && $dec !== []) {
            return (string) $dec[0];
        }
        return null;
    }
    return null;
};

$normalize = static function (array $r) use ($firstPhoto, $medSet): array {
    // Mirrors state.js normalize() 1:1.
    $speciesLabel = strtolower(((string) ($r['species'] ?? '')) ?: 'dog') === 'cat' ? 'Cat' : 'Dog';
    $breedRaw = strtolower((string) ($r['breed_type'] ?? ''));
    $breedLabel = $breedRaw !== ''
        ? mb_strtoupper(mb_substr($breedRaw, 0, 1, 'UTF-8'), 'UTF-8') . mb_substr($breedRaw, 1, null, 'UTF-8')
        : '—';
    $sexLabel = ($r['sex'] ?? null) === 'female' ? 'F' : (($r['sex'] ?? null) === 'male' ? 'M' : '—');
    $statusLabel = ANIMAL_STATUS_LABELS[$r['adoption_status']] ?? 'Not listed';
    $location = !empty($r['color_markings'])
        ? (string) $r['color_markings']
        : (($r['source'] ?? null) === 'resident_listing'
            ? 'Resident listing'
            : (!empty($r['source']) ? 'Rescued case' : '—'));
    return [
        'id' => (string) $r['id'],
        'name' => ((string) ($r['name'] ?? '')) ?: 'Unnamed',
        'species' => $speciesLabel,
        'breed' => $breedLabel,
        'age' => ((string) ($r['age_estimate'] ?? '')) ?: '—',
        'sex' => $sexLabel,
        'status' => $statusLabel,
        'barangay' => $location,
        'intake' => substr((string) ($r['created_at'] ?? ''), 0, 10),
        'photo' => $firstPhoto($r['photo_urls'] ?? null),
        'hasMedical' => isset($medSet[(string) $r['id']]),
    ];
};

$animals = array_map($normalize, $rows);

$statusCount = static function (string $s) use ($animals): int {
    return count(array_filter($animals, static fn(array $a) => $a['status'] === $s));
};
$counts = [
    'all' => count($animals),
    'Available' => $statusCount('Available'),
    'Pending' => $statusCount('Pending'),
    'Adopted' => $statusCount('Adopted'),
    'Not listed' => $statusCount('Not listed'),
];

$noMedical = count(array_filter($animals, static fn(array $a) => empty($a['hasMedical'])));
$animalKpiData = [
    ['icon' => 'paw-print', 'value' => $counts['all'], 'label' => 'Total', 'tone' => 'jungle', 'filter' => 'all', 'trend' => null, 'desc' => 'Every animal currently in the shelter system.'],
    ['icon' => 'check-circle-2', 'value' => $counts['Available'], 'label' => 'Available', 'tone' => 'sky', 'filter' => 'Available', 'trend' => null, 'desc' => 'Animals listed and ready for adoption.'],
    ['icon' => 'hourglass', 'value' => $counts['Pending'], 'label' => 'Pending', 'tone' => 'amber', 'filter' => 'Pending', 'trend' => null, 'desc' => 'In-care animals on hold pending adoption or review.'],
    ['icon' => 'heart', 'value' => $counts['Adopted'], 'label' => 'Adopted', 'tone' => 'ink', 'filter' => 'Adopted', 'trend' => null, 'desc' => 'Animals that have already been adopted.'],
    ['icon' => 'alert-triangle', 'value' => $noMedical, 'label' => 'No medical records', 'tone' => 'coral', 'filter' => null, 'trend' => $noMedical ? ['text' => 'Needs records', 'tone' => 'down'] : null, 'desc' => 'Animals with no medical file on record.'],
];

$animalFilters = [
    ['key' => 'all', 'label' => 'All'],
    ['key' => 'Available', 'label' => 'Available'],
    ['key' => 'Pending', 'label' => 'Pending'],
    ['key' => 'Adopted', 'label' => 'Adopted'],
    ['key' => 'Not listed', 'label' => 'Not listed'],
];
