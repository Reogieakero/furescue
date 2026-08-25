# FurEscue (Spot)

A web-based animal rescue and adoption management system for **Mati City**. It connects community members, rescuers, and the City Veterinarian's Office on one platform for rescue reporting, geotagged case mapping, pet adoption, e-learning on responsible pet ownership, and welfare analytics.

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Vanilla PHP 8.1+ REST API (PDO + MySQL), custom router, JWT auth |
| Frontend | PHP-rendered pages + ES-module islands, Tailwind CSS |
| Maps | Leaflet + leaflet.heat (heatmap of reported cases) |
| Testing | PHPUnit |

## Quick Start

Full setup (PHP extensions, MySQL user, `.env`, troubleshooting, demo accounts): **[docs/technical/HOW_TO_RUN.md](docs/technical/HOW_TO_RUN.md)**.

You need **PHP 8.1+** (with `pdo_mysql`, `mysqli`, `openssl`, `mbstring`, `curl`), **MySQL 8.0.13+**, **Composer**, and **Node.js 18+**. Then from the repo root:

```bat
copy .env.example .env
```

Edit `.env` and set `DB_DRIVER=mysql` plus your MySQL credentials (create the `furescue` database first — SQL is in HOW_TO_RUN). Then:

```bat
composer install
php bin\migrate.php
php seeders\seed.php
npm install
npm run build
php -S 127.0.0.1:8000 -t public public\index.php
```

Open:

| Page | URL |
|------|-----|
| Landing | http://127.0.0.1:8000/ |
| Login | http://127.0.0.1:8000/auth/login.php |
| Admin | http://127.0.0.1:8000/admin/ |

Seeded password for every demo account: **`Password123!`**. Admin: `admin@furescue.local`.

## Repository Layout

```
src/              PHP API (controllers, services, auth, routes)
public/           Web root — PHP pages, CSS, JS, uploads
bin/              CLI tools (migrations)
migrations/       SQL schema migrations
seeders/          Demo data seeder
tests/            PHPUnit unit tests
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
| [HOW_TO_RUN.md](docs/technical/HOW_TO_RUN.md) | Install, configure, migrate, seed, and run |
| [SYSTEM_REPORT.md](docs/technical/SYSTEM_REPORT.md) | System report |
