// Dashboard markup components. Everything reads from ./state.js — no mock data.
import { AppShell } from "../../layout/app-shell.js";
import { PaginationBar } from "../../../../js/components/ui/pagination.js";
import { Select } from "../../../../js/components/ui/select.js";
import { Button } from "../../../../js/components/ui/button.js";
import { state, queueState } from "./state.js";
import { shortId, initials, timeAgo, titleCase } from "./helpers.js";

const QUEUE_PAGE_SIZE = 7;
const ACTIVITY_PAGE_SIZE = 5;

const ChevronRight = () => '<i data-lucide="chevron-right" class="link-chevron"></i>';

function EmptyState({ icon = "inbox", text = "No records." } = {}) {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${text}</span></div>`;
}

function avatarImg(src, name) {
  return src
    ? `<img class="table-avatar" src="${src}" alt="">`
    : `<span class="table-avatar table-avatar--initial">${initials(name)}</span>`;
}

function rescuerAvatar(src, name) {
  return src
    ? `<img class="rescuer-avatar" src="${src}" alt="">`
    : `<span class="rescuer-avatar rescuer-avatar--initial">${initials(name)}</span>`;
}

/* ---------- Greeting ---------- */

function Greeting(user) {
  const name = (user && user.full_name) || "Admin";
  return `
  <div class="greeting">
    <div>
      <span class="stamp stamp--coral">Command Center</span>
      <h1 class="greeting-title">Good morning, ${name}</h1>
      <p class="greeting-sub" id="greeting-sub">${state.decisionCount} items need a decision today across reports, rescuers, health records, and adoptions.</p>
    </div>
    <div class="greeting-actions">
      ${Button({ text: "Export Report", variant: "outline", icon: "download" })}
      ${Button({ text: "New Announcement", variant: "default", icon: "megaphone" })}
    </div>
  </div>`;
}

/* ---------- KPI row ---------- */

function buildKpis() {
  const o = state.overview;
  return [
    { icon: "map-pin", value: o.reports, label: "Active reports", note: null },
    { icon: "badge-check", value: state.reportsPending.total, label: "Pending verify", note: state.reportsPending.total ? { text: "Needs You", cls: "kpi-note--coral" } : null },
    { icon: "siren", value: o.rescuers_on_duty, label: "Rescuers on duty", note: null },
    { icon: "heart-pulse", value: state.healthUpdates.total, label: "Health updates", note: state.healthUpdates.total ? { text: "Recent", cls: "kpi-note--muted" } : null },
    { icon: "home", value: o.adoptions_pending, label: "Pending adoptions" },
    { icon: "check-circle-2", value: o.cases_resolved, label: "Resolved cases", dark: true },
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

export function KpiGrid() {
  return `<div class="kpi-grid" id="kpi-grid">${buildKpis().map(KpiTile).join("")}</div>`;
}

/* ---------- queue tabs (Needs your attention) ---------- */

function TableHead(cols) {
  return `
  <thead>
    <tr class="table-head">
      ${cols.map((c) => `<th>${c}</th>`).join("")}
    </tr>
  </thead>`;
}

function slicePage(items, key) {
  const page = queueState[key] || 1;
  const start = (page - 1) * QUEUE_PAGE_SIZE;
  return items.slice(start, start + QUEUE_PAGE_SIZE);
}

function paginationBar(key, total) {
  if (total <= QUEUE_PAGE_SIZE) return "";
  return `<div class="queue-pagination">${PaginationBar({ total, perPage: QUEUE_PAGE_SIZE, page: queueState[key] || 1 })}</div>`;
}

function mapReport(r) {
  return {
    id: shortId(r.id),
    rid: r.id,
    brgy: r.address_text || "—",
    reporter: shortId(r.resident_id),
    when: timeAgo(r.created_at),
  };
}

function mapRescuerApplicant(u) {
  return {
    name: u.full_name || "Unnamed applicant",
    rid: u.id,
    img: u.profile_photo_url || "",
    org: u.phone_number || "—",
    file: "—",
    when: timeAgo(u.created_at),
  };
}

function mapHealthUpdate(h) {
  const healthy = h.health_status === "healthy";
  const animal = [h.animal_name || "", h.breed_type || ""].filter(Boolean).join(", ") || "Unnamed animal";
  return {
    id: shortId(h.id),
    rid: h.id,
    animal,
    by: h.logged_by_name || "—",
    when: timeAgo(h.logged_at),
    icon: h.species === "cat" ? "cat" : "paw-print",
    ok: healthy,
    rescue: titleCase(h.rescue_status) || "Rescued",
    status: healthy ? "Stable" : "Needs Attention",
    statusCls: healthy ? "hc-card--accent" : "hc-card--coral",
  };
}

function mapAdoption(a) {
  return {
    name: a.applicant_name || shortId(a.applicant_id),
    rid: a.id,
    animal: a.animal_name || shortId(a.animal_id),
    visit: "—",
    visitCls: "status-text--muted",
    when: timeAgo(a.created_at),
  };
}

function mapCase(c) {
  const status = String(c.status || "assigned");
  const statusCls =
    status === "resolved" ? "stamp--accent"
    : status === "in_progress" ? "stamp--accent"
    : "stamp--coral";
  return {
    id: shortId(c.id),
    animal: "—",
    brgy: "—",
    rescuer: "—",
    status: titleCase(status),
    statusCls,
    when: timeAgo(c.updated_at || c.created_at),
  };
}

export function ReportsQueueInner() {
  const list = state.reportsPending.items.map(mapReport);
  if (list.length === 0) {
    return `<div class="queue-empty">${EmptyState({ icon: "file-text", text: "No reports pending verification." })}</div>`;
  }
  const rows =
    slicePage(list, "reports").map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">${r.id}</td>
      <td class="table-cell">${r.brgy}</td>
      <td class="table-cell">${r.reporter}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          <a href="#" class="action-link" data-action="details" data-id="${r.rid}">Details</a>
          <a href="#" class="action-link" data-action="verify" data-id="${r.rid}">Verify</a>
          <a href="#" class="action-link action-link--danger" data-action="dismiss" data-id="${r.rid}">Dismiss</a>
        </span>
      </td>
    </tr>`
    ).join("");
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Case", "Barangay", "Reporter", "Submitted", "Action"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="#" class="btn-link">View all ${state.reportsPending.total} reports ${ChevronRight()}</a></div>
    ${paginationBar("reports", list.length)}`;
}

export function RescuersQueueInner() {
  const list = state.rescuersPending.items.map(mapRescuerApplicant);
  if (list.length === 0) {
    return `<div class="queue-empty">${EmptyState({ icon: "user-check", text: "No rescuer applications awaiting review." })}</div>`;
  }
  const rows =
    slicePage(list, "rescuers").map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--strong"><span class="table-avatar-name">${avatarImg(r.img, r.name)}${r.name}</span></td>
      <td class="table-cell">${r.org}</td>
      <td class="table-cell"><span class="file-link"><i data-lucide="file-check"></i> ${r.file}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          <a href="#" class="action-link" data-action="details" data-id="${r.rid}">Details</a>
          <a href="#" class="action-link" data-action="approve-rescuer" data-id="${r.rid}">Approve</a>
          <a href="#" class="action-link action-link--danger" data-action="reject-rescuer" data-id="${r.rid}">Reject</a>
        </span>
      </td>
    </tr>`
    ).join("");
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Applicant", "Affiliation", "Proof of ID", "Submitted", "Action"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${paginationBar("rescuers", list.length)}`;
}

