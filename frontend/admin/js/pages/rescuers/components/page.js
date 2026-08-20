import { createIcons, icons } from "lucide";
import { AppShell } from "../../../layout/app-shell.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { state } from "../state.js";
import { buildKpis, KpiTile, rescuerCounts } from "./kpis.js";
import { FilterTabs } from "./filters.js";
import { RescuerTable } from "./table.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Rescuers</h1>
      <p class="page-sub">Manage rescuers, duty status, and applications.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download" })}
    </div>
  </div>`;
}

function RescuersPanel() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="siren"></i>
        <h2 class="panel-title">${state.filter === "pending" ? "Applications" : "Rescuers"}</h2>
      </div>
    </div>
    <div id="rescuer-filters">${FilterTabs()}</div>
    <div id="rescuer-table" class="panel-body">${RescuerTable()}</div>
  </div>`;
}

export function RescuersPage(user) {
  const kpis = buildKpis().map(KpiTile).join("");
  return AppShell({
    user,
    notifications: 0,
    badges: { rescuers: rescuerCounts().pending },
    activeNav: "rescuers",
    children: [
      PageHead(),
      `<div id="rescuer-kpis" class="kpi-grid">${kpis}</div>`,
      RescuersPanel(),
    ].join(""),
  });
}

export function rerenderAll() {
  const kpis = document.getElementById("rescuer-kpis");
  if (kpis) kpis.innerHTML = buildKpis().map(KpiTile).join("");
  const filters = document.getElementById("rescuer-filters");
  if (filters) filters.innerHTML = FilterTabs();
  const table = document.getElementById("rescuer-table");
  if (table) table.innerHTML = RescuerTable();
  const navBadge = document.querySelector('.sidebar-link[data-nav="rescuers"] .sidebar-badge');
  if (navBadge) navBadge.textContent = rescuerCounts().pending;
  createIcons({ icons });
}
