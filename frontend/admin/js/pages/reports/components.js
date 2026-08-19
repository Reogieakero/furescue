// Reports page markup. Everything reads from ./state.js — no mock data.
import { createIcons, icons } from "lucide";
import { AppShell } from "../../layout/app-shell.js";
import { PaginationBar } from "../../../../js/components/ui/pagination.js";
import { Button } from "../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../js/components/ui/select.js";
import { attachTooltip, hideTooltip } from "../../../../js/components/ui/tooltip.js";
import * as api from "../../lib/admin-data.js";
import { state } from "./state.js";
import { shortId, timeAgo, titleCase } from "../dashboard/helpers.js";

const PAGE_SIZE = 15;

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function stampCls(status) {
  if (status === "dismissed" || status === "rejected") return "stamp--muted";
  if (status === "assigned" || status === "pending_verification" || status === "open") return "stamp--coral";
  return "stamp--accent";
}

function reportCounts() {
  const count = (s) => state.reports.filter((r) => r.status === s).length;
  return {
    all: state.reports.length,
    pending: count("pending_verification"),
    verified: count("verified"),
    dismissed: count("dismissed"),
    activeCases: state.cases.filter((c) => c.status !== "resolved").length,
    resolvedCases: state.cases.filter((c) => c.status === "resolved").length,
  };
}

/* ---------- KPI row ---------- */