export function HealthQueueInner() {
  const list = state.healthUpdates.items.map(mapHealthUpdate);
  if (list.length === 0) {
    return `<div class="queue-empty">${EmptyState({ icon: "heart-pulse", text: "No recent health updates." })}</div>`;
  }
  const rows =
    slicePage(list, "health").map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">${r.id}</td>
      <td class="table-cell">${r.animal}</td>
      <td class="table-cell">${r.by}</td>
      <td class="table-cell table-cell--muted">${r.status}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          <a href="#" class="action-link" data-action="details" data-id="${r.rid}">Details</a>
          <a href="#" class="action-link">View record</a>
        </span>
      </td>
    </tr>`
    ).join("");
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Update", "Animal", "Logged by", "Status", "When", "Action"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${paginationBar("health", list.length)}`;
}

export function AdoptionQueueInner() {
  const list = state.adoptionsPending.items.map(mapAdoption);
  if (list.length === 0) {
    return `<div class="queue-empty">${EmptyState({ icon: "home", text: "No adoption applications awaiting review." })}</div>`;
  }
  const rows =
    slicePage(list, "adopt").map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--strong">${r.name}</td>
      <td class="table-cell">${r.animal}</td>
      <td class="table-cell"><span class="status-text ${r.visitCls}">${r.visit}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          <a href="#" class="action-link" data-action="details" data-id="${r.rid}">Details</a>
          <a href="#" class="action-link" data-action="approve-adoption" data-id="${r.rid}">Approve</a>
          <a href="#" class="action-link action-link--danger" data-action="decline-adoption" data-id="${r.rid}">Decline</a>
        </span>
      </td>
    </tr>`
    ).join("");
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Applicant", "Animal", "Home visit", "Submitted", "Action"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="#" class="btn-link">View all ${state.adoptionsPending.total} applications ${ChevronRight()}</a></div>
    ${paginationBar("adopt", list.length)}`;
}

