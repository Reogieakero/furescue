import { Chart } from "chart.js";
import {
  state,
  vaccinationBreakdown,
  vaccinationBreakdownForSpecies,
  activitySeries,
  healthByBarangay,
  topConditions,
} from "../state.js";
import { esc } from "./util.js";

// Reference vaccination schedules shown in the species vaccination cards.
// These are static medical reference lists, not population data.
const DOG_VACCINES = {
  core: ["DHPP / DAPP", "Rabies", "Leptospirosis"],
  nonCore: ["Bordetella", "Canine Influenza", "Lyme"],
};
const CAT_VACCINES = {
  core: ["FVRCP", "Rabies", "FeLV (Feline Leukemia Virus)"],
  nonCore: ["Chlamydia felis", "Bordetella"],
};

const C = {
  complete: "hsl(199, 74%, 53%)",
  partial: "hsl(206, 50%, 53%)",
  none: "hsl(215, 16%, 47%)",
  jungle: "hsl(213, 68%, 25%)",
  coral: "hsl(211, 71%, 38%)",
  teal: "hsl(206, 50%, 53%)",
  critical: "hsl(0, 72%, 51%)",
  grid: "rgba(11, 37, 69, 0.08)",
  tick: "rgba(11, 37, 69, 0.45)",
  band: "hsla(199, 74%, 53%, 0.16)",
};

const FONT = '"IBM Plex Mono", ui-monospace, monospace';

Chart.defaults.font.family = FONT;
Chart.defaults.font.size = 10;
Chart.defaults.color = C.tick;

const charts = { donutDog: null, donutCat: null, trend: null, stacked: null };

export function destroyCharts() {
  Object.keys(charts).forEach((k) => {
    if (charts[k]) {
      charts[k].destroy();
      charts[k] = null;
    }
  });
}

// ---- Species vaccination cards (dog + cat donuts) ---------------------

function vaxLegend(b) {
  return [
    { label: "Complete", val: b.complete, cls: "status-seg--complete" },
    { label: "Partial", val: b.partial, cls: "status-seg--partial" },
    { label: "Not vaccinated", val: b.none, cls: "status-seg--none" },
  ]
    .map(
      (s) =>
        `<span class="status-legend-item"><span class="status-dot ${s.cls}"></span>${s.label} &middot; ${s.val}</span>`
    )
    .join("");
}

function vaxList(v) {
  const group = (label, items) => `
    <div class="hr-vax-group">
      <span class="hr-vax-group-label">${label}</span>
      <ul class="hr-vax-items">${items.map((i) => `<li>${esc(i)}</li>`).join("")}</ul>
    </div>`;
  return `<div class="hr-vax-list">${group("Core", v.core)}${group("Non-core", v.nonCore)}</div>`;
}

function SpeciesVaccinationCard(species, vaccines, title, icon, canvasId) {
  const b = vaccinationBreakdownForSpecies(species);
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="${icon}"></i><h2 class="panel-title panel-title--sm">${title}</h2></div>
    <div class="donut-wrap">
      <div class="donut">
        <canvas id="${canvasId}"></canvas>
        <div class="donut-center"><span class="donut-total">${b.total}</span><span class="donut-label">${species === "dog" ? "Dogs" : "Cats"}</span></div>
      </div>
      <div class="status-legend">${vaxLegend(b)}</div>
    </div>
    ${vaxList(vaccines)}
  </div>`;
}

export function DogVaccinationCard() {
  return SpeciesVaccinationCard("dog", DOG_VACCINES, "Dog vaccinations", "dog", "hr-donut-dog");
}

export function CatVaccinationCard() {
  return SpeciesVaccinationCard("cat", CAT_VACCINES, "Cat vaccinations", "cat", "hr-donut-cat");
}

function mountSpeciesDonut(canvasId, species) {
  const canvas = document.getElementById(canvasId);
  if (!canvas) return null;
  const b = vaccinationBreakdownForSpecies(species);
  return new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: ["Complete", "Partial", "Not vaccinated"],
      datasets: [
        {
          data: [b.complete, b.partial, b.none],
          backgroundColor: [C.complete, C.partial, C.none],
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
      plugins: { legend: { display: false }, tooltip: { enabled: true } },
    },
  });
}

// ---- Checkups & treatments trend --------------------------------------

export function TrendPanel() {
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="activity"></i><h2 class="panel-title panel-title--sm">Checkups &amp; treatments</h2></div>
    <div class="hr-chart"><canvas id="hr-trend-canvas"></canvas></div>
  </div>`;
}

