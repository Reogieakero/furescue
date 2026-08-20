import { state } from "../state.js";
import { shortId, initials, timeAgo, titleCase } from "../helpers.js";
import {
  ChevronRight,
  EmptyState,
  rescuerAvatar,
  TableHead,
  slicePage,
  paginationBar,
} from "./util.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { Select } from "../../../../../js/components/ui/select.js";
import { PaginationBar } from "../../../../../js/components/ui/pagination.js";
import { AttentionQueue, mapHealthUpdate } from "./queues.js";
import { ActivityTable } from "./activity.js";

export function HealthCarousel() {
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

export function RescuersCard() {
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

export function MapCard() {
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

export function ChartCard() {
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

export function ElearningCard() {
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

export function AuditLogCard() {
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

export function AttentionRow() {
  return `
  <div class="attention-row">
    <div class="attention-main">${AttentionQueue()}</div>
    <div class="attention-side">
      ${HealthCarousel()}
      ${RescuersCard()}
    </div>
  </div>`;
}

export function DashboardSections() {
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