function AttentionQueue() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="inbox"></i>
        <h2 class="panel-title">Needs your attention</h2>
      </div>
      <div class="q-tabs" id="queueTabs">
        <button data-q="reports" class="q-btn is-active">Reports &middot; ${state.reportsPending.total}</button>
        <button data-q="rescuers" class="q-btn">Rescuers &middot; ${state.rescuersPending.total}</button>
        <button data-q="health" class="q-btn">Health &middot; ${state.healthUpdates.total}</button>
        <button data-q="adopt" class="q-btn">Adoptions &middot; ${state.adoptionsPending.total}</button>
      </div>
    </div>
    <div id="queue-reports" class="queue-panel">${ReportsQueueInner()}</div>
    <div id="queue-rescuers" class="queue-panel is-hidden">${RescuersQueueInner()}</div>
    <div id="queue-health" class="queue-panel is-hidden">${HealthQueueInner()}</div>
    <div id="queue-adopt" class="queue-panel is-hidden">${AdoptionQueueInner()}</div>
  </div>`;
}

/* ---------- recent health updates carousel ---------- */

function HealthCarousel() {
  const list = state.healthUpdates.items.map(mapHealthUpdate);
  if (list.length === 0) {
    return `
  <div class="panel health-carousel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title panel-title--sm">Recent health updates</h2></div>
      <span class="stamp stamp--sm stamp--accent">0 Updates</span>
    </div>
    <div class="carousel">
      <div class="carousel-empty">
        ${EmptyState({ icon: "heart-pulse", text: "No health updates yet." })}
        <p class="carousel-empty-note">Recent field status logs will appear here.</p>
      </div>
    </div>
  </div>`;
  }
  const slideHtml = (h) => `
    <div class="carousel-slide">
      <div class="hc-top">
        <div class="hc-icon"><i data-lucide="${h.icon}"></i></div>
        <div class="hc-meta">
          <div class="hc-animal">${h.animal}</div>
          <div class="hc-when">${h.when}</div>
        </div>
      </div>
      <div class="hc-cards">
        <div class="hc-card">
          <span class="hc-card-icon"><i data-lucide="shield-check"></i></span>
          <div class="hc-card-body">
            <span class="hc-card-label">Rescue status</span>
            <span class="hc-card-value hc-card--accent">${h.rescue}</span>
          </div>
        </div>
        <div class="hc-card ${h.statusCls}">
          <span class="hc-card-icon"><i data-lucide="activity"></i></span>
          <div class="hc-card-body">
            <span class="hc-card-label">Health</span>
            <span class="hc-card-value">${h.status}</span>
          </div>
        </div>
      </div>
    </div>`;
  const dotsHtml = list
    .map((_, i) => `<button class="carousel-dot${i === 0 ? " is-active" : ""}" data-i="${i}" aria-label="Slide ${i + 1}"></button>`)
    .join("");
  return `
  <div class="panel health-carousel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title panel-title--sm">Recent health updates</h2></div>
      <span class="stamp stamp--sm stamp--accent">${list.length} Updates</span>
    </div>
    <div class="carousel">
      <div class="carousel-track">${list.map(slideHtml).join("")}${slideHtml(list[0])}</div>
    </div>
    <div class="carousel-dots">${dotsHtml}</div>
  </div>`;
}

/* ---------- attention row (queue + health carousel + rescuers) ---------- */

function RescuersCard() {
  const rows = state.rescuers.slice(0, 4).map((u) => {
    const r = {
      name: u.full_name || "Rescuer",
      img: u.profile_photo_url || "",
      org: u.phone_number || "Rescuer",
      meta: "Active",
    };
    return `
    <div class="rescuer">
      <div class="rescuer-avatar-wrap">
        ${rescuerAvatar(r.img, r.name)}
        <span class="rescuer-status"></span>
      </div>
      <div class="rescuer-body">
        <div class="rescuer-name">${r.name}</div>
        <div class="rescuer-org">${r.org}</div>
      </div>
      <span class="rescuer-meta">${r.meta}</span>
    </div>`;
  }).join("");
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="siren"></i><h2 class="panel-title panel-title--sm">Rescuers on duty</h2></div>
      <div class="rescuer-head-tools">
        <span class="stamp stamp--sm stamp--accent">${state.overview.rescuers_on_duty} Active</span>
        <a href="#" class="btn-link">View all ${ChevronRight()}</a>
      </div>
    </div>
    ${rows ? `<div class="rescuer-list">${rows}</div>` : EmptyState({ icon: "siren", text: "No rescuers on duty." })}
  </div>`;
}

