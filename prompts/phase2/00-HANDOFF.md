# PHASE 2 HANDOFF — HTML→PHP Migration (FurEscue)

> LIVING FILE. Every agent finishing a unit MUST update this file before ending its session.
> Every agent starting a unit MUST read this file first. If your unit's prerequisites are not ✅, STOP and report — do not improvise past gaps.

## How to use these prompts
Units run strictly in order: 2.0 → 2.1 → 2.2 → 2.3 → 2.4 → 2.5 → 2.6 → 2.7 → 2.8.
Drag exactly ONE `2.x-*.md` into a fresh agent session. The prompt is fully self-contained.

## Unit status

| Unit | Page | Status | Notes |
|------|------|--------|-------|
| 2.0 | Admin shell (shared include) | ✅ done | `public/includes/admin-shell.php` — contract in ledger below |
| 2.1 | Dashboard `admin/index.php` (was `.html`) | ⬜ unblocked — RE-SCOPE first: page already ported on legacy shell API; see 2.0 ledger entry |
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
- [2.0, 2026-08-22] DONE + verified: created `public/includes/admin-shell.php`; extended `public/includes/site-head.php` with optional `$fontsHref` (default Nunito link unchanged when unset); flipped `NAV_TARGETS.dashboard` → `/admin/index.php` in `public/admin/js/layout/app-shell.js` (all other targets still `.html`). Verified via mirror harness: HTTP 200 + `.admin-shell`/`#admin-date`/nav items/active-highlight/dropdown markup; badge override (reports=21) + default notifications=3 + static badges intact; anon guard 302 → `/auth/login.php`; landing `/` regression-clean (Nunito link, no Fraunces). Both files `php -l` clean.
  - **`admin-shell.php` contract** — define BEFORE `require __DIR__ . '/../includes/admin-shell.php';`: `$adminUser` = array id/full_name/email/role/profile_photo_url (photo empty → falls back to pravatar img like JS); `$activeNav` = string lowercase key ('' → 'dashboard'); `$navBadges` = assoc map badgeKey→value, may be empty (server merge = `['notifications'=>3] + $navBadges`; explicit `null` value suppresses that item's static badge, matching JS); `$adminChildren` = pre-rendered HTML string echoed inside `<main class="admin-main">`. Pair with site-head: set `$fontsHref` to Fraunces+Nunito+IBM Plex Mono URL and `$pageCss=['/admin/css/admin.css', ...]`.
  - **DISCOVERED DISCREPANCY (pre-existing, committed in d687357 as "early admin PHP scaffolding", never ledgered):** unit 2.1's source page `public/admin/index.html` NO LONGER EXISTS — a full dashboard port already lives at `public/admin/index.php`, built on a LEGACY function-API shell: `public/admin/includes/{shell.php,ui-helpers.php}` exposing `admin_app_shell($children, $badges, $notifications, $activeNav)` (different file + different API than this unit's variable-based include). Dashboard nav target was already relative `index.php` before this unit made it absolute. Unit 2.1 must be re-scoped as reconcile/migrate `index.php` onto `public/includes/admin-shell.php` + decide fate of legacy `admin/includes/shell.php` (left untouched here; both coexist harmlessly). Also note: existing `index.php` currently passes the admin fonts URL through `$pageCss` instead of `$fontsHref`.
  - No deviations from unit spec; no blockers left open.

## Cross-cutting facts every unit relies on
- Portable PHP CLI: `%TEMP%\opencode\furescue-php\php.exe` (8.3.33; pdo_mysql/openssl/mbstring/zip on; **no pdo_pgsql**).
- Verification mirror: `%TEMP%\opencode\furescue-mirror` (repo copy WITH vendor/). Repo root has NO vendor and OneDrive blocks composer there — never composer-install in the repo.
- Test loop: copy changed files repo→mirror (same relative paths), start DETACHED server (`Start-Process`, NOT Start-Job — jobs die with the shell), curl assertions, kill process when done.
- NO working DB credentials exist. MySQL80 runs locally but documented demo creds are access-denied; user wires `.env` themselves later. Therefore: verify HTTP 200 + shell/guard DOM markers + `php -l`; DB-row fidelity is assured by code review against controller serialization, not live data. DB-backed smoke test instructions ship in the final handoff for the user.
- `public/index.php` flow: `'/'` → homepage branch; real existing files under `public/` → native serve/exec via `return false` passthrough; everything else → API router. New `public/admin/*.php` pages execute directly once created.
- `guard.php` contract: define `$requiredRole` BEFORE `require __DIR__ . '/../includes/guard.php';`. Anon → 302 `/auth/login.php`. Wrong role → 302 `/index.php`.
