# PHASE 2 HANDOFF — HTML→PHP Migration (FurEscue)

> LIVING FILE. Every agent finishing a unit MUST update this file before ending its session.
> Every agent starting a unit MUST read this file first. If your unit's prerequisites are not ✅, STOP and report — do not improvise past gaps.

## How to use these prompts
Units run strictly in order: 2.0 → 2.1 → 2.2 → 2.3 → 2.4 → 2.5 → 2.6 → 2.7 → 2.8.
Drag exactly ONE `2.x-*.md` into a fresh agent session. The prompt is fully self-contained.

## Unit status

| Unit | Page | Status | Notes |
|------|------|--------|-------|
| 2.0 | Admin shell (shared include) | ⬜ not started | |
| 2.1 | Dashboard `admin/index.html` | ⬜ blocked by 2.0 | |
| 2.2 | Cases `admin/cases.html` | ⬜ blocked by 2.0 | |
| 2.3 | Case-detail `admin/case-detail.html` | ⬜ blocked by 2.2 | |
| 2.4 | Animals `admin/animals.html` | ⬜ blocked by 2.0 | |
| 2.5 | Reports `admin/reports.html` | ⬜ blocked by 2.0 | |
| 2.6 | Rescuers `admin/rescuers.html` | ⬜ blocked by 2.0 | |
| 2.7 | Health-records `admin/health-records.html` | ⬜ blocked by 2.0 | |
| 2.8 | Health-record `admin/health-record.html` | ⬜ blocked by 2.7 | |

## Completed-so-far ledger (append per unit)
*(format: `- [2.x] <date> — files touched, key class-string notes, deviations`)*

- [Phase 1, prior session] DONE + verified: `public/includes/{site-head,header,footer,guard,homepage}.php`, `src/Auth/SessionAuth.php`, `public/auth/login.php` (+`auth/js/auth.js` interactivity-only rewrite), landing ported into `public/index.php` @ `/`; deleted `public/index.html`, `public/landing/index.html`, `public/auth/login.html`. Deviations accepted: static-passthrough (`return false`) added in `public/index.php` (PHP ≥8.3 built-in server routes EVERY request through the router script — without it assets never serve); navbar "Get Started" → `/auth/login.php` (signup page never existed); icons still hydrate client-side via lucide.

## Cross-cutting facts every unit relies on
- Portable PHP CLI: `%TEMP%\opencode\furescue-php\php.exe` (8.3.33; pdo_mysql/openssl/mbstring/zip on; **no pdo_pgsql**).
- Verification mirror: `%TEMP%\opencode\furescue-mirror` (repo copy WITH vendor/). Repo root has NO vendor and OneDrive blocks composer there — never composer-install in the repo.
- Test loop: copy changed files repo→mirror (same relative paths), start DETACHED server (`Start-Process`, NOT Start-Job — jobs die with the shell), curl assertions, kill process when done.
- NO working DB credentials exist. MySQL80 runs locally but documented demo creds are access-denied; user wires `.env` themselves later. Therefore: verify HTTP 200 + shell/guard DOM markers + `php -l`; DB-row fidelity is assured by code review against controller serialization, not live data. DB-backed smoke test instructions ship in the final handoff for the user.
- `public/index.php` flow: `'/'` → homepage branch; real existing files under `public/` → native serve/exec via `return false` passthrough; everything else → API router. New `public/admin/*.php` pages execute directly once created.
- `guard.php` contract: define `$requiredRole` BEFORE `require __DIR__ . '/../includes/guard.php';`. Anon → 302 `/auth/login.php`. Wrong role → 302 `/index.php`.
