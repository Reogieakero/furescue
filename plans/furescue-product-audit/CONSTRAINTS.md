# Constraints (hard locks)

Read this before editing. The paste-ready master prompt is `PROMPT.md`.

## Allowed

- Owned `public/` files per the agent ownership map in `00-master-plan.md`
- Pack files under `plans/furescue-product-audit/`
- `public/css/input.css` + `tailwind.config.js` only in Phase 02 or 90 (serialized)
- Shared chrome (`admin-shell`, `resident-shell`, `admin-nav`, `public/js/components/ui/*`, `public/admin/js/layout/*`) only in Phase 02 or 90
- Start `php -S 127.0.0.1:8000 -t public public\index.php` if needed
- `npm run build` after CSS/token edits
- `php -l` on edited PHP
- Browser clicks at 375 / 768 / 1440

## Forbidden

- Commit, push, amend
- `/api/v1` contract, router, JSON envelope, or `src/Http/Routes/**` changes
- Visual redesign, new JS framework, new component library
- Building `/admin/listings/`, `/admin/applications/`, `/admin/elearning/`, `/admin/messages/`
- Editing shared chrome from page agents 10–13 / 20–26
- Agents 11–13 editing `resident-shell.php`
- Agents 20–26 editing `admin-nav.php` or `admin-shell.php`
- Growing a file already over ~300 lines
- Overwriting filled `#app` when `window.__PAGE_STATE__` is present
- Markdown outside this pack
- Deleting files, adding dependencies, or schema changes without asking

## Design system

Live tokens in `public/css/input.css` win over `docs/study/DESIGN_SYSTEM.md` hex and DM Sans. Lucide only. New reusable style goes into `input.css` first.

## Classification labels

Use exactly: `working` | `broken-fixed` | `stub-documented`.

`href="#"`: wired `data-action` | should-be-URL | unimplemented stub.
