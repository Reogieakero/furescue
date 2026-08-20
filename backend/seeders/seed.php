<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Database;

$pdo = Database::connect();

$devPassword = 'Password123!';
$hash = password_hash($devPassword, PASSWORD_ARGON2ID);

function insert(\PDO $pdo, string $table, array $data): string
{
    $id = $data['id'] ?? Database::uuidV4();
    $data['id'] = $id;
    $cols = array_keys($data);
    $placeholders = array_map(static fn($c) => ":$c", $cols);
    $sql = "INSERT INTO {$table} (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $placeholders) . ")";
    $pdo->prepare($sql)->execute($data);
    return $id;
}

function userExists(\PDO $pdo, string $email): bool
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return (bool) $stmt->fetch();
}

function userId(\PDO $pdo, string $email): ?string
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null;
}

function ensureUser(\PDO $pdo, array $data): ?string
{
    if (userExists($pdo, $data['email'])) {
        return userId($pdo, $data['email']);
    }
    return insert($pdo, 'users', $data);
}

function rowExists(\PDO $pdo, string $table, string $where, array $params = []): bool
{
    $stmt = $pdo->prepare("SELECT 1 FROM {$table} WHERE {$where} LIMIT 1");
    $stmt->execute($params);
    return (bool) $stmt->fetch();
}

$adminEmail = 'admin@furescue.local';

$adminId = userId($pdo, $adminEmail);
if (!$adminId) {
    $adminId = insert($pdo, 'users', [
        'full_name'      => 'City Vet Admin',
        'email'          => $adminEmail,
        'password_hash'  => $hash,
        'auth_provider'  => 'native',
        'role'           => 'admin',
        'account_status' => 'active',
    ]);

    $rescuerId = insert($pdo, 'users', [
        'full_name'      => 'Rescuer One',
        'email'          => 'rescuer@furescue.local',
        'password_hash'  => $hash,
        'auth_provider'  => 'native',
        'role'           => 'rescuer',
        'account_status' => 'active',
        'phone_number'   => '09171234560',
    ]);
    insert($pdo, 'rescuer_approvals', [
        'user_id' => $rescuerId, 'reviewed_by' => $adminId, 'decision' => 'approved', 'reviewed_at' => date('Y-m-d H:i:s', strtotime('-30 days')),
    ]);
    insert($pdo, 'rescuer_duty_status', ['user_id' => $rescuerId, 'status' => 'on_duty']);

    foreach ([['Resident Juan', 'juan@furescue.local', '09171234561'], ['Resident Maria', 'maria@furescue.local', '09171234562']] as $r) {
        insert($pdo, 'users', [
            'full_name' => $r[0], 'email' => $r[1], 'password_hash' => $hash,
            'auth_provider' => 'native', 'role' => 'resident', 'account_status' => 'active', 'phone_number' => $r[2],
        ]);
    }

    $modules = [
        ['Dog Behavior Basics', 'dog_behavior', 'Understand common dog behaviors and body language to build trust with rescued dogs.', 'published'],
        ['Cat Care 101', 'cat_behavior', 'Essential care and behavior tips for rescued and adoptable cats.', 'published'],
    ];
    foreach ($modules as $m) {
        insert($pdo, 'elearning_modules', [
            'title' => $m[0], 'category' => $m[1], 'content_body' => $m[2], 'published_status' => $m[3], 'created_by' => $adminId,
        ]);
    }
    echo "Base accounts + starter modules seeded.\n";
} else {
    echo "Admin already exists — base accounts skipped.\n";
}

$extraModules = [
    ['Loose Leash Walking', 'basic_training', 'Step-by-step guide to calm loose-leash walking for shelter dogs.', 'published'],
    ['Enrichment for Shelter Dogs', 'general_care', 'Simple enrichment ideas that keep rescued dogs mentally stimulated.', 'published'],
    ['Kitten Socialization', 'cat_behavior', 'How to socialize rescued kittens for confident adoption.', 'published'],
    ['Emergency First Aid', 'general_care', 'Field checklist for stabilizing injured animals before transport.', 'draft'],
];
foreach ($extraModules as $m) {
    if (!rowExists($pdo, 'elearning_modules', 'title = ?', [$m[0]])) {
        insert($pdo, 'elearning_modules', [
            'title' => $m[0], 'category' => $m[1], 'content_body' => $m[2], 'published_status' => $m[3], 'created_by' => $adminId,
        ]);
    }
}

