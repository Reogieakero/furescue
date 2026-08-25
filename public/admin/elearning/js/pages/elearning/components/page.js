import { createIcons, icons } from "lucide";
import { AppShell } from "/admin/js/layout/app-shell.js";
import { Button } from "/js/components/ui/button.js";
import { SkeletonTable } from "/js/components/ui/skeleton.js";
import { state } from "../state.js";
import { buildKpis, KpiTile } from "./kpis.js";
import { FilterTabs } from "./filters.js";
import { LibraryBody } from "./table.js";
import { Editor } from "./editor.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Content</span>
      <h1 class="page-title">E-Learning</h1>
      <p class="page-sub">Author lessons for the resident Learning Hub. Drafts stay private until you publish.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "New module", variant: "default", icon: "plus", attrs: 'data-action="new"' })}
    </div>
  </div>`;
}

function LibraryPanel() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="book-open"></i>
        <h2 class="panel-title">Module library</h2>
      </div>
    </div>
    <div id="elearn-filters">${FilterTabs()}</div>
    <div id="elearn-table" class="panel-body">${LibraryBody()}</div>
  </div>`;
}

export function libraryInner() {
  const kpis = buildKpis().map(KpiTile).join("");
  return `${PageHead()}
      <div id="elearn-kpis" class="kpi-grid">${kpis}</div>
      ${LibraryPanel()}`;
}

export function ElearningPage(user, { loading = false } = {}) {
  if (loading) {
    return AppShell({
      user,
      notifications: 0,
      badges: {},
      activeNav: "e-learning",
      children: `<div class="elearn-page">${PageHead()}<div class="panel"><div class="panel-body">${SkeletonTable({ rows: 6, cols: 5 })}</div></div></div>`,
    });
  }
  const editorOpen = state.view === "editor";
  return AppShell({
    user,
    notifications: 0,
    badges: {},
    activeNav: "e-learning",
    children: `
    <div class="elearn-page">
      <div id="elearn-library"${editorOpen ? ' class="is-hidden"' : ""}>${libraryInner()}</div>
      <div id="elearn-editor"${editorOpen ? "" : ' class="is-hidden"'}>${editorOpen ? Editor() : ""}</div>
    </div>`,
  });
}

export function rerenderLibrary() {
  const kpis = document.getElementById("elearn-kpis");
  if (kpis) kpis.innerHTML = buildKpis().map(KpiTile).join("");
  const filters = document.getElementById("elearn-filters");
  if (filters) filters.innerHTML = FilterTabs();
  const table = document.getElementById("elearn-table");
  if (table) table.innerHTML = LibraryBody();
  createIcons({ icons });
}

export function rerenderAll() {
  const library = document.getElementById("elearn-library");
  const editor = document.getElementById("elearn-editor");
  if (!library || !editor) return;
  if (state.view === "editor") {
    library.classList.add("is-hidden");
    editor.classList.remove("is-hidden");
    editor.innerHTML = Editor();
  } else {
    editor.classList.add("is-hidden");
    editor.innerHTML = "";
    library.classList.remove("is-hidden");
    if (!library.childElementCount) library.innerHTML = libraryInner();
    rerenderLibrary();
  }
  createIcons({ icons });
}
