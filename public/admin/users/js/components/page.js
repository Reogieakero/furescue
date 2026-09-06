import { createIcons, icons } from "lucide";
import { AppShell } from "/assets/js/admin/app-shell.js";
import { Button } from "/shared/components/button/button.js";
import { state } from "../state.js";
import { buildKpis, KpiTile } from "./kpis.js";
import { FilterTabs } from "./filters.js";
import { UserTable } from "./table.js";
import { panelTitle } from "./util.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--accent">System</span>
      <h1 class="page-title">Users</h1>
      <p class="page-sub">Create accounts, change roles, and suspend or remove access.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download", attrs: 'data-export="csv"' })}
      ${Button({ text: "Create user", variant: "default", icon: "user-plus", attrs: 'data-action="create"' })}
    </div>
  </div>`;
}

function UsersPanel() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="users"></i>
        <h2 class="panel-title" id="user-panel-title">${panelTitle(state.filter)}</h2>
      </div>
    </div>
    <div id="user-filters">${FilterTabs()}</div>
    <div id="user-table" class="panel-body">${UserTable()}</div>
  </div>`;
}

export function UsersPage(user) {
  const kpis = buildKpis().map(KpiTile).join("");
  return AppShell({
    user,
    notifications: 0,
    badges: {},
    activeNav: "users",
    children: [PageHead(), `<div id="user-kpis" class="kpi-grid">${kpis}</div>`, UsersPanel()].join(""),
  });
}

export function rerenderAll() {
  const kpis = document.getElementById("user-kpis");
  if (kpis) kpis.innerHTML = buildKpis().map(KpiTile).join("");
  const title = document.getElementById("user-panel-title");
  if (title) title.textContent = panelTitle(state.filter);
  const filters = document.getElementById("user-filters");
  if (filters) filters.innerHTML = FilterTabs();
  const table = document.getElementById("user-table");
  if (table) table.innerHTML = UserTable();
  createIcons({ icons });
}
