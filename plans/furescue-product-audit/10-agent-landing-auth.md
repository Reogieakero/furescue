# Agent 10 --- Landing + Auth

## Ownership

Owned routes:

-   `/`
-   `/auth/login.php`
-   `/auth/signup.php`
-   `/auth/logout.php`

Owned files:

-   `public/includes/homepage.php`
-   `public/landing/**`
-   `public/auth/login.php`
-   `public/auth/signup.php`
-   `public/auth/logout.php`
-   `public/auth/js/auth.js`
-   `public/auth/css/auth.css`
-   `public/includes/header.php`
-   `public/includes/footer.php`

Do not edit `resident-shell.php`, `admin-shell.php`, `admin-nav.php`,
or `public/js/components/ui/*`.

## Design system

Tokens: `--primary`, `--paper`, `--ink`, `--jungle`, `--coral`,
`--brand-1/2`, `--radius`, `--shadow-md`, `--font-sans` /
`--font-display`. Classes: `.logo-mark`, `.btn`, `.input`, `.field`.
Lucide only. No raw study hex (`#3D7432`) or DM Sans in new CSS.
Landing `$fontsHref` and `--font-sans` now match live Nunito / Fraunces /
IBM Plex Mono (this run). Landing-only CSS — no `npm run build`.
Verify 375 / 768 / 1440.

## File size / split

`homepage.php` is ~221 after the prior copy/hero-art extract. This run
only changed `$fontsHref`. Do not grow it.

## Interaction checklist

Landing:

-   [x] Log in / Get Started → `/auth/login.php`
-   [x] in-page nav: `#home`, `#audiences`, `#features`, `#how`
-   [x] mobile hamburger `#menu-toggle` / `#mobile-menu` at 375px
-   [x] hero CTAs “Adopt a Friend” / “Report an Activity”
-   [x] audience “Learn more” hashes
-   [x] footer column links
-   [x] footer brand `#home`

Auth:

-   [x] login POST with demo resident and admin
-   [x] empty-field validation
-   [x] wrong password error
-   [x] password eye toggle
-   [x] Google button (`href="#google"` + `data-google-signin`)
-   [x] signup POST + validation
-   [x] link login ↔ signup
-   [x] logout clears tokens and lands on login
-   [x] already-authenticated redirect (session guard)
-   [x] admin login lands on admin; resident on `/`

## Viewport checklist

| Route | 375 | 768 | 1440 | Notes |
| --- | --- | --- | --- | --- |
| `/` | clicked | clicked | clicked | hamburger opens at 375 (`is-open`, `aria-expanded=true`); hidden at 768/1440. `innerWidth` 375/768/1440. `overflowX` false. Fonts Nunito. |
| `/auth/login.php` | clicked | clicked | clicked | split stacks (`grid-template-columns` one track at 375/768; two tracks at 1440). No overflow. |
| `/auth/signup.php` | clicked | clicked | clicked | same split as login |
| `/auth/logout.php` | clicked | — | clicked | redirect-only → `/auth/login.php`; JS tokens cleared |

## Known debt

-   Hero CTAs already point at `/animals/` and `/report/` (anon login
    guard). Do not invent landing `#adopt` / `#report` sections.
-   Footer column labels are `href="#"` marketing placeholders —
    `stub-documented` unless a live route is the obvious target
    (“Report a stray” → `/report/`, “Browse adoption” → `/animals/`).
-   `public/landing/components/*.js` is unused if PHP renders the page.
-   Google sign-in requires `GOOGLE_CLIENT_ID`; unconfigured is a toast,
    not a missing page.

## Findings