function AttentionRow() {
  return `
  <div class="attention-row">
    <div class="attention-main">${AttentionQueue()}</div>
    <div class="attention-side">
      ${HealthCarousel()}
      ${RescuersCard()}
    </div>
  </div>`;
}

/* ---------- two column area ---------- */

function MapCard() {
  return `
  <div class="panel" id="case-density-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="map"></i><h2 class="panel-title">Case density &middot; City of Mati</h2></div>
      <div class="map-tools">
        <span class="map-label">Heat intensity</span>
        ${Select({
          id: "heat-intensity",
          value: "medium",
          placeholder: "Heat intensity",
          options: [
            { value: "low", label: "Low" },
            { value: "medium", label: "Medium" },
            { value: "high", label: "High" },
          ],
        })}
        <button type="button" id="map-expand" class="map-expand" aria-label="Expand map" title="Expand map"><i data-lucide="maximize"></i></button>
        <a href="#" class="btn-link">Open full map ${ChevronRight()}</a>
      </div>
    </div>
    <div id="case-density-map" class="map-canvas map-canvas--leaflet"></div>
    <div class="map-foot"><span id="heat-count">0</span> Active pins &middot; Live</div>
  </div>`;
}

export function ActivityInner() {
  const list = state.activity.map(mapCase);
  if (list.length === 0) {
    return `<div class="activity-empty">${EmptyState({ icon: "list", text: "No records." })}</div>`;
  }
  const page = state.activityPage || 1;
  const start = (page - 1) * ACTIVITY_PAGE_SIZE;
  const rows =
    list.slice(start, start + ACTIVITY_PAGE_SIZE).map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">${r.id}</td>
      <td class="table-cell">${r.animal}</td>
      <td class="table-cell">${r.brgy}</td>
      <td class="table-cell">${r.rescuer}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${r.statusCls}">${r.status}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
    </tr>`
    ).join("");
  const pagination =
    list.length > ACTIVITY_PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: ACTIVITY_PAGE_SIZE, page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Case", "Animal", "Barangay", "Rescuer", "Status", "Updated"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}

function ActivityTable() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="list"></i><h2 class="panel-title">Recent case activity</h2></div>
      <a href="#" class="btn-link">View all cases ${ChevronRight()}</a>
    </div>
    <div id="activity-table" class="activity-table">${ActivityInner()}</div>
  </div>`;
}

