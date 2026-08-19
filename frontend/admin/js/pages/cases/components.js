// Cases page markup. Everything reads from ./state.js — no mock data.
import { createIcons, icons } from "lucide";
import Chart from "chart.js";
import { AppShell } from "../../layout/app-shell.js";
import { PaginationBar } from "../../../../js/components/ui/pagination.js";
import { Button } from "../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../js/components/ui/select.js";
import { openDrawer } from "../../../../js/components/ui/drawer.js";
import * as api from "../../lib/admin-data.js";
import { state } from "./state.js";
import { shortId, timeAgo, titleCase, initials } from "../dashboard/helpers.js";

const PAGE_SIZE = 6;

const MATI_CENTER = [6.95, 126.2];
const MATI_BOUNDS = [
  [6.85, 126.1],
  [7.08, 126.4],
];

const STATUS_COLORS = {
  open: "hsl(211, 71%, 38%)", // coral
  assigned: "hsl(211, 71%, 38%)", // coral
  in_progress: "hsl(199, 74%, 53%)", // jungle2
  resolved: "hsl(215, 16%, 47%)", // stamp
};

// Case map display mode: "pins" (default) or "heatmap".
let caseMapMode = "pins";

const HEAT_GRADIENT = {
  0.3: "hsl(199, 74%, 53%)", // jungle
  0.6: "hsl(211, 71%, 38%)", // coral
  1.0: "hsl(0, 84%, 60%)", // alert red
};

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function caseStampCls(status) {
  if (status === "in_progress" || status === "resolved") return "stamp--accent";
  return "stamp--coral"; // open, assigned
}

function caseCounts() {
  const count = (s) => state.cases.filter((c) => c.status === s).length;
  return {
    all: state.cases.length,
    open: count("open"),
    assigned: count("assigned"),
    in_progress: count("in_progress"),
    resolved: count("resolved"),
  };
}

export function getCase(id) {
  return state.cases.find((c) => c.id === id) || null;
}

function enrich(c) {
  const report = c.report_id ? state.reports.find((r) => r.id === c.report_id) : null;
  const rescuer = c.assigned_rescuer_id ? state.rescuers.find((u) => u.id === c.assigned_rescuer_id) : null;
  const status = String(c.status || "open");
  const lat = Number(c.latitude != null ? c.latitude : report && report.latitude);
  const lng = Number(c.longitude != null ? c.longitude : report && report.longitude);
  return {
    id: c.id,
    shortId: shortId(c.id),
    status: titleCase(status),
    statusCls: caseStampCls(status),
    statusRaw: status,
    report,
    rescuer,
    brgy: report ? report.address_text || "—" : "—",
    animal: report ? report.animal_description || "—" : "—",
    lat: Number.isFinite(lat) ? lat : null,
    lng: Number.isFinite(lng) ? lng : null,
    when: timeAgo(c.created_at),
    updated: timeAgo(c.updated_at || c.created_at),
    createdAt: c.created_at,
    updatedAt: c.updated_at || c.created_at,
  };
}

/* ---------- KPI row ---------- */

function buildKpis() {
  const c = caseCounts();
  return [
    { icon: "clipboard-list", value: c.all, label: "Total cases", note: null, desc: "Every case in the system, all statuses included." },
    { icon: "folder-open", value: c.open, label: "Open", note: c.open ? { text: "Intake", cls: "kpi-note--coral" } : null, desc: "Newly reported cases not yet assigned to a rescuer." },
    { icon: "user-plus", value: c.assigned, label: "Assigned", note: null, desc: "Assigned to a rescuer, awaiting their acceptance." },
    { icon: "activity", value: c.in_progress, label: "In progress", dark: true, desc: "Rescues that are actively underway." },
    { icon: "check-circle-2", value: c.resolved, label: "Resolved", note: null, desc: "Cases successfully completed and closed." },
  ];
}