echo "Seeding demo datasets...\n";

$residents = [
    ['Ana Santos',   'ana@furescue.local',   '09171234563', 'Barangay Matiao, City of Mati'],
    ['Pedro Ramos',  'pedro@furescue.local', '09171234564', 'Barangay Bobon, City of Mati'],
    ['Rosa Dela Cruz', 'rosa@furescue.local', '09171234565', 'Barangay Dahican, City of Mati'],
    ['Miguel Torres','miguel@furescue.local','09171234566', 'Barangay San Isidro, City of Mati'],
];
$residentIds = [];
foreach ($residents as $r) {
    $residentIds[] = ensureUser($pdo, [
        'full_name' => $r[0], 'email' => $r[1], 'password_hash' => $hash,
        'auth_provider' => 'native', 'role' => 'resident', 'account_status' => 'active',
        'phone_number' => $r[2], 'address' => $r[3],
    ]);
}
$residentIds[] = userId($pdo, 'juan@furescue.local');
$residentIds[] = userId($pdo, 'maria@furescue.local');

$activeRescuers = [
    ['Rescuer Two',   'rescuer2@furescue.local', '09171234567'],
    ['Rescuer Three', 'rescuer3@furescue.local', '09171234568'],
    ['Rescuer Four',  'rescuer4@furescue.local', '09171234569'],
    ['Rescuer Five',  'rescuer5@furescue.local', '09171234570'],
];
$rescuerIds = [userId($pdo, 'rescuer@furescue.local')];
foreach ($activeRescuers as $r) {
    $id = ensureUser($pdo, [
        'full_name' => $r[0], 'email' => $r[1], 'password_hash' => $hash,
        'auth_provider' => 'native', 'role' => 'rescuer', 'account_status' => 'active', 'phone_number' => $r[2],
    ]);
    if (!rowExists($pdo, 'rescuer_approvals', 'user_id = ?', [$id])) {
        insert($pdo, 'rescuer_approvals', [
            'user_id' => $id, 'reviewed_by' => $adminId, 'decision' => 'approved', 'reviewed_at' => date('Y-m-d H:i:s', strtotime('-20 days')),
        ]);
    }
    if (!rowExists($pdo, 'rescuer_duty_status', 'user_id = ?', [$id])) {
        insert($pdo, 'rescuer_duty_status', ['user_id' => $id, 'status' => 'on_duty']);
    }
    $rescuerIds[] = $id;
}

$applicants = [
    ['Rescuer Six',   'rescuer6@furescue.local', '09171234571'],
    ['Rescuer Seven', 'rescuer7@furescue.local', '09171234572'],
];
foreach ($applicants as $r) {
    ensureUser($pdo, [
        'full_name' => $r[0], 'email' => $r[1], 'password_hash' => $hash,
        'auth_provider' => 'native', 'role' => 'rescuer', 'account_status' => 'pending', 'phone_number' => $r[2],
    ]);
}

$spots = [
    ['Poblacion',     6.9510, 126.1990],
    ['Dahican',       6.9412, 126.2300],
    ['Bobon',         6.9180, 126.2310],
    ['Taguibo',       7.0025, 126.2390],
    ['Mayo',          6.9610, 126.1650],
    ['Matiao',        6.9060, 126.2070],
    ['San Rafael',    6.9760, 126.2130],
    ['Tamisan',       6.9240, 126.2520],
    ['Mamali',        6.8970, 126.1770],
    ['Don Salvador',  6.9730, 126.1500],
    ['Libuac',        6.9340, 126.2460],
    ['San Isidro',    6.9860, 126.2250],
];

$animals = [
    ['Buddy',  'dog', 'aspin', 'male',   '2 years', 'brown with white chest', 'rescued_case'],
    ['Luna',   'cat', 'puspin', 'female', '1 year',  'solid black',            'rescued_case'],
    ['Max',    'dog', 'aspin', 'male',   '3 years', 'fawn',                    'rescued_case'],
    ['Bella',  'dog', 'aspin', 'female', '2 years', 'white and tan',           'rescued_case'],
    ['Coco',   'cat', 'puspin', 'male',   '6 months', 'orange tabby',          'rescued_case'],
    ['Mimi',   'cat', 'puspin', 'female', '2 years', 'gray and white',         'rescued_case'],
    ['Bruno',  'dog', 'aspin', 'male',   '4 years', 'black',                   'rescued_case'],
    ['Ginger', 'dog', 'aspin', 'female', '1 year',  'red-brown',               'rescued_case'],
];

