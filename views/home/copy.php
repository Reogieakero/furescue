<?php

declare(strict_types=1);

$badgeClassMap = [
    'default' => 'badge badge--solid',
    'secondary' => 'badge badge--soft',
    'accent' => 'badge badge--sage',
];

$audiences = [
    [
        'id' => 'rescuers',
        'icon' => 'siren',
        'title' => 'Animal Rescuers & Volunteers',
        'badge' => 'Faster response',
        'badgeVariant' => 'default',
        'desc' => 'Manage animal-related reports from one place. Locate cases on a map, prioritize the most urgent situations, and respond quicker — reducing delays across every rescue operation.',
        'points' => [
            'Live map of reported cases near you',
            'Urgent-first queue & status tracking',
            'Coordinate teams & volunteer shifts',
        ],
    ],
    [
        'id' => 'vets',
        'icon' => 'stethoscope',
        'title' => 'City Veterinarian',
        'badge' => 'Data-driven',
        'badgeVariant' => 'secondary',
        'desc' => 'Organize and monitor animal welfare data with clear visibility into high-incident areas. Plan actions and allocate resources where they matter most, backed by real reporting.',
        'points' => [
            'Heatmaps of frequent incident zones',
            'Centralized welfare dashboards',
            'Resource & clinic allocation planning',
        ],
    ],
    [
        'id' => 'community',
        'icon' => 'users',
        'title' => 'Community Members',
        'badge' => 'Get involved',
        'badgeVariant' => 'accent',
        'desc' => 'A simple, accessible way to report stray, injured, or abandoned animals and to browse pets available for adoption. Strengthen the public–rescuer collaboration and help animals find permanent homes.',
        'points' => [
            'Report a stray in under a minute',
            'Browse Puspin & Aspin for adoption',
            'Track the impact of your reports',
        ],
    ],
];

$features = [
    ['icon' => 'map', 'title' => 'Map-based case locating', 'desc' => 'Every report is pinned to a map so rescuers and vets can see exactly where help is needed and route efficiently.'],
    ['icon' => 'bell-ring', 'title' => 'Urgency prioritization', 'desc' => 'Injured and at-risk animals are surfaced first, helping teams act on the most critical cases without delay.'],
    ['icon' => 'zap', 'title' => 'Faster response times', 'desc' => 'A centralized inbox of reports removes the back-and-forth and shortens the path from sighting to rescue.'],
    ['icon' => 'bar-chart-3', 'title' => 'Welfare analytics', 'desc' => 'City vets get visibility into incident hotspots and trends to plan data-driven, resource-smart action.'],
    ['icon' => 'home', 'title' => 'Adoption marketplace', 'desc' => 'Community members browse Puspin and Aspin available for adoption, making the process simple and efficient.'],
    ['icon' => 'users', 'title' => 'Community collaboration', 'desc' => 'Public reports, rescuer coordination, and vet oversight in one platform that strengthens the whole network.'],
];

$steps = [
    ['n' => '01', 'title' => 'Report', 'status' => 'Reporting sighting…', 'desc' => 'Community members spot a stray, injured, or abandoned Puspin or Aspin and file a quick report with a photo and location.'],
    ['n' => '02', 'title' => 'Locate & Prioritize', 'status' => 'Locating & prioritizing…', 'desc' => 'Reports appear on the shared map. Rescuers and the city vet see urgent cases first and plan the fastest route.'],
    ['n' => '03', 'title' => 'Rescue & Rehome', 'status' => 'Updating status…', 'desc' => 'Teams respond, vets monitor welfare data, and recovered animals move into the adoption marketplace for a permanent home.'],
];

$stats = [
    ['value' => '190+', 'label' => 'Rescued Animals', 'sub' => 'puspin & aspin given a second chance'],
    ['value' => '64', 'label' => 'Adoptions', 'sub' => 'matched with forever homes'],
    ['value' => '350+', 'label' => 'Reports Handled', 'sub' => 'from sighting to safe rescue'],
    ['value' => '48', 'label' => 'Active Volunteers', 'sub' => 'rescuers on the ground daily'],
];

$whatWeDo = [
    ['icon' => 'megaphone', 'title' => 'Report', 'desc' => 'Spotted a stray, injured, or abandoned animal? Send a quick report with a photo and location in under a minute.'],
    ['icon' => 'map-pin', 'title' => 'Locate', 'desc' => 'Every case is pinned to a shared live map, so responders see exactly where help is needed.'],
    ['icon' => 'ambulance', 'title' => 'Rescue', 'desc' => 'Urgent cases are prioritized so rescuers and city vets can act fast where it matters most.'],
    ['icon' => 'heart', 'title' => 'Adopt', 'desc' => 'Recovered Puspin & Aspin find permanent homes through the adoption marketplace.'],
];
