<?php

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

use App\Database;

$pdo = Database::connect();
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$devPassword = 'Password123!';
$hash = password_hash($devPassword, PASSWORD_ARGON2ID);

function insert(\PDO $pdo, string $table, array $data): string
{
    $id = $data['id'] ?? Database::uuidV4();
    $data['id'] = $id;
    $cols = array_keys($data);
    $colSql = implode(', ', array_map(static fn($c) => "`$c`", $cols));
    $placeholders = array_map(static fn($c) => ":$c", $cols);
    $sql = "INSERT INTO {$table} (" . $colSql . ") VALUES (" . implode(', ', $placeholders) . ")";
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

function randItem(array $arr)
{
    return $arr[array_rand($arr)];
}

function randInt(int $min, int $max): int
{
    return random_int($min, $max);
}

function randFloat(float $min, float $max, int $decimals = 1): float
{
    return round($min + mt_rand() / mt_getrandmax() * ($max - $min), $decimals);
}

function timeAgo(int $seconds): string
{
    return date('Y-m-d H:i:s', time() - $seconds);
}

function pickN(array $arr, int $n): array
{
    if ($n >= count($arr)) {
        return $arr;
    }
    shuffle($arr);
    return array_slice($arr, 0, $n);
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
        'user_id' => $rescuerId, 'reviewed_by' => $adminId, 'decision' => 'approved', 'reviewed_at' => timeAgo(2592000),
    ]);
    insert($pdo, 'rescuer_duty_status', ['user_id' => $rescuerId, 'status' => 'on_duty']);

    foreach ([['Resident Juan', 'juan@furescue.local', '09171234561', 'Barangay Poblacion, City of Mati'], ['Resident Maria', 'maria@furescue.local', '09171234562', 'Barangay Dahican, City of Mati']] as $r) {
        insert($pdo, 'users', [
            'full_name' => $r[0], 'email' => $r[1], 'password_hash' => $hash,
            'auth_provider' => 'native', 'role' => 'resident', 'account_status' => 'active', 'phone_number' => $r[2], 'address' => $r[3],
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
    ['Senior Dog Care', 'general_care', 'Special considerations for aging dogs in rescue and foster homes.', 'published'],
    ['Feral Cat TNR Guide', 'cat_behavior', 'Best practices for trap-neuter-return programs in community settings.', 'published'],
    ['Puppy Socialization', 'dog_behavior', 'Critical windows and safe exposure exercises for puppies under 16 weeks.', 'published'],
    ['Separation Anxiety', 'basic_training', 'Evidence-based protocols for reducing anxiety in newly adopted dogs.', 'draft'],
];
foreach ($extraModules as $m) {
    if (!rowExists($pdo, 'elearning_modules', 'title = ?', [$m[0]])) {
        insert($pdo, 'elearning_modules', [
            'title' => $m[0], 'category' => $m[1], 'content_body' => $m[2], 'published_status' => $m[3], 'created_by' => $adminId,
        ]);
    }
}

echo "Seeding large procedural datasets...\n";

$firstNames = ['Ana','Pedro','Rosa','Miguel','Juan','Maria','Liza','Carlos','Elena','Diego','Sofia','Marco','Isabella','Luis','Camila','Gabriel','Valentina','Andres','Natalia','Jose','Lucia','Fernando','Paola','Ricardo','Angela','Roberto','Daniela','Oscar','Adriana','Manuel','Patricia','Jorge','Clara','Alberto','Monica','Raul','Teresa','Arturo','Gloria','Hector','Silvia','Eduardo','Rebecca','Francisco','Vanessa','Ramon','Irene','Salvador','Emma','Pablo'];
$lastNames = ['Santos','Ramos','Dela Cruz','Torres','Garcia','Mendoza','Villanueva','Rivera','Castillo','Aquino','Cruz','Sanchez','Perez','Reyes','Gonzales','Bautista','Hernandez','Dizon','Magbanua','Lopez','Flores','Navarro','Vargas','Soriano','Medina','Espinoza','Coronel','Pascual','Salvador','Mercado','Manalo','Tadeo','Calderon','Dagohoy','Ferrer','Abella','Ong','Sim','Chua','Dy','Tiu','Ng','Wee','Lee','Tan','Go','Yap','Sy','Araneta','Enriquez'];
$barangays = [
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
    ['Badas',         6.9120, 126.2180],
    ['Tagabawas',     6.9480, 126.1880],
    ['Central',       6.9550, 126.2020],
];

$dogBreeds = ['aspin'];
$catBreeds = ['puspin'];
$dogColors = ['brown and white','black','fawn','white and tan','orange tabby','gray and white','red-brown','brindle','cream','dark brindle','golden','merle','spotted white','tricolor','solid black','chocolate','rust and white','blue merle'];
$catColors = ['solid black','gray and white','orange tabby','tuxedo','calico','seal point','flame point','tortoiseshell','solid white','smoke','lynx point','dilute calico'];
$dogNames = ['Buddy','Luna','Max','Bella','Coco','Mimi','Bruno','Ginger','Rocky','Daisy','Shadow','Mochi','Bentley','Cleo','Oscar','Nala','Simba','Pumpkin','Cooper','Willow','Rex','Lola','Toby','Mila','Zeus','Duke','Zara','Koda','Mochi','Ace','Ruby','Oreo','Honey','Bear','Storm','Maple','Peanut','Gizmo','Trixie','Baxter','Nala','Rocco','Lady','Marley','Sasha','Diesel','Nina','Tiger','Coco','Jade'];
$catNames = ['Luna','Mimi','Coco','Shadow','Cleo','Mochi','Nala','Simba','Pumpkin','Willow','Mila','Zara','Ivy','Pepper','Oreo','Gizmo','Trixie','Sushi','Mochi','Luna','Tiger','Jade','Nina','Cali','Lola','Milo','Leo','Kitty','Felix','Oscar','Cleo','Simba','Nala','Luna','Shadow','Mimi','Coco','Willow','Mila','Pumpkin','Ginger','Zara','Ivy','Pepper','Sushi','Trixie','Lola','Milo'];
$rescuerFirst = ['Rescuer','Field','Search','Rescue','Rover','Patrol','Swift','Brave','Guardian','Hope','Relief','Storm','Eagle','Hawk','Pilot','Nova','Atlas','Blaze','Comet','Drift','Echo','Flint','Forge','Glider','Horizon','Jade','Kestrel','Lumen','Mercury','Nomad','Orbit','Phoenix','Quest','Ranger','Summit','Trace','Vector','Zenith'];
$rescuerLast = ['Alpha','Bravo','Charlie','Delta','Echo','Foxtrot','Golf','Hotel','India','Juliet','Kilo','Lima','Mike','November','Oscar','Papa','Quebec','Romeo','Sierra','Tango','Uniform','Victor','Whiskey','Xray','Yankee','Zulu'];
$rescuerPhones = ['09171234567','09171234568','09171234569','09171234570','09171234571','09171234572','09171234573','09171234574','09171234575','09171234576','09171234577','09171234578','09171234579','09171234580','09171234581','09171234582','09171234583','09171234584','09171234585','09171234586','09171234587','09171234588','09171234589','09171234590','09171234591','09171234592'];
$reportTemplates = [
    'Stray {animal} with {injury} spotted near the {brgy} {landmark}. Needs rescue and veterinary attention.',
    '{animal} showing {symptom} seen along the {brgy} {landmark}. Possibly needs treatment.',
    'Injured {animal} found near {brgy} {landmark}. Unable to move properly; requesting immediate dispatch.',
    'Litter of abandoned {animal}s near {brgy} {landmark}. One appears weak and dehydrated.',
    '{animal} with {injury} wandering around {brgy} {landmark}. Appears frightened but approachable.',
    '{animal} trapped in a drain near {brgy} {landmark}. Needs urgent extraction.',
    '{animal} hit by a vehicle near {brgy} {landmark}. Still alive but unable to stand.',
    '{animal} with severe {symptom} at {brgy} {landmark}. Concerned resident seeking help.',
    'Abandoned {animal} left at {brgy} {landmark}. Not microchipped; needs foster or shelter placement.',
    '{animal} with {injury} and {symptom} near {brgy} {landmark}. Community member offering temporary shelter.',
];
$landmarks = ['market','shoreline','school','church','barangay hall','bridge','roadside','park','rice field','reservoir','gym','plaza','health center','sari-sari store row','footbridge'];
$animalsList = ['dog','cat','dog','cat','dog','cat','dog','cat'];
$injuries = ['an injured leg','a fractured paw','a deep wound on the shoulder','a swollen eye','a cut on the ear','a limp in the hind leg','a neck wound','a broken rib'];
$symptoms = ['eye discharge and lethargy','vomiting and diarrhea','labored breathing','high fever','skin lesions','limping','seizures','pale gums','persistent cough','jaundice'];

function loremWords(int $n = 12): string
{
    static $words = null;
    if ($words === null) {
        $words = array_merge(
            explode(' ','lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua ut enim ad minim veniam quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur excepteur sint occaecat cupidatat non proident sunt in culpa qui officia deserunt mollit anim id est laborum'),
            explode(' ','animal welfare rescue community care shelter veterinary treatment recovery rehabilitation foster adoption medical attention'),
            explode(' ','stray injured abandoned lost trapped dehydrated malnourished sick wounded frightened calm friendly'),
            explode(' ','Barangay Mati City Davao Oriental Philippines southeast coastal urban rural barangay hall market shoreline')
        );
    }
    shuffle($words);
    return implode(' ', array_slice($words, 0, min($n, count($words))));
}

function generateAnimalName(string $species, int $seed): string
{
    $dog = ['Buddy','Luna','Max','Bella','Coco','Mimi','Bruno','Ginger','Rocky','Daisy','Shadow','Mochi','Bentley','Cleo','Oscar','Nala','Simba','Pumpkin','Cooper','Willow','Rex','Lola','Toby','Mila','Zeus','Duke','Zara','Koda','Ace','Ruby','Oreo','Honey','Bear','Storm','Maple','Peanut','Gizmo','Trixie','Baxter','Rocco','Lady','Marley','Sasha','Diesel','Nina','Tiger','Jade','Finn','Ruby','Dash'];
    $cat = ['Luna','Mimi','Coco','Shadow','Cleo','Mochi','Nala','Simba','Pumpkin','Willow','Mila','Zara','Ivy','Pepper','Oreo','Gizmo','Trixie','Sushi','Lola','Milo','Leo','Kitty','Felix','Jade','Nina','Cali','Patches','Smokey','Tigger','Muffin'];
    $arr = $species === 'cat' ? $cat : $dog;
    return $arr[$seed % count($arr)];
}

$targetResidents = 250;
$targetRescuers = 30;
$targetApplicants = 10;
$targetReports = 600;
$verifiedRatio = 0.85;

$currentResidents = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'resident'")->fetchColumn();
$currentRescuers = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'active'")->fetchColumn();
$currentApplicants = (int) $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'rescuer' AND account_status = 'pending'")->fetchColumn();

$neededResidents = max(0, $targetResidents - $currentResidents);
$neededRescuers = max(0, $targetRescuers - $currentRescuers);
$neededApplicants = max(0, $targetApplicants - $currentApplicants);

$residentIds = [];
for ($i = 0; $i < $neededResidents; $i++) {
    $fn = $firstNames[$i % count($firstNames)];
    $ln = $lastNames[$i % count($lastNames)];
    $email = strtolower($fn . $ln . ($i + 1) . '@furescue.local');
    $phone = '09' . randInt(100000000, 999999999);
    $brgy = $barangays[$i % count($barangays)][0];
    $residentIds[] = ensureUser($pdo, [
        'full_name' => $fn . ' ' . $ln,
        'email' => $email,
        'password_hash' => $hash,
        'auth_provider' => 'native',
        'role' => 'resident',
        'account_status' => 'active',
        'phone_number' => $phone,
        'address' => "Barangay {$brgy}, City of Mati",
    ]);
}
if (!$residentIds) {
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'resident' LIMIT 500");
    $residentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$rescuerIds = [];
for ($i = 0; $i < $neededRescuers; $i++) {
    $fn = $rescuerFirst[$i % count($rescuerFirst)];
    $ln = $rescuerLast[$i % count($rescuerLast)];
    $email = strtolower($fn . $ln . ($i + 1) . '@furescue.local');
    $phone = $rescuerPhones[$i % count($rescuerPhones)];
    $id = ensureUser($pdo, [
        'full_name' => $fn . ' ' . $ln,
        'email' => $email,
        'password_hash' => $hash,
        'auth_provider' => 'native',
        'role' => 'rescuer',
        'account_status' => 'active',
        'phone_number' => $phone,
    ]);
    if (!rowExists($pdo, 'rescuer_approvals', 'user_id = ?', [$id])) {
        insert($pdo, 'rescuer_approvals', [
            'user_id' => $id, 'reviewed_by' => $adminId, 'decision' => 'approved', 'reviewed_at' => timeAgo(randInt(86400, 2592000)),
        ]);
    }
    if (!rowExists($pdo, 'rescuer_duty_status', 'user_id = ?', [$id])) {
        insert($pdo, 'rescuer_duty_status', ['user_id' => $id, 'status' => randItem(['on_duty','off_duty'])]);
    }
    $rescuerIds[] = $id;
}
if (!$rescuerIds) {
    $stmt = $pdo->query("SELECT id FROM users WHERE role = 'rescuer' AND account_status = 'active' LIMIT 500");
    $rescuerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
}

for ($i = 0; $i < $neededApplicants; $i++) {
    $fn = $rescuerFirst[$i % count($rescuerFirst)];
    $ln = $rescuerLast[$i % count($rescuerLast)];
    $email = strtolower($fn . $ln . 'app' . ($i + 1) . '@furescue.local');
    ensureUser($pdo, [
        'full_name' => $fn . ' ' . $ln . ' (Applicant)',
        'email' => $email,
        'password_hash' => $hash,
        'auth_provider' => 'native',
        'role' => 'rescuer',
        'account_status' => 'pending',
        'phone_number' => '09' . randInt(100000000, 999999999),
    ]);
}

echo "Residents: " . count($residentIds) . ", Rescuers: " . count($rescuerIds) . "\n";

$currentReports = (int) $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn();
$neededReports = max(0, $targetReports - $currentReports);

$animalByIdx = [];
$caseByIdx = [];
$animalIds = [];

for ($i = 0; $i < $neededReports; $i++) {
    $brgy = $barangays[$i % count($barangays)];
    $resident = $residentIds[$i % count($residentIds)];
    $verified = ($i % 100) < ($verifiedRatio * 100);
    $template = randItem($reportTemplates);
    $animal = randItem($animalsList);
    $injury = randItem($injuries);
    $symptom = randItem($symptoms);
    $landmark = randItem($landmarks);
    $desc = str_replace(['{animal}','{injury}','{symptom}','{brgy}','{landmark}'], [$animal,$injury,$symptom,$brgy[0],$landmark], $template);
    $contentHash = substr(hash('sha256', $brgy[0] . $desc . $i), 0, 64);

    if (rowExists($pdo, 'reports', 'content_hash = ?', [$contentHash])) {
        continue;
    }

    $reportId = insert($pdo, 'reports', [
        'resident_id'         => $resident,
        'animal_description'  => $desc,
        'photo_urls'          => json_encode(["/uploads/demo/report-" . ($currentReports + $i) . ".svg"]),
        'latitude'            => $brgy[1] + randFloat(-0.005, 0.005, 4),
        'longitude'           => $brgy[2] + randFloat(-0.005, 0.005, 4),
        'address_text'        => $brgy[0] . ", City of Mati",
        'content_hash'        => $contentHash,
        'validation_status'   => $verified ? 'validated' : 'pending',
        'status'              => $verified ? 'verified' : 'pending_verification',
        'verified_by'         => $verified ? $adminId : null,
        'verified_at'         => $verified ? timeAgo(randInt(3600, 604800)) : null,
    ]);

    if (!$verified) {
        continue;
    }

    $species = $animal;
    $breedType = $species === 'cat' ? randItem($catBreeds) : randItem($dogBreeds);
    $sex = randItem(['male','female']);
    $age = randItem(['2 months','4 months','6 months','8 months','10 months','1 year','1.5 years','2 years','3 years','4 years','5 years','6 years','7 years','8 years','10 years']);
    $color = $species === 'cat' ? randItem($catColors) : randItem($dogColors);
    $aName = generateAnimalName($species, $i);
    $animalId = insert($pdo, 'animals', [
        'name'            => $aName,
        'species'         => $species,
        'breed_type'      => $breedType,
        'sex'             => $sex,
        'age_estimate'    => $age,
        'birth_date'      => randItem([null, date('Y-m-d', time() - randInt(86400*30, 86400*365*8))]),
        'color_markings'  => $color,
        'barangay'        => $brgy[0],
        'description'     => $desc,
        'adoption_status' => randItem(['not_listed','available','available','available','available']),
        'source'          => 'rescued_case',
        'created_by'      => $adminId,
        'deleted_at'      => randItem([null, null, null, null, null, null, null, null, null, null, date('Y-m-d H:i:s', time() - randInt(86400*30, 86400*180))]),
    ]);
    $animalIds[] = $animalId;
    $animalByIdx[$i] = $animalId;

    $rescuer = $rescuerIds[$i % count($rescuerIds)];
    $caseStatus = randItem(['resolved','in_progress','assigned','resolved','in_progress','resolved','assigned','in_progress','open']);
    $caseId = insert($pdo, 'cases', [
        'report_id'           => $reportId,
        'assigned_rescuer_id' => $rescuer,
        'assigned_by'         => $adminId,
        'status'              => $caseStatus,
        'resolution_notes'    => $caseStatus === 'resolved' ? loremWords(randInt(6, 15)) : null,
        'resolution_photos'   => $caseStatus === 'resolved' ? json_encode(['/uploads/demo/resolution-' . $i . '.jpg']) : null,
    ]);
    $caseByIdx[$i] = $caseId;

    $health = randItem(['healthy','healthy','healthy','not_healthy']);
    insert($pdo, 'animal_field_status', [
        'animal_id'     => $animalId,
        'case_id'       => $caseId,
        'rescue_status' => randItem(['rescued','rescued','rescued','not_rescued']),
        'health_status' => $health,
        'logged_by'     => $rescuer,
        'logged_at'     => timeAgo(randInt(3600, 604800)),
    ]);

    $vaxStatus = $health === 'not_healthy' ? randItem(['none','none','partial']) : randItem(['complete','complete','partial']);
    $condition = $health === 'not_healthy' ? randItem(['Mange','Malnutrition','Fracture','Parvovirus','Tick fever','Respiratory infection','Wound care','Dehydration']) : 'Healthy';
    $treatment = $health === 'not_healthy' ? randItem(['none','ongoing','ongoing','completed']) : 'none';
    $lastCheckup = timeAgo(randInt(86400, 86400*90));
    $nextDue = timeAgo(randInt(-86400*30, 86400*120));
    $expiry = timeAgo(randInt(-86400*30, 86400*365));
    $weight = $species === 'cat' ? randFloat(2.5, 6.5) : randFloat(5, 35);
    $temp = randFloat(37.8, 40.2);

    insert($pdo, 'animal_medical_records', [
        'animal_id'             => $animalId,
        'medical_history_notes' => loremWords(randInt(8, 20)),
        'vaccination_status'    => $vaxStatus,
        'vaccination_details'   => json_encode([['vaccine' => 'Anti-rabies', 'date' => date('Y-m-d', strtotime($lastCheckup))]]),
        'vaccine_protocols'     => json_encode([['name' => 'DHPP', 'interval_days' => 21], ['name' => 'Rabies', 'interval_days' => 365]]),
        'vaccination_records'   => json_encode([['vaccine' => 'Anti-rabies', 'date' => date('Y-m-d', strtotime($lastCheckup))]]),
        'last_checkup_date'     => date('Y-m-d', strtotime($lastCheckup)),
        'next_checkup_due'      => date('Y-m-d', strtotime($nextDue)),
        'vaccination_expiry'    => $vaxStatus === 'none' ? null : date('Y-m-d', strtotime($expiry)),
        'condition'             => $condition,
        'treatment_stage'       => $treatment,
        'deworming_status'      => randItem(['unknown','up_to_date','overdue']),
        'neutered'              => randItem(['unknown','yes','no']),
        'weight_kg'             => $weight,
        'temperature_c'         => $temp,
        'vet_name'              => randItem(['Dr. Elena Vergara','Dr. Marco Reyes','Dr. Sofia Castillo','Dr. Andre Tan','Dr. Lina Co']),
        'updated_by'            => $adminId,
    ]);

    $baseBpm = $species === 'cat' ? 165 : 95;
    for ($k = 0; $k < 6; $k++) {
        insert($pdo, 'vitals_log', [
            'animal_id'          => $animalId,
            'heart_rate_bpm'     => $baseBpm + randInt(-10, 15),
            'respiratory_rate_bpm' => $species === 'cat' ? randInt(20, 30) : randInt(15, 25),
            'recorded_at'        => timeAgo(randInt(3600, 604800)),
            'source'             => randItem(['iot','field','clinic']),
        ]);
    }

    if (randInt(0, 5) === 0) {
        $docTypes = ['medical','medical','xray','photo','vaccination','treatment_plan'];
        insert($pdo, 'animal_documents', [
            'animal_id'     => $animalId,
            'name'          => randItem(['intake-form','xray-front','xray-side','vaccination-card','treatment-plan','adoption-photo','health-certificate']),
            'doc_type'      => randItem($docTypes),
            'file_url'      => '/uploads/demo/docs/' . $animalId . '-' . randInt(1000,9999) . '.pdf',
            'meta'          => json_encode(['uploaded_via' => 'seed', 'seed_index' => $i]),
            'uploaded_by'   => $adminId,
        ]);
    }
}

echo "Reports: " . $pdo->query("SELECT COUNT(*) FROM reports")->fetchColumn() . "\n";
echo "Animals: " . $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn() . "\n";
echo "Cases: " . $pdo->query("SELECT COUNT(*) FROM cases")->fetchColumn() . "\n";

$totalAnimals = (int) $pdo->query("SELECT COUNT(*) FROM animals")->fetchColumn();
$listedAnimals = (int) $pdo->query("SELECT COUNT(*) FROM adoption_listings")->fetchColumn();
$targetListings = max(0, (int) ($totalAnimals * 0.4) - $listedAnimals);

$allAnimalIds = [];
$stmt = $pdo->query("SELECT id, adoption_status FROM animals WHERE deleted_at IS NULL AND adoption_status != 'adopted' LIMIT 10000");
while ($row = $stmt->fetch()) {
    $allAnimalIds[] = $row['id'];
}
shuffle($allAnimalIds);
$toList = array_slice($allAnimalIds, 0, $targetListings);
foreach ($toList as $animalId) {
    insert($pdo, 'adoption_listings', [
        'animal_id'    => $animalId,
        'posted_by'    => $adminId,
        'status'       => 'approved',
        'reviewed_by'  => $adminId,
        'review_notes' => 'Procedurally approved for listing.',
        'reviewed_at'  => timeAgo(randInt(3600, 604800)),
    ]);
}

$neededAdoptions = max(0, 300 - (int) $pdo->query("SELECT COUNT(*) FROM adoptions")->fetchColumn());
for ($i = 0; $i < $neededAdoptions; $i++) {
    if (empty($allAnimalIds)) break;
    $animalId = randItem($allAnimalIds);
    $applicant = $residentIds[$i % count($residentIds)];
    $status = randItem(['pending','pending','pending','approved','approved','rejected','cancelled','completed']);
    if (!rowExists($pdo, 'adoptions', 'animal_id = ? AND applicant_id = ? AND status = ?', [$animalId, $applicant, $status])) {
        insert($pdo, 'adoptions', [
            'animal_id'    => $animalId,
            'applicant_id' => $applicant,
            'message'      => loremWords(randInt(8, 20)),
            'status'       => $status,
            'reviewed_by'  => in_array($status, ['pending','cancelled']) ? null : $adminId,
            'reviewed_at'  => in_array($status, ['pending','cancelled']) ? null : timeAgo(randInt(3600, 604800)),
            'completed_at' => $status === 'completed' ? timeAgo(randInt(86400, 86400*30)) : null,
            'rejection_reason' => $status === 'rejected' ? loremWords(randInt(5, 10)) : null,
        ]);
        if ($status === 'completed') {
            $pdo->prepare("UPDATE animals SET adoption_status = 'adopted' WHERE id = ?")->execute([$animalId]);
        } elseif ($status === 'pending') {
            $pdo->prepare("UPDATE animals SET adoption_status = 'pending' WHERE id = ?")->execute([$animalId]);
        }
    }
}

$allUserIds = [];
$stmt = $pdo->query("SELECT id FROM users LIMIT 1000");
while ($row = $stmt->fetch()) {
    $allUserIds[] = $row['id'];
}

$neededMessages = max(0, 500 - (int) $pdo->query("SELECT COUNT(*) FROM messages")->fetchColumn());
for ($i = 0; $i < $neededMessages; $i++) {
    $sender = $allUserIds[$i % count($allUserIds)];
    $receiver = $allUserIds[($i + 1) % count($allUserIds)];
    if ($sender === $receiver) continue;
    $types = ['report','case','adoption'];
    $relType = randItem($types);
    $relId = Database::uuidV4();
    insert($pdo, 'messages', [
        'sender_id'   => $sender,
        'receiver_id' => $receiver,
        'related_type' => $relType,
        'related_id'   => $relId,
        'message_text' => loremWords(randInt(4, 12)),
        'sent_at'      => timeAgo(randInt(3600, 604800)),
        'read_at'      => randItem([null, null, date('Y-m-d H:i:s')]),
    ]);
}

$neededNotifications = max(0, 400 - (int) $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn());
for ($i = 0; $i < $neededNotifications; $i++) {
    $userId = $allUserIds[$i % count($allUserIds)];
    $types = ['admin','case_update','report_update','adoption_update','system'];
    insert($pdo, 'notifications', [
        'user_id'     => $userId,
        'type'        => randItem($types),
        'message'     => loremWords(randInt(6, 14)),
        'related_type' => randItem(['report','case','adoption','animal','module']),
        'related_id'   => Database::uuidV4(),
        'is_read'     => randItem([0,0,1]),
    ]);
}

$modules = [];
$stmt = $pdo->query("SELECT id FROM elearning_modules LIMIT 50");
while ($row = $stmt->fetch()) {
    $modules[] = $row['id'];
}

$neededProgress = max(0, 600 - (int) $pdo->query("SELECT COUNT(*) FROM elearning_progress")->fetchColumn());
for ($i = 0; $i < $neededProgress; $i++) {
    $resident = $residentIds[$i % count($residentIds)];
    $module = $modules[$i % count($modules)];
    $status = randItem(['not_started','in_progress','completed','completed']);
    if (!rowExists($pdo, 'elearning_progress', 'resident_id = ? AND module_id = ?', [$resident, $module])) {
        insert($pdo, 'elearning_progress', [
            'resident_id'   => $resident,
            'module_id'     => $module,
            'status'        => $status,
            'completed_at'  => $status === 'completed' ? timeAgo(randInt(3600, 604800)) : null,
        ]);
    }
}

$count = static fn(string $sql): int => (int) $pdo->query($sql)->fetchColumn();

echo "Seeded successfully.\n";
echo "  admin:    admin@furescue.local / {$devPassword}\n";
echo "  rescuers: rescuer@furescue.local + " . ($count("SELECT COUNT(*) FROM users WHERE role = 'rescuer'") - 1) . " procedural / {$devPassword}\n";
echo "  residents: " . $count("SELECT COUNT(*) FROM users WHERE role = 'resident'") . " procedural / {$devPassword}\n";
echo "  reports: " . $count("SELECT COUNT(*) FROM reports")
    . " (verified " . $count("SELECT COUNT(*) FROM reports WHERE status = 'verified'")
    . ", pending_verification " . $count("SELECT COUNT(*) FROM reports WHERE status = 'pending_verification'") . ")\n";
echo "  animals: " . $count("SELECT COUNT(*) FROM animals")
    . ", cases: " . $count("SELECT COUNT(*) FROM cases")
    . ", adoptions: " . $count("SELECT COUNT(*) FROM adoptions") . "\n";
echo "  e-learning modules: " . $count("SELECT COUNT(*) FROM elearning_modules") . "\n";
echo "  notifications: " . $count("SELECT COUNT(*) FROM notifications") . "\n";
echo "  messages: " . $count("SELECT COUNT(*) FROM messages") . "\n";
echo "  vitals_log: " . $count("SELECT COUNT(*) FROM vitals_log") . "\n";
echo "  adoption_listings: " . $count("SELECT COUNT(*) FROM adoption_listings") . "\n";

require __DIR__ . '/seed_permissions.php';