$caseStatuses = ['resolved', 'in_progress', 'assigned', 'resolved', 'in_progress', 'resolved', 'assigned', 'in_progress'];
$healthFlags = ['healthy', 'not_healthy', 'healthy', 'healthy', 'not_healthy', 'healthy', 'not_healthy', 'healthy'];

$verifiedCount = 8;
$animalByIdx = [];
$caseByIdx = [];

for ($i = 0; $i < count($spots); $i++) {
    [$brgy, $lat, $lng] = $spots[$i];
    $resident = $residentIds[$i % count($residentIds)];
    $verified = $i < $verifiedCount;
    $desc = ($i % 2 === 0)
        ? "Stray dog with an injured leg spotted near the {$brgy} market. Needs rescue and veterinary attention."
        : "Cat showing eye discharge and lethargy seen along the {$brgy} shoreline. Possibly needs treatment.";
    $contentHash = substr(hash('sha256', $brgy . $desc), 0, 64);

    if (rowExists($pdo, 'reports', 'content_hash = ?', [$contentHash])) {
        $pdo->prepare('UPDATE reports SET photo_urls = COALESCE(photo_urls, ?) WHERE content_hash = ?')
            ->execute([json_encode(["/uploads/demo/report-{$i}.svg"]), $contentHash]);
        continue;
    }

    $reportId = insert($pdo, 'reports', [
        'resident_id'         => $resident,
        'animal_description'  => $desc,
        'photo_urls'          => json_encode(["/uploads/demo/report-{$i}.svg"]),
        'latitude'            => $lat,
        'longitude'           => $lng,
        'address_text'        => "{$brgy}, City of Mati",
        'content_hash'        => $contentHash,
        'validation_status'   => $verified ? 'validated' : 'pending',
        'status'              => $verified ? 'verified' : 'pending_verification',
        'verified_by'         => $verified ? $adminId : null,
        'verified_at'         => $verified ? date('Y-m-d H:i:s', strtotime('-3 days')) : null,
    ]);

    if (!$verified) {
        continue;
    }

    $a = $animals[$i % count($animals)];
    $animalId = insert($pdo, 'animals', [
        'name'            => $a[0],
        'species'         => $a[1],
        'breed_type'      => $a[2],
        'sex'             => $a[3],
        'age_estimate'    => $a[4],
        'color_markings'  => $a[5],
        'description'     => $desc,
        'adoption_status' => 'available',
        'source'          => $a[6],
        'created_by'      => $adminId,
    ]);
    $animalByIdx[$i] = $animalId;

    $rescuer = $rescuerIds[$i % count($rescuerIds)];
    $caseId = insert($pdo, 'cases', [
        'report_id'           => $reportId,
        'assigned_rescuer_id' => $rescuer,
        'assigned_by'         => $adminId,
        'status'              => $caseStatuses[$i % count($caseStatuses)],
        'resolution_notes'    => $caseStatuses[$i % count($caseStatuses)] === 'resolved' ? 'Animal brought to care facility; healing well.' : null,
    ]);
    $caseByIdx[$i] = $caseId;

    insert($pdo, 'animal_field_status', [
        'animal_id'     => $animalId,
        'case_id'       => $caseId,
        'rescue_status' => 'rescued',
        'health_status' => $healthFlags[$i % count($healthFlags)],
        'logged_by'     => $rescuer,
        'logged_at'     => date('Y-m-d H:i:s', strtotime("-{$i} hours")),
    ]);
}

foreach ([0, 1, 3, 4, 6] as $mi) {
    if (!isset($animalByIdx[$mi])) {
        continue;
    }
    $animalId = $animalByIdx[$mi];
    if (rowExists($pdo, 'animal_medical_records', 'animal_id = ?', [$animalId])) {
        continue;
    }
    insert($pdo, 'animal_medical_records', [
        'animal_id'             => $animalId,
        'medical_history_notes' => 'Initial assessment on intake; monitored daily.',
        'vaccination_status'    => $mi % 2 === 0 ? 'partial' : 'complete',
        'last_checkup_date'     => date('Y-m-d', strtotime('-7 days')),
        'updated_by'            => $adminId,
    ]);
}

