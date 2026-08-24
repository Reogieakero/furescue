import { AppShell } from "/admin/js/layout/app-shell.js";
import { createIcons, icons } from "lucide";
import { Button } from "/js/components/ui/button.js";
import { SkeletonCases } from "/js/components/ui/skeleton.js";
import { setNavBadge } from "/js/lib/swr.js";
import { state } from "../state.js";
import { KpiStrip, CaseFilterTabs, CaseToolbar, renderStatusBreakdown } from "./kpi.js";
import { CasePanel, CaseList, renderCaseList, initCaseSort } from "./list.js";
import { MapPanel, renderCaseMap } from "./map.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Rescue Management</span>
      <h1 class="page-title">Cases</h1>
      <p class="page-sub">Track active rescues, assign rescuers, and follow each case to resolution.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download" })}
    </div>
  </div>`;
}

export function CasesPage(user, { loading = false } = {}) {
  if (loading) {
    return AppShell({
      user,
      notifications: 0,
      badges: { cases: state.cases.length },
      activeNav: "cases",
      children: SkeletonCases(),
    });
  }
  return AppShell({
    user,
    notifications: 0,
    badges: { cases: state.cases.length },
    activeNav: "cases",
    children: [
      PageHead(),
      `<div id="case-kpis" class="case-kpis">${KpiStrip()}</div>`,
      `<div class="cols case-split">`,
      `<div class="case-list-col">${CasePanel()}</div>`,
      `<div class="case-map-col">${MapPanel()}</div>`,
      `</div>`,
    ].join(""),
  });
}

export function rerenderAll() {
  const kpis = document.getElementById("case-kpis");
  if (kpis) {
    kpis.innerHTML = KpiStrip();
  }
  const controls = document.getElementById("case-controls");
  if (controls) controls.innerHTML = CaseToolbar();
  const tabsWrap = document.getElementById("case-tabs-wrap");
  if (tabsWrap) tabsWrap.innerHTML = CaseFilterTabs();
  const badge = document.getElementById("case-total-badge");
  if (badge) badge.textContent = String(state.cases.length);
  renderCaseList();
  renderCaseMap();
  renderStatusBreakdown();
  initCaseSort();
  const navBadge = document.querySelector('.sidebar-link[href="/admin/cases/"] .sidebar-badge');
  if (navBadge) navBadge.textContent = String(state.cases.length);
  setNavBadge("cases", state.cases.length);
  createIcons({ icons });
}