function buildKpis() {
  const c = reportCounts();
  const o = state.overview || {};
  return [
    { icon: "map-pin", value: c.all, label: "Total reports", note: null },
    { icon: "badge-check", value: c.pending, label: "Pending verify", note: c.pending ? { text: "Needs You", cls: "kpi-note--coral" } : null },
    { icon: "file-check", value: c.verified, label: "Verified", note: null },
    { icon: "file-x", value: c.dismissed, label: "Dismissed", note: null },
    { icon: "clipboard-list", value: c.activeCases, label: "Active cases", note: null },
    { icon: "check-circle-2", value: c.resolvedCases, label: "Resolved cases", dark: true },
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
  </div>`;
}

/* ---------- filters ---------- */

const FILTERS = [
  { key: "all", label: "All" },
  { key: "pending_verification", label: "Pending verification" },
  { key: "verified", label: "Verified" },
  { key: "dismissed", label: "Dismissed" },
];

function FilterTabs() {
  const c = reportCounts();
  const count = { all: c.all, pending_verification: c.pending, verified: c.verified, dismissed: c.dismissed };
  return `
  <div class="report-toolbar">
    <div class="q-tabs" id="report-tabs">
      ${FILTERS.map(
        (f) => `<button data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
      ).join("")}
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="report-search" type="text" placeholder="Search case #, barangay, description…" value="${esc(state.query)}">
    </div>
    <div class="report-sort">
      <label for="report-sort" class="report-sort-label">Sort</label>
      ${Select({
        id: "report-sort",
        options: [
          { value: "assigned", label: "Assigned" },
          { value: "verified", label: "Verified" },
        ],
        value: state.sort,
        placeholder: "Sort",
        className: "report-sort-control",
      })}
    </div>
  </div>`;
}

/* ---------- table ---------- */

function enrich(r) {
  const c = state.cases.find((x) => x.report_id === r.id) || null;
  const rescuer =
    c && c.assigned_rescuer_id ? state.rescuers.find((u) => u.id === c.assigned_rescuer_id) : null;
  return {
    rid: r.id,
    id: shortId(r.id),
    brgy: r.address_text || "—",
    reporter: shortId(r.resident_id),
    status: titleCase(r.status),
    statusCls: stampCls(r.status),
    when: timeAgo(r.created_at),
    caseStatus: c ? titleCase(c.status) : null,
    caseStatusCls: c ? stampCls(c.status) : "",
    resolved: !!(c && c.status === "resolved"),
    rescuer: rescuer ? rescuer.full_name : c && c.assigned_rescuer_id ? "Assigned" : "—",
  };
}

function actionLinks(r) {
  if (r.status === "pending_verification") {
    return [
      Button({ text: "Verify", variant: "default", size: "sm", icon: "badge-check", attrs: `data-action="verify" data-id="${r.id}"` }),
      Button({ text: "Dismiss", variant: "destructive", size: "sm", icon: "file-x", attrs: `data-action="dismiss" data-id="${r.id}"` }),
    ].join("");
  }
  if (r.status === "verified") {
    const c = state.cases.find((x) => x.report_id === r.id) || null;
    if (!c) return "";
    if (!c.assigned_rescuer_id) {
      return Button({ text: "Assign rescuer", variant: "default", size: "sm", icon: "user-plus", attrs: `data-action="assign" data-id="${r.id}" data-case="${c.id}"` });
    }
    if (c.status === "assigned") {
      return `<span class="action-text">${esc("Waiting for rescuer to accept the assigned rescue")}</span>`;
    }
    if (c.status === "in_progress") {
      return `<span class="action-text">${esc("In progress")}</span>`;
    }
  }
  return "";
}

function caseStatusRank(c) {
  if (!c) return 0;
  return { open: 1, assigned: 2, in_progress: 3, resolved: 4 }[c.status] ?? 4;
}

function sortKey(r) {
  const c = state.cases.find((x) => x.report_id === r.id) || null;
  const ts = -new Date(r.created_at).getTime();
  if (state.sort === "assigned") {
    const assigned = c && c.assigned_rescuer_id ? 0 : 1;
    return [assigned, caseStatusRank(c), ts];
  }
  if (state.sort === "verified") {
    const verified = r.status === "verified" ? 0 : 1;
    return [verified, ts];
  }
  return [ts];
}

// Wires the shadcn Select sort control. Guarded so it only binds once per
// #report-sort element (re-renders of the table alone keep this element).
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

function cmp(a, b) {
  for (let i = 0; i < a.length; i++) {
    if (a[i] < b[i]) return -1;
    if (a[i] > b[i]) return 1;
  }
  return 0;
}

function filteredReports() {
  const q = state.query.trim().toLowerCase();
  let list = state.reports;
  if (state.filter !== "all") list = list.filter((r) => r.status === state.filter);
  if (q) {
    list = list.filter((r) =>
      [shortId(r.id), r.address_text, r.animal_description, shortId(r.resident_id)]
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  return list.sort((a, b) => cmp(sortKey(a), sortKey(b)));
}

export function ReportTable() {
  const list = filteredReports();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="file-text"></i><span>No reports match.</span></div></div>`;
  }
  const start = (state.page - 1) * PAGE_SIZE;
  const rows = list
    .slice(start, start + PAGE_SIZE)
    .map((r) => {
      const v = enrich(r);
      return `
    <tr data-id="${r.id}" class="${v.resolved ? "row--resolved" : ""}">
      <td class="table-cell table-cell--mono table-cell--strong">${v.id}</td>
      <td class="table-cell">${v.brgy}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${v.reporter}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${v.statusCls}">${v.status}</span></td>
      <td class="table-cell">${v.caseStatus ? `<span class="stamp stamp--sm ${v.caseStatusCls}">${v.caseStatus}</span>` : "—"}</td>
      <td class="table-cell">${v.rescuer}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${v.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">${actionLinks(r)}</span>
      </td>
    </tr>`;
    })
    .join("");
  const pageTotal = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Case</th><th>Barangay</th><th>Reporter</th><th>Status</th><th>Case status</th><th>Rescuer</th><th>Submitted</th><th>Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}

/* ---------- page ---------- */

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

export function ReportsPage(user) {
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

/* ---------- partial re-renders ---------- */

// On row hover, shows a small map tooltip (shadcn-style) pinned to the row.
// Suppressed while the cursor is over the action column so links stay clickable.
function buildTooltipContent(r) {
  const hasLoc = Number.isFinite(Number(r.latitude)) && Number.isFinite(Number(r.longitude));
  if (!hasLoc) {
    return `<div class="tooltip-empty"><i data-lucide="map-pin-off"></i><span>No location</span></div>`;
  }
  return `
    <div class="tooltip-map" data-lat="${r.latitude}" data-lng="${r.longitude}"></div>
    <div class="drawer-map-cap"><i data-lucide="map-pin"></i><span class="loc-name loc-loading">Resolving…</span></div>`;
}

export function hideReportMapDrawer() {
  hideTooltip();
}

export function attachReportTooltips() {
  const table = document.getElementById("report-table");
  if (!table) return;
  table.querySelectorAll("tr[data-id]").forEach((row) => {
    if (row.dataset.tipAttached) return;
    row.dataset.tipAttached = "1";
    const r = state.reports.find((x) => x.id === row.dataset.id) || null;
    if (!r) return;
    attachTooltip(row, {
      placement: "top-right",
      offset: 16,
      className: "tooltip--map-lg",
      content: () => buildTooltipContent(r),
      shouldShow: (e) => !e.target.closest(".table-actions, .action-link, [data-action]"),
      onMount: (el) => {
        const mapEl = el.querySelector(".tooltip-map");
        if (mapEl && window.L) {
          const lat = Number(mapEl.dataset.lat);
          const lng = Number(mapEl.dataset.lng);
          const map = window.L.map(mapEl, {
            scrollWheelZoom: false,
            dragging: false,
            touchZoom: false,
            doubleClickZoom: false,
            zoomControl: false,
            attributionControl: false,
          }).setView([lat, lng], 14);
          window.L
            .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
              attribution: "&copy; OpenStreetMap contributors",
              maxZoom: 18,
            })
            .addTo(map);
          window.L
            .marker([lat, lng])
            .addTo(map)
            .bindPopup(esc(r.address_text || "Report location"));
          setTimeout(() => map.invalidateSize(), 60);
          el._map = map;
        }
        const capEl = el.querySelector(".loc-name");
        const fallback = r.address_text ? titleCase(r.address_text) : "Unknown location";
        if (!Number.isFinite(Number(r.latitude)) || !Number.isFinite(Number(r.longitude))) {
          if (capEl) {
            capEl.textContent = fallback;
            capEl.classList.remove("loc-loading");
          }
        } else {
          api.reverseGeocode(Number(r.latitude), Number(r.longitude)).then((loc) => {
            const specific = (loc && (loc.name || loc.road || loc.full)) || null;
            if (capEl) {
              capEl.textContent = specific || fallback;
              capEl.classList.remove("loc-loading");
            }
          }).catch(() => {
            if (capEl) {
              capEl.textContent = fallback;
              capEl.classList.remove("loc-loading");
            }
          });
        }
        createIcons({ icons });
      },
      onDestroy: (el) => {
        if (el._map) el._map.remove();
      },
    });
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
  const navBadge = document.querySelector('.sidebar-link[data-nav="reports"] .sidebar-badge');
  if (navBadge) navBadge.textContent = state.reports.length;
  createIcons({ icons });
}