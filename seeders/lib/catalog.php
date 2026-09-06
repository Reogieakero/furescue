<?php

/**
 * Realistic Mati City catalogs for demo seeders.
 */

if (defined('SEED_CATALOG_LOADED')) {
    return;
}
define('SEED_CATALOG_LOADED', true);

function seed_catalog_barangays(): array
{
    return [
        ['Central', 6.9550, 126.2020],
        ['Dahican', 6.9412, 126.2300],
        ['Bobon', 6.9180, 126.2310],
        ['Taguibo', 7.0020, 126.2390],
        ['Mayo', 6.9610, 126.1650],
        ['Matiao', 6.9060, 126.2070],
        ['Tamisan', 6.9240, 126.2520],
        ['Mamali', 6.8970, 126.1770],
        ['Don Salvador Lopez', 6.9730, 126.1500],
        ['Badas', 6.9120, 126.2180],
        ['Don Martin Marundan', 6.9480, 126.1880],
        ['Buso', 6.9800, 126.1950],
        ['Cabuaya', 6.9350, 126.1600],
        ['Culian', 6.9900, 126.1700],
        ['Danao', 6.9050, 126.2450],
        ['Dawan', 6.9680, 126.2250],
        ['Langka', 6.9200, 126.1850],
        ['Lawigan', 6.8950, 126.2550],
        ['Libudon', 6.9750, 126.2450],
        ['Luban', 6.9400, 126.1450],
        ['Macambol', 6.9000, 126.2000],
        ['Sainz', 6.9580, 126.2150],
        ['Sanghay', 6.9300, 126.2200],
        ['Tagabakid', 6.9850, 126.2100],
        ['Tagbinonga', 6.9150, 126.1550],
    ];
}

function seed_catalog_first_names(): array
{
    return [
        'Ana', 'Pedro', 'Rosa', 'Miguel', 'Juan', 'Maria', 'Liza', 'Carlos', 'Elena', 'Diego',
        'Sofia', 'Marco', 'Isabella', 'Luis', 'Camila', 'Gabriel', 'Valentina', 'Andres', 'Natalia', 'Jose',
        'Lucia', 'Fernando', 'Paola', 'Ricardo', 'Angela', 'Roberto', 'Daniela', 'Oscar', 'Adriana', 'Manuel',
        'Patricia', 'Jorge', 'Clara', 'Alberto', 'Monica', 'Raul', 'Teresa', 'Arturo', 'Gloria', 'Hector',
        'Silvia', 'Eduardo', 'Rebecca', 'Francisco', 'Vanessa', 'Ramon', 'Irene', 'Salvador', 'Emma', 'Pablo',
        'Katrina', 'Noel', 'Jasmine', 'Rafael', 'Bianca', 'Cedric', 'Aileen', 'Dominic', 'Joyce', 'Harold',
        'Marites', 'Reynaldo', 'Jonalyn', 'Wilfredo', 'Chona', 'Armando', 'Lourdes', 'Benjie', 'Rowena', 'Cesar',
    ];
}

function seed_catalog_last_names(): array
{
    return [
        'Santos', 'Ramos', 'Dela Cruz', 'Torres', 'Garcia', 'Mendoza', 'Villanueva', 'Rivera', 'Castillo', 'Aquino',
        'Cruz', 'Sanchez', 'Perez', 'Reyes', 'Gonzales', 'Bautista', 'Hernandez', 'Dizon', 'Magbanua', 'Lopez',
        'Flores', 'Navarro', 'Vargas', 'Soriano', 'Medina', 'Espinoza', 'Coronel', 'Pascual', 'Salvador', 'Mercado',
        'Manalo', 'Tadeo', 'Calderon', 'Dagohoy', 'Ferrer', 'Abella', 'Ong', 'Chua', 'Tan', 'Go',
        'Yap', 'Sy', 'Araneta', 'Enriquez', 'Alvarado', 'Cabrera', 'Domingo', 'Estrada', 'Francisco', 'Gutierrez',
        'Ignacio', 'Jimenez', 'Lim', 'Morales', 'Nunez', 'Ortega', 'Padilla', 'Quijano', 'Romero', 'Santiago',
    ];
}

function seed_catalog_landmarks(): array
{
    return [
        'public market', 'barangay hall', 'covered court', 'elementary school', 'health center',
        'sari-sari store row', 'coastal road', 'fish port', 'rice field edge', 'chapel',
        'waiting shed', 'purok basketball court', 'junction near the highway', 'drainage canal',
        'Dahican Beach access road', 'Pujada Bay shoreline', 'City Hall vicinity',
        'DOrSU campus gate', 'Subangan Museum parking', 'Mati Airport road',
        'provincial capitol grounds', 'Guang-guang mangrove path', 'Lawigan coastline',
        'Mayo boulevard', 'Balete tree park',
    ];
}

function seed_catalog_dog_names(): array
{
    return [
        'Buddy', 'Luna', 'Max', 'Bella', 'Coco', 'Bruno', 'Ginger', 'Rocky', 'Daisy', 'Shadow',
        'Mochi', 'Bentley', 'Oscar', 'Nala', 'Simba', 'Cooper', 'Willow', 'Rex', 'Lola', 'Toby',
        'Mila', 'Zeus', 'Duke', 'Zara', 'Koda', 'Ace', 'Ruby', 'Oreo', 'Honey', 'Bear',
        'Storm', 'Maple', 'Peanut', 'Gizmo', 'Trixie', 'Baxter', 'Rocco', 'Lady', 'Marley', 'Sasha',
        'Diesel', 'Nina', 'Tiger', 'Jade', 'Finn', 'Dash', 'Choco', 'Bantay', 'Tambay', 'Ligaya',
        'Toyo', 'Sawa', 'Putol', 'Kampupot', 'Balong', 'Tisoy', 'Bolt', 'Amber', 'Rusty', 'Sky',
    ];
}

