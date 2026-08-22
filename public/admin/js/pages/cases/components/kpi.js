import { state } from "../state.js";
import { Chart } from "chart.js";
import { createIcons, icons } from "lucide";
import { Select } from "../../../../../js/components/ui/select.js";
import { esc } from "./util.js";
import { shortId, timeAgo, titleCase, initials } from "../../dashboard/helpers.js";

const STATUS_COLORS = {
  open: "hsl(211, 71%, 38%)",
  assigned: "hsl(211, 71%, 38%)",
  in_progress: "hsl(199, 74%, 53%)",
  resolved: "hsl(215, 16%, 47%)",
};

const STATUS_PALETTE = {
  open: "hsl(211, 71%, 38%)",
  assigned: "hsla(211, 71%, 38%, 0.65)",
  in_progress: "hsl(199, 74%, 53%)",
  resolved: "hsl(215, 16%, 47%)",
};

export function caseCounts() {
  const count = (s) => state.cases.filter((c) => c.status === s).length;
  return {
    all: state.cases.length,
    open: count("open"),
    assigned: count("assigned"),
    in_progress: count("in_progress"),
    resolved: count("resolved"),
  };
}

export function buildKpis() {
  const c = caseCounts();
  return [
    { icon: "clipboard-list", value: c.all, label: "Total cases", note: null, desc: "Every case in the system, all statuses included." },
    { icon: "folder-open", value: c.open, label: "Open", note: c.open ? { text: "Intake", cls: "kpi-note--coral" } : null, desc: "Newly reported cases not yet assigned to a rescuer." },
    { icon: "user-plus", value: c.assigned, label: "Assigned", note: null, desc: "Assigned to a rescuer, awaiting their acceptance." },
    { icon: "activity", value: c.in_progress, label: "In progress", dark: true, desc: "Rescues that are actively underway." },
    { icon: "check-circle-2", value: c.resolved, label: "Resolved", note: null, desc: "Cases successfully completed and closed." },
  ];
}

export function KpiTile(k) {
  const note = k.note
    ? `<span class="kpi-note ${k.note.cls}">${k.note.icon ? `<i data-lucide="${k.note.icon}"></i>` : ""}${k.note.text}</span>`
    : "";
  return `
  <div class="kpi-tile${k.dark ? " kpi-tile--dark" : ""}">
    <div class="kpi-top">
      <div class="kpi-icon"><i data-lucide="${k.icon}"></i></div>
      ${note}
    </div>
    <div class="kpi-value">${k.value}</div>
    <div class="kpi-label">${k.label}</div>
    <div class="kpi-desc">${esc(k.desc)}</div>
  </div>`;
}

export function KpiStrip() {
  const kpis = buildKpis().map(KpiTile).join("");
  return `
    <div class="kpi-grid">${kpis}</div>
    <div class="kpi-donut" id="kpi-donut-card">${StatusChart()}</div>`;
}

export const CASE_FILTERS = [
  { key: "all", label: "All" },
  { key: "open", label: "Open" },
  { key: "assigned", label: "Assigned" },
  { key: "in_progress", label: "In Progress" },
  { key: "resolved", label: "Resolved" },
];

export function CaseFilterTabs() {
  const c = caseCounts();
  const count = {
    all: c.all,
    open: c.open,
    assigned: c.assigned,
    in_progress: c.in_progress,
    resolved: c.resolved,
  };
  return `
  <div class="q-tabs" id="case-tabs">
    ${CASE_FILTERS.map(
      (f) => `<button data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
    ).join("")}
  </div>`;
}

export function CaseToolbar() {
  return `
  <div class="report-toolbar">
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="case-search" type="text" placeholder="Search case #, barangay, animal…" value="${esc(state.query)}">
    </div>
    <div class="report-sort">
      ${Select({
        id: "case-sort",
        options: [
          { value: "", label: "Sort" },
          { value: "newest", label: "Newest" },
          { value: "status", label: "Status" },
          { value: "updated", label: "Updated" },
        ],
        value: state.sort,
        placeholder: "Sort",
        className: "report-sort-control",
      })}
    </div>
  </div>`;
}

function StatusChart() {
  const c = caseCounts();
  const legend = [
    { label: "Open", val: c.open, cls: "status-seg--open" },
    { label: "Assigned", val: c.assigned, cls: "status-seg--assigned" },
    { label: "In progress", val: c.in_progress, cls: "status-seg--live" },
    { label: "Resolved", val: c.resolved, cls: "status-seg--resolved" },
  ]
    .map((s) => `<span class="status-legend-item"><span class="status-dot ${s.cls}"></span>${s.label} &middot; ${s.val}</span>`)
    .join("");
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="pie-chart"></i><h2 class="panel-title panel-title--sm">Case status breakdown</h2></div>
    <div class="donut-wrap">
      <div class="donut">
        <canvas id="status-donut"></canvas>
        <div class="donut-center"><span class="donut-total">${c.all}</span><span class="donut-label">Cases</span></div>
      </div>
      <div class="status-legend">${legend}</div>
    </div>
  </div>`;
}

let statusDonutInstance = null;

function mountStatusDonut() {
  const canvas = document.getElementById("status-donut");
  if (!canvas) return;
  if (statusDonutInstance) {
    statusDonutInstance.destroy();
    statusDonutInstance = null;
  }
  const c = caseCounts();
  statusDonutInstance = new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: ["Open", "Assigned", "In progress", "Resolved"],
      datasets: [
        {
          data: [c.open, c.assigned, c.in_progress, c.resolved],
          backgroundColor: [STATUS_PALETTE.open, STATUS_PALETTE.assigned, STATUS_PALETTE.in_progress, STATUS_PALETTE.resolved],
          borderColor: "#fff",
          borderWidth: 2,
          hoverOffset: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: {
        legend: { display: false },
        tooltip: { enabled: true },
      },
    },
  });
}

export function renderStatusBreakdown() {
  const wrap = document.getElementById("kpi-donut-card");
  if (wrap) {
    wrap.innerHTML = StatusChart();
    createIcons({ icons });
    mountStatusDonut();
  }
}