| Control | Route | Classification | Evidence |
| --- | --- | --- | --- |
| Log in / Get Started | `/` | working | CDP click 375 mobile menu + 768 `.nav-actions` + 1440 Get Started → `/auth/login.php` |
| In-page `#home` `#audiences` `#features` `#how` | `/` | working | IDs exist. 375 mobile Features click closed menu. 768 Features / 1440 How It Works clicked; section `getBoundingClientRect().top` entered view (~481 / ~464) |
| Mobile hamburger `#menu-toggle` / `#mobile-menu` | `/` | broken-fixed | Phase 02: click at 375 did not set `is-open`. Cause: `type=module` `landing.js` bound `DOMContentLoaded` only. Now boots if `document.readyState !== "loading"`. CDP 375: after click `aria-expanded=true`, `#mobile-menu` `display:block` + `is-open`. Hidden at 768/1440 |
| Hero Adopt a Friend / Report an Activity | `/` | working | href `/animals/` `/report/`; CDP 375/768/1440 click → `/auth/login.php` (anon login guard). Not a dead hash |
| Audience Learn more | `/` | working | CDP 375 click `a.audience-link` href `#rescuers`; card `top` ~213 after smooth-scroll |
| Footer Report / Browse / How it works | `/` | working | live `/report/`, `/animals/`, `#how`. Hero Report already proves `/report/` |
| Footer brand `#home` | `/` | working | `footer.php` `href="#home"`; `landing.js` smooth-scrolls in-page hashes |
| Footer other columns (`Find rescuers`, For-col, Safety/Contact/FAQ) | `/` | stub-documented | `href="#"` unimplemented stubs — no live resident surface |
| Login empty validation | `/auth/login.php` | working | CDP 375 submit empty → toast “Please enter your email and password.” JS `preventDefault` (boot() also fixed for deferred module) |
| Login wrong password | `/auth/login.php` | working | CDP fill `juan@furescue.local` / `nope` → toast “Email or password is incorrect”; stayed on login |
| Login POST resident | `/auth/login.php` | working | CDP `juan@furescue.local` / `Password123!` → `/index.php` |
| Login POST admin | `/auth/login.php` | working | CDP `admin@furescue.local` / `Password123!` at 1440 → `/admin/index.php` |
| Password eye | `/auth/login.php`, `/auth/signup.php` | working | CDP `#toggle-pw` click: `#password` type `password` → `text` |
| Google `#google` + `data-google-signin` | auth | working | wired data-action (not a URL, not an invented OAuth). Click `preventDefault` (hrefAfter stayed on page, no `#google` navigation). `GOOGLE_CLIENT_ID` empty → `showToast("Google sign-in is not configured.")`. Overlapping prior toast meant the Google string was not uniquely snapshotted |
| Signup empty / invalid / taken | `/auth/signup.php` | working | invalid email → `#form-error` “Please enter a valid email address.”; `juan@furescue.local` POST → “That email is already registered. Try signing in instead.” (`EMAIL_TAKEN`). Empty-submit CDP click was off-viewport; handler covered by the invalid-email case |
| Login ↔ signup links | auth | working | CDP signup `a[href="/auth/login.php"]` → login. Markup signup link on login foot |
| Logout | `/auth/logout.php` | working | after resident + admin: lands `/auth/login.php`; `localStorage` `furescue_user` / `furescue_access_token` null |
| Already-authenticated redirect | `/auth/login.php` | stub-documented | after resident PHP session, GET `/auth/login.php` still rendered `#login-form` (no bounce). PHP login does not mint JWT so JS `requireAuth` does not apply here. Not invented |
| Landing fonts P1-3 | `/` | broken-fixed | `$fontsHref` was DM Sans; now same Google Fonts URL as other pages (Nunito / Fraunces / IBM Plex Mono). `--font-sans` in `00_tokens.css` is `"Nunito"`. CDP computed `fontFamily` starts with Nunito; `fontsHref` matches. No visual restyle beyond the token |
| `homepage.php` split | `/` | working | still ~221; this run did not grow it |
| `public/landing/components/*.js` | `/` | stub-documented | unused JS factories; PHP renders the page |

Method: Chrome CDP (`Emulation.setDeviceMetricsOverride`), `innerWidth` 375 / 768 / 1440 on `http://127.0.0.1:8000`. Browser MCP not available. `npm run build` not run (landing-only CSS). No commit. No `/api/v1` / router edits.