function KpiTile(k) {
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

/* ---------- filters / tabs ---------- */

const CASE_FILTERS = [
  { key: "all", label: "All" },
  { key: "open", label: "Open" },
  { key: "assigned", label: "Assigned" },
  { key: "in_progress", label: "In Progress" },
  { key: "resolved", label: "Resolved" },
];

function CaseFilterTabs() {
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

function CaseToolbar() {
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

/* ---------- case cards ---------- */

function statusRank(c) {
  return { open: 1, assigned: 2, in_progress: 3, resolved: 4 }[c.statusRaw] ?? 4;
}

function sortKey(c) {
  if (state.sort === "status") return [statusRank(c), -new Date(c.createdAt).getTime()];
  if (state.sort === "updated") return [-new Date(c.updatedAt).getTime(), -new Date(c.createdAt).getTime()];
  return [-new Date(c.createdAt).getTime()];
}

function cmp(a, b) {
  for (let i = 0; i < a.length; i++) {
    if (a[i] < b[i]) return -1;
    if (a[i] > b[i]) return 1;
  }
  return 0;
}

function filteredCases() {
  const q = state.query.trim().toLowerCase();
  let list = state.cases.map(enrich);
  if (state.filter !== "all") list = list.filter((c) => c.statusRaw === state.filter);
  if (q) {
    list = list.filter((c) =>
      [c.shortId, c.brgy, c.animal, c.rescuer && c.rescuer.full_name]
        .filter(Boolean)
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  return list.sort((a, b) => cmp(sortKey(a), sortKey(b)));
}

function caseAction(c) {
  if (!c.rescuer && c.statusRaw === "open") {
    return Button({
      text: "Assign rescuer",
      variant: "default",
      size: "sm",
      icon: "user-plus",
      attrs: `data-action="assign" data-case="${c.id}" data-report="${c.report ? c.report.id : ""}"`,
    });
  }
  if (c.statusRaw === "assigned") {
    return `<span class="action-text">${esc("Waiting for rescuer to accept the assigned rescue")}</span>`;
  }
  if (c.statusRaw === "in_progress") {
    return `<span class="action-text">${esc("In progress")}</span>`;
  }
  return "";
}

function rescuerChip(rescuer) {
  if (!rescuer) return `<span class="case-card-unassigned">Unassigned</span>`;
  const initial = `<span class="table-avatar table-avatar--initial">${initials(rescuer.full_name)}</span>`;
  return `
    <span class="case-card-rescuer">
      ${initial}
      <span class="case-card-rescuer-name">${esc(rescuer.full_name)}</span>
    </span>`;
}

function CaseCard(c) {
  const live = c.statusRaw === "in_progress" ? " case-card--live" : "";
  return `
  <article class="case-card${live}" data-case-id="${esc(c.id)}">
    <div class="case-card-head">
      <span class="case-card-id">${c.shortId}</span>
      <span class="stamp stamp--sm ${c.statusCls}">${c.status}</span>
    </div>
    <div class="case-card-body">
      <div class="case-card-row"><i data-lucide="map-pin"></i><span>${esc(c.brgy)}</span></div>
      <div class="case-card-row"><i data-lucide="paw-print"></i><span>${esc(c.animal)}</span></div>
    </div>
    <div class="case-card-foot">
      ${rescuerChip(c.rescuer)}
      <span class="case-card-time">${c.statusRaw === "in_progress" ? `Updated ${c.updated}` : c.when}</span>
    </div>
    <div class="case-card-actions">${caseAction(c)}</div>
  </article>`;
}

export function CaseList() {
  const list = filteredCases();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="clipboard-list"></i><span>No cases match.</span></div></div>`;
  }
  const start = (state.page - 1) * PAGE_SIZE;
  const pageItems = list.slice(start, start + PAGE_SIZE);
  const cards = pageItems.map(CaseCard).join("");
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `<div class="case-grid">${cards}</div>${pagination}`;
}

export function renderCaseList() {
  const el = document.getElementById("case-list");
  if (el) {
    el.innerHTML = CaseList();
    createIcons({ icons });
  }
}

/* ---------- map ---------- */

let caseMapInstance = null;

export function renderCaseMap() {
  const el = document.getElementById("case-map");
  if (!el) return;
  if (caseMapInstance) {
    caseMapInstance.remove();
    caseMapInstance = null;
  }
  if (!window.L) return;

  const map = window.L.map(el, {
    center: MATI_CENTER,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: MATI_BOUNDS,
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  window.L
    .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    })
    .addTo(map);

  const all = state.cases.map(enrich).filter((c) => c.lat != null && c.lng != null);

  if (caseMapMode === "heatmap" && window.L.heatLayer) {
    const heatPoints = all.map((c) => [c.lat, c.lng, 1]);
    window.L.heatLayer(heatPoints, {
      radius: 25,
      blur: 15,
      maxZoom: 17,
      gradient: HEAT_GRADIENT,
    }).addTo(map);
  } else {
    all.forEach((c) => {
      const color = STATUS_COLORS[c.statusRaw] || STATUS_COLORS.open;
      const marker = window.L.circleMarker([c.lat, c.lng], {
        radius: 9,
        color: "#fff",
        weight: 2,
        fillColor: color,
        fillOpacity: 1,
      }).addTo(map);
      marker.bindPopup(`<strong>${esc(c.shortId)}</strong> &middot; ${esc(c.status)}<br>${esc(c.brgy)}`);
      marker.on("click", () => {
        const card = document.querySelector(`[data-case-id="${cssEscape(c.id)}"]`);
        if (card) {
          card.scrollIntoView({ behavior: "smooth", block: "center" });
          card.classList.add("is-highlight");
          setTimeout(() => card.classList.remove("is-highlight"), 1800);
        }
        openCaseDrawer(c.id);
      });
    });
  }

  const count = document.getElementById("case-map-count");
  if (count) count.textContent = String(all.length);

  window.setTimeout(() => map.invalidateSize(), 0);
  caseMapInstance = map;
}

function cssEscape(value) {
  return String(value).replace(/["\\]/g, "\\$&");
}

export function initCaseMapMode() {
  const wrap = document.getElementById("case-map-toggle");
  if (!wrap || wrap.dataset.modeInit) return;
  wrap.dataset.modeInit = "1";
  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-map-mode]");
    if (!btn) return;
    const mode = btn.dataset.mapMode;
    if (mode === caseMapMode) return;
    caseMapMode = mode;
    wrap.querySelectorAll("[data-map-mode]").forEach((b) => b.classList.toggle("is-active", b === btn));
    renderCaseMap();
    const label = document.getElementById("case-map-foot-label");
    if (label) {
      label.textContent =
        caseMapMode === "heatmap" ? "heat points · Density of reported cases" : "pinned cases · Click a pin for details";
    }
  });
}

/* ---------- status breakdown chart (shadcn-style donut, Chart.js) ---------- */

let statusDonutInstance = null;

const STATUS_PALETTE = {
  open: "hsl(211, 71%, 38%)",
  assigned: "hsla(211, 71%, 38%, 0.65)",
  in_progress: "hsl(199, 74%, 53%)",
  resolved: "hsl(215, 16%, 47%)",
};

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

/* ---------- drawer ---------- */

function locationSub(loc, name) {
  if (loc && loc.full && typeof name === "string" && loc.full.indexOf(name) === 0) {
    return loc.full.slice(name.length).replace(/^\s*,\s*/, "");
  }
  if (loc && loc.road) return loc.road;
  return "";
}

function infoRows(c) {
  const rows = [
    { label: "Case", value: c.shortId },
    { label: "Status", value: c.status },
    { label: "Barangay", value: c.brgy !== "—" ? titleCase(c.brgy) : "—" },
    { label: "Animal", value: c.animal },
    { label: "Rescuer", value: c.rescuer ? c.rescuer.full_name : "Unassigned" },
    { label: "Created", value: c.createdAt ? new Date(c.createdAt).toLocaleString() : "—" },
    { label: "Updated", value: c.updatedAt ? new Date(c.updatedAt).toLocaleString() : "—" },
  ];
  return rows
    .map(
      (row) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(row.label)}</span>
      <span class="dialog-info-value">${esc(row.value)}</span>
    </div>`
    )
    .join("");
}

export function openCaseDrawer(caseId) {
  const raw = getCase(caseId);
  if (!raw) return;
  const c = enrich(raw);
  const resolved = c.statusRaw === "resolved";
  const actions = [
    !resolved
      ? Button({
          text: "Reassign",
          variant: "outline",
          size: "sm",
          icon: "refresh-cw",
          attrs: `data-drawer-action="reassign" data-case="${c.id}" data-report="${c.report ? c.report.id : ""}"`,
        })
      : "",
    !resolved
      ? Button({
          text: "Resolve",
          variant: "default",
          size: "sm",
          icon: "check-circle-2",
          attrs: `data-drawer-action="resolve" data-case="${c.id}"`,
        })
      : "",
  ].join("");

  openDrawer({
    title: `Case ${c.shortId}`,
    body: `
      <div class="dialog-info">${infoRows(c)}</div>
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name" id="case-detail-location">Resolving location…</div>
          <div class="drawer-location-sub" id="case-detail-location-sub"></div>
        </div>
      </div>
      <div id="case-detail-map" class="drawer-map"></div>
      ${actions ? `<div class="drawer-foot-actions">${actions}</div>` : ""}`,
    onMount: (bodyEl) => {
      const lat = c.lat;
      const lng = c.lng;
      const mapEl = bodyEl.querySelector("#case-detail-map");
      let marker = null;
      if (window.L && mapEl && lat != null && lng != null) {
        const map = window.L.map(mapEl).setView([lat, lng], 15);
        window.L
          .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
          })
          .addTo(map);
        marker = window.L.marker([lat, lng]).addTo(map).bindPopup(esc(c.brgy || "Case location"));
        setTimeout(() => map.invalidateSize(), 300);
      }
      const locEl = bodyEl.querySelector("#case-detail-location");
      const subEl = bodyEl.querySelector("#case-detail-location-sub");
      const fallback = c.brgy !== "—" ? titleCase(c.brgy) : "Unknown location";
      if (lat != null && lng != null) {
        api.reverseGeocode(lat, lng).then((loc) => {
          const specific = loc && (loc.name || loc.road || loc.full);
          const sub = specific ? locationSub(loc, specific) : "";
          if (locEl) {
            locEl.textContent = specific || fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = sub || (specific ? "" : fallback);
          if (specific && marker) marker.setPopupContent(esc(specific));
        }).catch(() => {
          if (locEl) {
            locEl.textContent = fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = "";
        });
      } else if (locEl) {
        locEl.textContent = fallback;
        locEl.classList.remove("loc-loading");
      }
    },
  });
}

/* ---------- sort ---------- */

export function initCaseSort() {
  const el = document.getElementById("case-sort");
  if (!el || el.dataset.sortInit) return;
  el.dataset.sortInit = "1";
  initSelect(document.getElementById("case-controls") || document, {
    "case-sort": (val) => {
      if (state.sort === val) return;
      state.sort = val;
      state.page = 1;
      renderCaseList();
    },
  });
}

/* ---------- page ---------- */

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

function CasePanel() {
  return `
  <div class="panel case-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="clipboard-list"></i>
        <h2 class="panel-title">Cases</h2>
      </div>
      <div class="panel-head-tools">
        <div id="case-tabs-wrap">${CaseFilterTabs()}</div>
        <span class="stamp stamp--sm stamp--accent" id="case-total-badge">${state.cases.length}</span>
      </div>
    </div>
    <div id="case-controls">${CaseToolbar()}</div>
    <div id="case-list" class="panel-body">${CaseList()}</div>
  </div>`;
}

function MapPanel() {
  return `
  <div class="panel case-map-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="map"></i>
        <h2 class="panel-title">Case map &middot; City of Mati</h2>
      </div>
      <div class="map-tools">
        <div class="map-toggle" id="case-map-toggle" role="group" aria-label="Map display mode">
          <button type="button" class="map-toggle-btn${caseMapMode === "pins" ? " is-active" : ""}" data-map-mode="pins"><i data-lucide="map-pin"></i> Pins</button>
          <button type="button" class="map-toggle-btn${caseMapMode === "heatmap" ? " is-active" : ""}" data-map-mode="heatmap"><i data-lucide="flame"></i> Heatmap</button>
        </div>
      </div>
    </div>
    <div id="case-map" class="map-canvas map-canvas--leaflet case-map"></div>
    <div class="map-foot"><span id="case-map-count">0</span> <span id="case-map-foot-label">${caseMapMode === "heatmap" ? "heat points · Density of reported cases" : "pinned cases · Click a pin for details"}</span></div>
  </div>`;
}

function KpiStrip() {
  const kpis = buildKpis().map(KpiTile).join("");
  return `
    <div class="kpi-grid">${kpis}</div>
    <div class="kpi-donut" id="kpi-donut-card">${StatusChart()}</div>`;
}

export function CasesPage(user) {
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

/* ---------- partial re-renders ---------- */

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
  const navBadge = document.querySelector('.sidebar-link[data-nav="cases"] .sidebar-badge');
  if (navBadge) navBadge.textContent = String(state.cases.length);
  createIcons({ icons });
}
