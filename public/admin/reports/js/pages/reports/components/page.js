import { createIcons, icons } from "lucide";
import { AppShell } from "/admin/js/layout/app-shell.js";
import { Button } from "/js/components/ui/button.js";
import { SkeletonReports } from "/js/components/ui/skeleton.js";
import { setNavBadge } from "/js/lib/swr.js";
import { initSelect } from "/js/components/ui/select.js";
import { state } from "../state.js";
import { buildKpis, KpiTile } from "./kpis.js";
import { FilterTabs } from "./filters.js";
import { ReportTable } from "./table.js";
import { attachReportTooltips } from "./tooltips.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Reports</h1>
      <p class="page-sub">Verify reports, assign rescuers, and track the full case workflow.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download" })}
    </div>
  </div>`;
}

function ReportsPanel() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="map-pin"></i>
        <h2 class="panel-title">All reports</h2>
      </div>
    </div>
    <div id="report-filters">${FilterTabs()}</div>
    <div id="report-table" class="panel-body">${ReportTable()}</div>
  </div>`;
}

export function ReportsPage(user, { loading = false } = {}) {
  if (loading) {
    return AppShell({
      user,
      notifications: 0,
      badges: { reports: state.reports.length },
      activeNav: "reports",
      children: SkeletonReports(),
    });
  }
  const kpis = buildKpis().map(KpiTile).join("");
  return AppShell({
    user,
    notifications: 0,
    badges: { reports: state.reports.length },
    activeNav: "reports",
    children: [
      PageHead(),
      `<div id="report-kpis" class="kpi-grid">${kpis}</div>`,
      ReportsPanel(),
    ].join(""),
  });
}

export function initReportSort() {
  const el = document.getElementById("report-sort");
  if (!el || el.dataset.sortInit) return;
  el.dataset.sortInit = "1";
  initSelect(document.getElementById("report-filters") || document, {
    "report-sort": (val) => {
      if (state.sort === val) return;
      state.sort = val;
      state.page = 1;
      const table = document.getElementById("report-table");
      if (table) {
        table.innerHTML = ReportTable();
        createIcons({ icons });
        attachReportTooltips();
      }
    },
  });
}

export function rerenderAll() {
  const kpis = document.getElementById("report-kpis");
  if (kpis) kpis.innerHTML = buildKpis().map(KpiTile).join("");
  const filters = document.getElementById("report-filters");
  if (filters) filters.innerHTML = FilterTabs();
  const table = document.getElementById("report-table");
  if (table) table.innerHTML = ReportTable();
  attachReportTooltips();
  initReportSort();
  const navBadge = document.querySelector('.sidebar-link[href="/admin/reports/"] .sidebar-badge');
  if (navBadge) navBadge.textContent = state.reports.length;
  setNavBadge("reports", state.reports.length);
  createIcons({ icons });
}
