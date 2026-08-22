# FurEscue (Spot)

A web-based animal rescue and adoption management system for **Mati City**. It connects community members, rescuers, and the City Veterinarian's Office on one platform for rescue reporting, geotagged case mapping, pet adoption, e-learning on responsible pet ownership, and welfare analytics.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Vanilla PHP 8.1+ REST API (PDO + MySQL), custom router, JWT auth |
| Frontend | Static HTML + ES modules, Tailwind CSS |
| Maps | Leaflet + leaflet.heat (heatmap of reported cases) |
| Testing | PHPUnit |

## Quick Start

Full setup guide: [docs/technical/HOW_TO_RUN.md](docs/technical/HOW_TO_RUN.md)

```bat
composer install
php bin\migrate.php
php seeders\seed.php
php -S 127.0.0.1:8000 -t public public\index.php
```

Then in a second terminal: `npm install && npm run build`

Open http://127.0.0.1:8000/landing/index.html — demo accounts are listed in HOW_TO_RUN.

## Repository Layout

```
backend/          PHP REST API source (src/, tests/)
public/           Web root — static pages, CSS, uploads
bin/              CLI tools (migrations)
migrations/       SQL schema migrations
seeders/          Demo data seeder
docs/             Project documentation
```

## Documentation

### Study / Paper Documents ([docs/study](docs/study))

| Document | Contents |
|----------|----------|
| [OBJECTIVES.md](docs/study/OBJECTIVES.md) | 1.3 Objectives of the Study |
| [SIGNIFICANCE_AND_SCOPE.md](docs/study/SIGNIFICANCE_AND_SCOPE.md) | 1.4 Significance · 1.5 Scope and Limitations |
| [CONCEPTUAL_FRAMEWORK.md](docs/study/CONCEPTUAL_FRAMEWORK.md) | 1.6 Conceptual Framework (IPO model) |
| [REQUIREMENTS_SPECIFICATION.md](docs/study/REQUIREMENTS_SPECIFICATION.md) | Functional requirements per user role |
| [DESIGN_SYSTEM.md](docs/study/DESIGN_SYSTEM.md) | Color palette & typography |

### Technical Documents ([docs/technical](docs/technical))

| Document | Contents |
|----------|----------|
| [ARCHITECTURE_AUDIT.md](docs/technical/ARCHITECTURE_AUDIT.md) | Facts observed in the repository (stack, routing, auth, etc.) |
| [FEATURES.md](docs/technical/FEATURES.md) | Feature inventory (as built) — what is implemented per feature area, with evidence |
| [IMPLEMENTATION_AUDIT.md](docs/technical/IMPLEMENTATION_AUDIT.md) | Traceability: study objectives & requirements vs. implementation status |
| [HOW_TO_RUN.md](docs/technical/HOW_TO_RUN.md) | Install, configure, migrate, seed, and run |
| [SYSTEM_REPORT.md](docs/technical/SYSTEM_REPORT.md) | System report |