function seed_catalog_cat_names(): array
{
    return [
        'Mimi', 'Cleo', 'Mochi', 'Pumpkin', 'Ivy', 'Pepper', 'Sushi', 'Milo', 'Leo', 'Felix',
        'Cali', 'Patches', 'Smokey', 'Tigger', 'Muffin', 'Luna', 'Shadow', 'Willow', 'Nala', 'Simba',
        'Oreo', 'Gizmo', 'Trixie', 'Jade', 'Nina', 'Kitty', 'Mingming', 'Puspin', 'Tambi', 'Garfield',
        'Misty', 'Snow', 'Cocoa', 'Marble', 'Pudding', 'Biscuit', 'Nori', 'Mango', 'Lilikoi', 'Suki',
        'Kira', 'Mochi', 'Bean', 'Olive', 'Hazel', 'Ash', 'Pearl', 'Inky', 'Dot', 'Pebble',
    ];
}

function seed_catalog_dog_colors(): array
{
    return [
        'brown and white', 'solid black', 'fawn', 'white and tan', 'brindle', 'cream',
        'dark brindle', 'golden', 'tricolor', 'chocolate', 'rust and white', 'gray and white',
        'red-brown', 'spotted white', 'black with white chest', 'tan with black muzzle',
    ];
}

function seed_catalog_cat_colors(): array
{
    return [
        'solid black', 'gray and white', 'orange tabby', 'tuxedo', 'calico', 'seal point',
        'flame point', 'tortoiseshell', 'solid white', 'smoke', 'lynx point', 'dilute calico',
        'brown tabby', 'silver tabby', 'black and orange',
    ];
}

function seed_catalog_vets(): array
{
    return [
        'Dr. Elena Vergara', 'Dr. Marco Reyes', 'Dr. Sofia Castillo', 'Dr. Andre Tan',
        'Dr. Lina Co', 'Dr. Paolo Mendoza', 'Dr. Camille Dizon', 'Dr. Hector Ramos',
    ];
}

function seed_catalog_report_stories(): array
{
    return [
        'Stray {animal} with {injury} near the {brgy} {landmark}. Approachable but in pain; asking City Vet to dispatch.',
        '{animal} showing {symptom} along the {brgy} {landmark}. Has been there since morning and will not leave the shade.',
        'Injured {animal} found beside the {brgy} {landmark}. Unable to put weight on one leg; needs transport.',
        'Litter of abandoned {animal}s under a crate near {brgy} {landmark}. One pup/kitten looks weak and dehydrated.',
        '{animal} with {injury} wandering around {brgy} {landmark}. Frightened but took water from a resident.',
        '{animal} trapped near a drain by the {brgy} {landmark}. Neighbors can hear it but cannot reach it safely.',
        '{animal} hit by a motorcycle near {brgy} {landmark}. Still breathing, cannot stand. Immediate help requested.',
        '{animal} with severe {symptom} at {brgy} {landmark}. Concerned resident can keep watch until a rescuer arrives.',
        'Abandoned {animal} tied to a post at {brgy} {landmark}. No collar or microchip; needs foster or shelter.',
        '{animal} with {injury} and {symptom} near {brgy} {landmark}. A sari-sari store owner is offering temporary shade.',
        'Thin {animal} scavenging leftovers behind the {brgy} {landmark}. Possible mange; children have been feeding it.',
        'Friendly {animal} following tricycles at {brgy} {landmark}. Looks lost rather than feral; please check for an owner.',
        'Pregnant {animal} hiding under the {brgy} {landmark}. Residents want her brought in before she delivers on the street.',
        'Night-shift vendor saw a {animal} with {injury} sleeping by the {brgy} {landmark}. Still there at sunrise.',
        '{animal} cubs crying from a box left at {brgy} {landmark}. Note says the family can no longer keep them.',
    ];
}

function seed_catalog_injuries(): array
{
    return [
        'an injured hind leg', 'a fractured front paw', 'a deep shoulder wound',
        'a swollen left eye', 'a torn ear tip', 'a limp on the right hind leg',
        'a neck abrasion from a tight tie', 'road rash along the flank',
    ];
}

function seed_catalog_symptoms(): array
{
    return [
        'eye discharge and lethargy', 'vomiting and diarrhea', 'labored breathing',
        'high fever and hiding', 'patchy hair loss', 'persistent coughing',
        'pale gums', 'not eating for two days', 'head tilt', 'severe itching',
    ];
}

function seed_catalog_ages(): array
{
    return [
        ['8 weeks', 56],
        ['3 months', 90],
        ['5 months', 150],
        ['8 months', 240],
        ['1 year', 365],
        ['1.5 years', 540],
        ['2 years', 730],
        ['3 years', 1095],
        ['4 years', 1460],
        ['5 years', 1825],
        ['7 years', 2555],
        ['9 years', 3285],
    ];
}

function seed_catalog_conditions(): array
{
    return [
        'Mange', 'Malnutrition', 'Fracture', 'Tick fever', 'Respiratory infection',
        'Wound care', 'Dehydration', 'Kennel cough', 'Ear mites', 'Abscess',
    ];
}

function seed_email_slug(string $name): string
{
    $slug = strtolower($name);
    $slug = str_replace(['ñ', 'Ñ'], 'n', $slug);
    $slug = preg_replace('/[^a-z0-9]+/', '', $slug) ?? '';
    return $slug !== '' ? $slug : 'user';
}
