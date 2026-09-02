import { createIcons, icons } from "lucide";
import { AppShell } from "/assets/js/admin/app-shell.js";
import { Button } from "/assets/js/components/ui/button.js";
import { Select } from "/assets/js/components/ui/select.js";
import { setNavBadge } from "/assets/js/lib/swr.js";
import { state, allAttentionCount } from "../state.js";
import { KpiStrip } from "./kpis.js";
import {
  DogVaccinationCard,
  CatVaccinationCard,
  TopConditionsPanel,
  TrendPanel,
  StackedPanel,
  mountCharts,
  destroyCharts,
} from "./charts.js";
import { RecordsPanel, FilterTabs } from "./table.js";
import { AttentionPanel } from "./queue.js";
import { esc } from "./util.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Animal Management</span>
      <h1 class="page-title">Health Records</h1>
      <p class="page-sub">Track vaccinations, checkups, conditions, and vitals across the shelter population.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Export CSV", variant: "outline", icon: "download", attrs: 'data-export="csv"' })}
      <button type="button" class="btn-see-animals" data-animals-open><i data-lucide="paw-print"></i><span>See animals</span></button>
    </div>
  </div>`;
}

function ControlsPanel() {
  return `
  <div class="panel hr-toolbar-panel">
    <div class="report-toolbar">
      <div class="q-tabs" id="hr-tabs">${FilterTabs()}</div>
      <div class="report-search">
        <i data-lucide="search"></i>
        <input id="hr-search" type="text" placeholder="Search animal, barangay, condition, vet, id…" value="${esc(state.query)}">
      </div>
      <div class="report-sort">
        <label for="hr-range" class="report-sort-label">Range</label>
        ${Select({
          id: "hr-range",
          options: [
            { value: "30d", label: "Last 30 days" },
            { value: "90d", label: "Last 90 days" },
            { value: "12mo", label: "Last 12 months" },
          ],
          value: state.range,
          placeholder: "Range",
        })}
      </div>
    </div>
  </div>`;
}

export function HealthRecordsPage(user) {
  const ATTENTION = allAttentionCount();
  return AppShell({
    user,
    notifications: 3,
    badges: { health: ATTENTION },
    activeNav: "health records",
    children: [
      `<div class="hr-list">
        ${PageHead()}
        <div id="hr-kpis">${KpiStrip()}</div>
        ${ControlsPanel()}
        <div class="cols cols--vax"><div id="hr-vax-dog"></div><div id="hr-vax-cat"></div><div id="hr-conditions"></div></div>
        <div id="hr-trend"></div>
        <div class="cols cols--two hr-split-row"><div id="hr-stacked"></div><div id="hr-queue"></div></div>
        <div id="hr-records"></div>
      </div>`,
    ].join(""),
  });
}

export function rerenderAll() {
  destroyCharts();

  const set = (id, html) => {
    const el = document.getElementById(id);
    if (el) el.innerHTML = html;
  };

  set("hr-kpis", KpiStrip());
  set("hr-vax-dog", DogVaccinationCard());
  set("hr-vax-cat", CatVaccinationCard());
  set("hr-conditions", TopConditionsPanel());
  set("hr-trend", TrendPanel());
  set("hr-stacked", StackedPanel());
  set("hr-queue", AttentionPanel());
  set("hr-records", RecordsPanel());

  createIcons({ icons });
  mountCharts();

  const attention = allAttentionCount();
  const navBadge = document.querySelector('.sidebar-link[href="/admin/health-records/"] .sidebar-badge');
  if (navBadge) navBadge.textContent = String(attention);
  setNavBadge("health", attention);
}
