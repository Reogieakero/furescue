import { createIcons, icons } from "lucide";
import { AppShell } from "/assets/js/admin/app-shell.js";
import { Button } from "/shared/components/button/button.js";
import { SkeletonRescuers } from "/shared/components/skeleton/skeleton.js";
import { setNavBadge } from "/assets/js/lib/swr.js";
import { state } from "../state.js";
import { KpiGrid } from "/shared/components/kpi-card/kpi-card.js";
import { buildKpis, KpiTile, rescuerCounts, toKpiCardProps } from "./kpis.js";
import { FilterTabs, FilterToolbar } from "./filters.js";
import { RescuerTable } from "./table.js";
import { RescuerDetail } from "./detail.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Rescuers</h1>
      <p class="page-sub">Manage rescuers, duty status, and applications.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download", attrs: 'data-export="csv"' })}
    </div>
  </div>`;
}

function RescuersPanel() {
  return `
  <div class="panel rescuer-record-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="siren"></i>
        <h2 class="panel-title">${state.filter === "pending" ? "Applications" : "Rescuers"}</h2>
      </div>
      <div class="panel-head-tools">
        <div id="rescuer-tabs-wrap">${FilterTabs()}</div>
      </div>
    </div>
    <div id="rescuer-filters">${FilterToolbar()}</div>
    <div id="rescuer-table" class="panel-body">${RescuerTable()}</div>
  </div>`;
}

export function RescuersPage(user, { loading = false } = {}) {
  if (loading) {
    return AppShell({
      user,
      notifications: 0,
      badges: { rescuers: rescuerCounts().pending },
      activeNav: "rescuers",
      children: SkeletonRescuers(),
    });
  }
  return AppShell({
    user,
    notifications: 0,
    badges: { rescuers: rescuerCounts().pending },
    activeNav: "rescuers",
    children: [
      PageHead(),
      KpiGrid({ id: "rescuer-kpis", items: buildKpis().map(toKpiCardProps) }),
      `<div class="rescuer-split">
        ${RescuersPanel()}
        <div id="rescuer-detail" class="panel rescuer-detail-panel">${RescuerDetail()}</div>
      </div>`,
    ].join(""),
  });
}

export function rerenderAll() {
  const kpis = document.getElementById("rescuer-kpis");
  if (kpis) kpis.innerHTML = buildKpis().map(KpiTile).join("");
  const tabs = document.getElementById("rescuer-tabs-wrap");
  if (tabs) tabs.innerHTML = FilterTabs();
  const table = document.getElementById("rescuer-table");
  if (table) table.innerHTML = RescuerTable();
  const navBadge = document.querySelector('.sidebar-link[href="/admin/rescuers/"] .sidebar-badge');
  if (navBadge) navBadge.textContent = rescuerCounts().pending;
  setNavBadge("rescuers", rescuerCounts().pending);
  createIcons({ icons });
}
