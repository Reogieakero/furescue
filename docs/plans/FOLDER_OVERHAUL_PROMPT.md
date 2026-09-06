# Folder overhaul — Cursor prompt (Multitask)

Paste the block below into a **new Cursor Agent chat** on the **FurEscue** repo. Turn **Multitask Mode** on so the lead can launch parallel subagents.

Do not paste this into PGSO. Do not start until the two spec files exist (they do).

---

```
Role: You are the lead senior platform engineer (10+ years PHP + CSS architecture). You reorganize large frontends without changing behavior. You prefer boring moves and shims over clever rewrites.

## Objective
Reorganize FurEscue folders, CSS, and JS to the PGSO-shaped tree in the specs. Retain every URL, screen, API, token value, and auth behavior. Folder architecture only.

## Read first (mandatory)
1. docs/technical/FOLDER_ARCHITECTURE.md
2. docs/technical/FOLDER_OVERHAUL_SPEC.md
3. AGENTS.md
4. docs/study/DESIGN_SYSTEM.md

Trust those files over docs/technical/ARCHITECTURE_AUDIT.md (stale backend/frontend tree).

Sibling reference (read-only, do not edit): C:/Users/nicol/OneDrive/Documents/reagan/pgso
Especially: public/assets/css/app.css (locked cascade), views/, docs/06-architecture.md.
Copy folder discipline. NEVER copy PGSO navy/yellow or drop Tailwind.

## Context
- Stack: vanilla PHP 8.1+ REST API + PHP pages + ES-module islands + Tailwind CLI + Lucide + Leaflet.
- Server: php -S 127.0.0.1:8000 -t public public\index.php
- public/index.php already serves real files, dir/index.php, /uploads/, then /api/v1.
- Pain: CSS monolith (public/css/input.css ~1700 lines) + admin/landing/auth partial trees; JS tunnels like public/admin/reports/js/pages/reports/workflow/events.js; leftover 302 stubs; markup in public/includes.
- Target: public/assets/css (tokens → base → brand → guest → landing → auth → components → shell → pages → responsive → print), public/assets/js/{lib,components,admin}, views/layouts + views/components + views/<module>, flattened page JS (no js/pages/).

## Scope
Work in: public/, views/ (create), package.json, tailwind.config.js, AGENTS.md, README.md, docs/technical/HOW_TO_RUN.md, docs/study/DESIGN_SYSTEM.md (path strings only).

Do NOT touch: src/ API behavior, migrations/, seeders/, tests/ (except if a test hardcodes a moved frontend path), dbtool/, .env, public/uploads/ contents, visual token values.

## Constraints
- MUST keep every URL in FOLDER_OVERHAUL_SPEC.md §4.
- MUST keep leftover 302 stubs until grep shows no inbound links; then stop and ask before delete.
- MUST keep Tailwind. After CSS moves run npm run build. Update tailwind content globs to public/** and views/**.
- MUST use absolute imports for shared JS (/assets/js/...) and relative imports for page-local JS.
- MUST leave temporary re-exports at /js/lib and /css/style.css until all callers are rewritten.
- MUST NOT restyle, add features, add dependencies, or rewrite page SQL into controllers.
- MUST NOT introduce React/Vue/Alpine or a second CSS framework.
- One concern per file. Split past ~300 lines. No new monoliths.
- UI that you touch: verify 375 / 768 / 1440. Zero overflow at 375px.
- Only make changes the specs request.

## Multitask / subagents
You are the lead. Use parallel subagents for independent workstreams in FOLDER_OVERHAUL_SPEC.md §6.

Batch 1 (parallel): W1 CSS cascade + W2 shared JS. You publish the locked paths (already in the spec) and resolve conflicts.

Batch 2: W3 views/layouts/includes — ONE agent (every page requires includes). Do not parallelize W3 against W4/W5.

Batch 3 (parallel after W3): W4a dashboard, W4b reports/cases/rescuers, W4c animals/health, W4d listings/applications, W4e elearning/messages/notifications/analytics, W5 resident+auth+landing.

Batch 4: W6 docs/stubs + W7 verify (you or one verifier).

Each subagent prompt MUST include: workstream ID, files it owns, files it must not touch, URL freeze pointer, "move + rewrite imports only", and "done when that row of §7 checklist is true".

Cap: one subagent per workstream. Do not launch two agents on the same page folder. If a file is shared (site-head, input.css, admin-nav), only the owning workstream edits it (W1/W3/W0).

## Action boundaries
Proceed with reversible in-repo moves, import rewrites, and validation.
Stop and ask before: deleting stubs, adding dependencies, changing a public URL, moving uploads, or expanding into API/SQL refactors.

## Progress evidence
Ground every "done" in a tool result: file tree, rg output, npm run build, phpunit. Do not claim pages work from memory.

## Acceptance (stop editing when all are true)
- [ ] Tree matches FOLDER_ARCHITECTURE.md §4
- [ ] CSS cascade matches architecture §5; npm run build succeeds
- [ ] No public/**/js/pages/ left
- [ ] Shared JS under public/assets/js/{lib,components,admin}
- [ ] Every §4.1 URL still resolves; §4.2 stubs kept or deleted only after empty grep + user OK
- [ ] php vendor\phpunit\phpunit\phpunit passes
- [ ] rg for js/pages/, from "/js/lib/, admin/css/partials, includes/admin-shell.php is empty or shim-only
- [ ] AGENTS.md, README.md, HOW_TO_RUN.md, DESIGN_SYSTEM.md paths updated
- [ ] No visual redesign, no API change

## First actions
1. Confirm the two spec files and list leftover stubs in public/admin/*.php
2. Create views/ and public/assets/{css,js} skeletons without breaking the running site (shims first)
3. Launch Batch 1 subagents with bounded prompts
4. After each batch: rg old paths, npm run build if CSS changed, fix 404 imports before starting the next batch
```

This prompt is for an agentic tool with real system access. Review the scope locks, forbidden actions, and stop conditions before pasting. Confirm you are in the FurEscue repo, not PGSO.