function ChartCard() {
  const bars = state.chart.map(
    (d) => `
    <div class="chart-col">
      <div class="chart-track"><div class="chart-bar${d.coral ? " chart-bar--coral" : ""}" style="height:${d.h}%"></div></div>
      <span class="chart-day">${d.day}</span>
    </div>`
  ).join("");
  const growth =
    state.growth === null
      ? ""
      : ` &middot; <span class="chart-foot-accent">${state.growth > 0 ? "+" : ""}${state.growth}% vs last week</span>`;
  return `
  <div class="panel panel--padded">
    <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h2 class="panel-title panel-title--sm">Report this week</h2></div>
    <div class="chart">${bars}</div>
    <div class="chart-foot">
      <span class="chart-foot-muted">Total approved</span>
      <span class="chart-foot-total">${state.overview.adoptions_completed}${growth}</span>
    </div>
  </div>`;
}

function ElearningCard() {
  const list = state.elearning.items || [];
  if (list.length === 0) {
    return `
  <div class="panel panel--padded elearn-card">
    <div class="panel-title-wrap"><i data-lucide="book-open"></i><h2 class="panel-title panel-title--sm">E-Learning library</h2></div>
    ${EmptyState({ icon: "book-open", text: "No records." })}
  </div>`;
  }
  const slideHtml = (m) => `
    <div class="carousel-slide carousel-slide--elearn">
      <span class="ec-category">${m.category || "Module"}</span>
      <h3 class="ec-title">${m.title || "Untitled module"}</h3>
      <p class="ec-meta">${timeAgo(m.created_at)} &middot; Published</p>
      <a href="#" class="btn-link ec-link">Read module ${ChevronRight()}</a>
    </div>`;
  const dotsHtml = list
    .map((_, i) => `<button class="carousel-dot${i === 0 ? " is-active" : ""}" data-i="${i}" aria-label="Slide ${i + 1}"></button>`)
    .join("");
  return `
  <div class="panel panel--padded elearn-card">
    <div class="panel-title-wrap"><i data-lucide="book-open"></i><h2 class="panel-title panel-title--sm">E-Learning library</h2></div>
    <div class="elearn-carousel">
      <div class="carousel">
        <div class="carousel-track">${list.map(slideHtml).join("")}${slideHtml(list[0])}</div>
      </div>
      <div class="carousel-dots">${dotsHtml}</div>
    </div>
    ${Button({ text: "Manage content", variant: "outline", className: "w-full elearn-action" })}
  </div>`;
}

function AuditLogCard() {
  const items = state.notifications.items.slice(0, 4);
  const rows = items.length
    ? items.map(
        (n) => `
    <li class="audit-item"><span class="audit-time">${timeAgo(n.created_at)}</span><span class="audit-text">${n.message || "—"}</span></li>`
      ).join("")
    : `<li class="audit-item"><span class="audit-text">No recent notifications.</span></li>`;
  return `
  <div class="audit">
    <div class="audit-head"><i data-lucide="bell"></i> Recent notifications</div>
    <ul class="audit-list">${rows}</ul>
  </div>`;
}

function DashboardSections() {
  return `
  ${MapCard()}
  <div class="cols cols--two">
    ${ChartCard()}
    ${ElearningCard()}
  </div>
  <div class="cols">
    <div class="col-main">${ActivityTable()}</div>
    <div class="col-side">${AuditLogCard()}</div>
  </div>`;
}

/* ---------- page ---------- */

export function DashboardPage(user) {
  return AppShell({
    user,
    notifications: state.notifications.total,
    badges: {
      reports: state.overview.reports,
      health: state.healthUpdates.total,
      applications: state.adoptionsPending.total,
    },
    children: [Greeting(user), KpiGrid(), AttentionRow(), DashboardSections()].join(""),
  });
}