$listingAnimals = [0, 1, 2, 3, 4, 5, 6];
foreach ($listingAnimals as $li) {
    if (!isset($animalByIdx[$li])) {
        continue;
    }
    $animalId = $animalByIdx[$li];
    if (rowExists($pdo, 'adoption_listings', 'animal_id = ?', [$animalId])) {
        continue;
    }
    insert($pdo, 'adoption_listings', [
        'animal_id'   => $animalId,
        'posted_by'   => $adminId,
        'status'      => 'approved',
        'reviewed_by' => $adminId,
        'review_notes' => 'Verified and approved for listing.',
        'reviewed_at' => date('Y-m-d H:i:s', strtotime('-2 days')),
    ]);
}

$adoptionRows = [
        [0, 'juan@furescue.local',   'completed', 1],
    [1, 'maria@furescue.local',  'completed', 4],
    [3, 'ana@furescue.local',    'approved',  null],
    [5, 'rosa@furescue.local',   'approved',  null],
    [2, 'pedro@furescue.local',  'pending',   null],
    [6, 'miguel@furescue.local', 'pending',   null],
];
foreach ($adoptionRows as $row) {
    [$ai, $applicantEmail, $status, $offset] = $row;
    if (!isset($animalByIdx[$ai])) {
        continue;
    }
    $animalId = $animalByIdx[$ai];
    $applicant = userId($pdo, $applicantEmail);
    if (rowExists($pdo, 'adoptions', 'animal_id = ? AND applicant_id = ? AND status = ?', [$animalId, $applicant, $status])) {
        continue;
    }
    $completedAt = $offset !== null ? date('Y-m-d H:i:s', strtotime("-{$offset} days")) : null;
    insert($pdo, 'adoptions', [
        'animal_id'    => $animalId,
        'applicant_id' => $applicant,
        'status'       => $status,
        'reviewed_by'  => $status === 'pending' ? null : $adminId,
        'reviewed_at'  => $status === 'pending' ? null : date('Y-m-d H:i:s', strtotime('-1 day')),
        'completed_at' => $completedAt,
    ]);
    if ($status === 'completed') {
        $pdo->prepare("UPDATE animals SET adoption_status = 'adopted' WHERE id = ?")->execute([$animalId]);
    } elseif ($status === 'pending') {
        $pdo->prepare("UPDATE animals SET adoption_status = 'pending' WHERE id = ?")->execute([$animalId]);
    }
}

$notifications = [
    'New report flagged in Poblacion — awaiting verification.',
    'Rescuer application from Rescuer Six needs your review.',
    'New adoption application submitted for Buddy.',
    'Health update logged for Bella by Rescuer Two.',
    'Adoption application approved for Luna.',
    'Case resolved — Ginger successfully rescued and stabilized.',
];
foreach ($notifications as $n) {
    if (rowExists($pdo, 'notifications', 'user_id = ? AND message = ?', [$adminId, $n])) {
        continue;
    }
    insert($pdo, 'notifications', [
        'user_id' => $adminId,
        'type'    => 'admin',
        'message' => $n,
        'is_read' => 0,
    ]);
}

$count = static fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

echo "Seeded successfully.\n";
echo "  admin:    admin@furescue.local / {$devPassword}\n";
echo "  rescuers: rescuer@furescue.local .. rescuer7@furescue.local / {$devPassword}\n";
echo "  residents: juan@, maria@, ana@, pedro@, rosa@, miguel@ / {$devPassword}\n";
echo "  reports: " . $count("SELECT COUNT(*) FROM reports")
    . " (verified " . $count("SELECT COUNT(*) FROM reports WHERE status = 'verified'")
    . ", pending_verification " . $count("SELECT COUNT(*) FROM reports WHERE status = 'pending_verification'") . ")\n";
echo "  animals: " . $count("SELECT COUNT(*) FROM animals")
    . ", cases: " . $count("SELECT COUNT(*) FROM cases")
    . ", adoptions: " . $count("SELECT COUNT(*) FROM adoptions") . "\n";
echo "  e-learning modules: " . $count("SELECT COUNT(*) FROM elearning_modules") . "\n";
echo "  notifications: " . $count("SELECT COUNT(*) FROM notifications") . "\n";