function mountTrend() {
  const canvas = document.getElementById("hr-trend-canvas");
  if (!canvas) return;
  const s = activitySeries(state.range);
  const mk = (label, color, data, alpha) => ({
    label,
    data,
    borderColor: color,
    backgroundColor: color.replace("hsl", "hsla").replace(")", `, ${alpha})`),
    borderWidth: 2,
    tension: 0.35,
    fill: true,
    pointRadius: 0,
    pointHoverRadius: 4,
  });
  charts.trend = new Chart(canvas, {
    type: "line",
    data: {
      labels: s.labels,
      datasets: [
        mk("Checkups", C.coral, s.checkups, 0.12),
        mk("Treatments", C.teal, s.treatments, 0.12),
        mk("Vaccinations", C.complete, s.vaccinations, 0.12),
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      plugins: {
        legend: { display: true, labels: { boxWidth: 10, font: { family: FONT, size: 10 } } },
        tooltip: { enabled: true },
      },
      scales: {
        x: { grid: { color: C.grid }, ticks: { color: C.tick, maxRotation: 0, autoSkip: true, maxTicksLimit: 12 } },
        y: { beginAtZero: true, grid: { color: C.grid }, ticks: { color: C.tick } },
      },
    },
  });
}

// ---- Health status by barangay (stacked) ------------------------------

export function StackedPanel() {
  const toggle = ["all", "dog", "cat"]
    .map(
      (s) =>
        `<button class="hr-toggle-btn${state.species === s ? " is-active" : ""}" data-species="${s}">${
          s === "all" ? "All" : s === "dog" ? "Dogs" : "Cats"
        }</button>`
    )
    .join("");
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Health by barangay</h2></div>
    <div class="report-sort" style="margin:8px 0 12px;"><span class="hr-toggle">${toggle}</span></div>
    <div class="hr-chart"><canvas id="hr-stacked-canvas"></canvas></div>
  </div>`;
}

function mountStacked() {
  const canvas = document.getElementById("hr-stacked-canvas");
  if (!canvas) return;
  const d = healthByBarangay();
  charts.stacked = new Chart(canvas, {
    type: "bar",
    data: {
      labels: d.labels,
      datasets: [
        { label: "Healthy", data: d.healthy, backgroundColor: C.complete, borderRadius: 2, barPercentage: 0.7 },
        { label: "Under treatment", data: d.treatment, backgroundColor: C.teal, borderRadius: 2, barPercentage: 0.7 },
        { label: "Critical", data: d.critical, backgroundColor: C.critical, borderRadius: 2, barPercentage: 0.7 },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      interaction: { mode: "index", intersect: false },
      plugins: {
        legend: { display: true, labels: { boxWidth: 10, font: { family: FONT, size: 10 } } },
        tooltip: { enabled: true },
      },
      scales: {
        x: { stacked: true, grid: { display: false }, ticks: { color: C.tick, maxRotation: 45, minRotation: 45 } },
        y: { stacked: true, beginAtZero: true, grid: { color: C.grid }, ticks: { color: C.tick } },
      },
    },
  });
}

const CONDITION_COLORS = {
  Healthy: "hsl(152, 64%, 42%)",
  Mange: "hsl(28, 90%, 55%)",
  Malnutrition: "hsl(40, 92%, 50%)",
  Fracture: "hsl(0, 72%, 51%)",
  Parvovirus: "hsl(280, 60%, 55%)",
  "Tick fever": "hsl(199, 74%, 53%)",
  "Respiratory infection": "hsl(211, 71%, 48%)",
  "Wound care": "hsl(14, 78%, 55%)",
};

export function TopConditionsPanel() {
  const { entries, max } = topConditions();
  const rows = entries.length
    ? entries
        .map(([label, val]) => {
          const pct = max ? Math.round((val / max) * 100) : 0;
          const color = CONDITION_COLORS[label] || "hsl(199, 74%, 53%)";
          return `
        <div class="hr-cond-row">
          <span class="hr-cond-label">${esc(label)}</span>
          <span class="hr-cond-val">${val}</span>
          <span class="hr-cond-track"><span class="hr-cond-bar" style="width:${pct}%;background:${color}"></span></span>
        </div>`;
        })
        .join("")
    : `<div class="empty-state"><i data-lucide="check-circle-2"></i><span>No conditions to summarise.</span></div>`;
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="stethoscope"></i><h2 class="panel-title panel-title--sm">Top conditions</h2></div>
    <div class="hr-cond-list">${rows}</div>
  </div>`;
}

export function mountCharts() {
  charts.donutDog = mountSpeciesDonut("hr-donut-dog", "dog");
  charts.donutCat = mountSpeciesDonut("hr-donut-cat", "cat");
  mountTrend();
  mountStacked();
}
