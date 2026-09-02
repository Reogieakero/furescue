import { state, queueState } from "../state.js";
import { shortId, initials, timeAgo, titleCase, truncate } from "../helpers.js";
import {
  ChevronRight,
  EmptyState,
  TableHead,
  slicePage,
  paginationBar,
  avatarImg,
  rescuerAvatar,
} from "./util.js";
import { PaginationBar } from "/assets/js/components/ui/pagination.js";

export function mapReport(r) {
  return {
    id: shortId(r.id),
    rid: r.id,
    brgy: r.address_text || "—",
    reporter: shortId(r.resident_id),
    when: timeAgo(r.created_at),
  };
}

export function mapRescuerApplicant(u) {
  return {
    name: u.full_name || "Unnamed applicant",
    rid: u.id,
    img: u.profile_photo_url || "",
    org: u.phone_number || "—",
    file: "—",
    when: timeAgo(u.created_at),
  };
}

export function mapHealthUpdate(h) {
  const healthy = h.health_status === "healthy";
  const animal = [h.animal_name || "", h.breed_type || ""].filter(Boolean).join(", ") || "Unnamed animal";
  return {
    id: shortId(h.id),
    rid: h.id,
    animal,
    animalId: h.animal_id || "",
    by: h.logged_by_name || "—",
    when: timeAgo(h.logged_at),
    icon: h.species === "cat" ? "cat" : "paw-print",
    ok: healthy,
    rescue: titleCase(h.rescue_status) || "Rescued",
    status: healthy ? "Stable" : "Needs Attention",
    statusCls: healthy ? "hc-card--accent" : "hc-card--coral",
  };
}

export function mapAdoption(a) {
  return {
    name: a.applicant_name || shortId(a.applicant_id),
    rid: a.id,
    animal: a.animal_name || shortId(a.animal_id),
    visit: "—",
    visitCls: "status-text--muted",
    when: timeAgo(a.created_at),
  };
}

export function mapCase(c) {
  const status = String(c.status || "assigned");
  const statusCls =
    status === "resolved" ? "stamp--accent"
      : status === "in_progress" ? "stamp--accent"
      : "stamp--coral";
  return {
    id: shortId(c.id),
    animal: c.animal_description ? truncate(c.animal_description, 28) : "—",
    brgy: c.address_text || "—",
    rescuer: c.assigned_rescuer_name || "—",
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
    <div class="panel-foot"><a href="/admin/reports/" class="btn-link">View all ${state.reportsPending.total} reports ${ChevronRight()}</a></div>
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
          <a href="${r.animalId ? `/admin/health-records/health-record.php?id=${r.animalId}` : "/admin/health-records/"}" class="action-link">View record</a>
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
    <div class="panel-foot"><a href="/admin/applications/" class="btn-link">View all ${state.adoptionsPending.total} applications ${ChevronRight()}</a></div>
    ${paginationBar("adopt", list.length)}`;
}

export function AttentionQueue() {
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
