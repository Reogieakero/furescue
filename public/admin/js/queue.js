import { createIcons, icons } from "lucide";
import { readPageClick, readPageSizeChange } from "/shared/components/pagination/pagination.js";
import { queueState, queuePageSize, state, refreshQueue } from "./state.js";
import { toast } from "/shared/components/toast/toast.js";
import { confirmDialog } from "/shared/components/dialog/dialog.js";
import { openDrawer } from "/shared/components/drawer/drawer.js";
import { Button } from "/shared/components/button/button.js";
import { mountPinMap } from "/assets/js/lib/leaflet.js";
import * as api from "/assets/js/admin/admin-data.js";
import { shortId, titleCase } from "./helpers.js";
import { firstPhoto } from "./insights.js";
import {
  ReportsQueueInner,
  RescuersQueueInner,
  HealthQueueInner,
  AdoptionQueueInner,
  KpiGrid,
} from "./components.js";

const QUEUE_INNERS = {
  reports: ReportsQueueInner,
  rescuers: RescuersQueueInner,
  health: HealthQueueInner,
  adopt: AdoptionQueueInner,
};

const QUEUE_TAB_LABELS = {
  reports: "Reports",
  rescuers: "Rescuers",
  health: "Health",
  adopt: "Adoptions",
};

const DETAILS_TITLES = {
  reports: "Report details",
  rescuers: "Rescuer application details",
  health: "Health update details",
  adopt: "Adoption application details",
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

function reportPhotoHtml(r) {
  const src = firstPhoto(r && (r.photo_urls || r.photoUrls));
  if (!src) {
    return `<div class="drawer-photo-empty"><i data-lucide="image-off"></i><span>No photo attached</span></div>`;
  }
  return `<img class="drawer-photo" src="${esc(src)}" alt="Report photo">`;
}

function openReportDetails(id) {
  const r = state.reportsPending.items.find((i) => i.id === id);
  if (!r) return;
  const rows = buildDetailsInfo("reports", id)
    .map(
      (row) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(row.label)}</span>
      <span class="dialog-info-value">${esc(row.value)}</span>
    </div>`
    )
    .join("");

  openDrawer({
    title: "Report details",
    body: `
      <div class="dialog-info">${rows}</div>
      <div id="report-detail-map" class="drawer-map"></div>
      <div class="drawer-reported">
        ${reportPhotoHtml(r)}
        <span class="drawer-reported-text" id="drawer-reported-text"></span>
      </div>`,
    onMount: (bodyEl) => {
      const lat = Number(r.latitude);
      const lng = Number(r.longitude);
      const mapEl = bodyEl.querySelector("#report-detail-map");
      if (mapEl && Number.isFinite(lat) && Number.isFinite(lng)) {
        void mountPinMap(mapEl, lat, lng, { popup: esc(r.address_text || "Report location") });
      }

      const capEl = bodyEl.querySelector("#drawer-reported-text");
      if (capEl) {
        typewriter(capEl, `Reported at ${r.address_text || "Mati City"}`);
      }
    },
  });
}

function typewriter(el, text, speed = 26) {
  let i = 0;
  const cursor = '<span class="tw-cursor">|</span>';
  const step = () => {
    if (i >= text.length) {
      el.innerHTML = text;
      return;
    }
    el.innerHTML = text.slice(0, i) + cursor;
    i += 1;
    setTimeout(step, speed);
  };
  step();
}

function openDetailsDrawer(key, id) {
  const rows = buildDetailsInfo(key, id)
    .map(
      (row) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(row.label)}</span>
      <span class="dialog-info-value">${esc(row.value)}</span>
    </div>`
    )
    .join("");
  const opts = {
    title: DETAILS_TITLES[key] || "Details",
    body: `<div class="dialog-info">${rows}</div>`,
  };
  if (key === "rescuers") {
    opts.footer = `${Button({ text: "Reject", variant: "outline", attrs: 'data-drawer-act="reject"' })}
      ${Button({ text: "Approve", variant: "default", attrs: 'data-drawer-act="approve"' })}`;
    opts.onMount = (bodyEl) => {
      const drawer = bodyEl.closest(".drawer");
      const reject = drawer.querySelector('[data-drawer-act="reject"]');
      const approve = drawer.querySelector('[data-drawer-act="approve"]');
      if (reject) reject.addEventListener("click", () => runAction("reject-rescuer", id));
      if (approve) approve.addEventListener("click", () => runAction("approve-rescuer", id));
    };
  }
  if (key === "adopt") {
    opts.footer = `${Button({ text: "Decline", variant: "outline", attrs: 'data-drawer-act="decline"' })}
      ${Button({ text: "Approve", variant: "default", attrs: 'data-drawer-act="approve"' })}`;
    opts.onMount = (bodyEl) => {
      const drawer = bodyEl.closest(".drawer");
      const decline = drawer.querySelector('[data-drawer-act="decline"]');
      const approve = drawer.querySelector('[data-drawer-act="approve"]');
      if (decline) decline.addEventListener("click", () => runAction("decline-adoption", id));
      if (approve) approve.addEventListener("click", () => runAction("approve-adoption", id));
    };
  }
  openDrawer(opts);
}

function buildDetailsInfo(key, id) {
  if (key === "reports") {
    const r = state.reportsPending.items.find((i) => i.id === id);
    return r
      ? [
          { label: "Case", value: shortId(r.id) },
          { label: "Barangay", value: titleCase(r.address_text) || "—" },
          { label: "Reporter", value: shortId(r.resident_id) },
          { label: "Animal description", value: r.animal_description || "—" },
          { label: "Latitude", value: r.latitude != null ? String(r.latitude) : "—" },
          { label: "Longitude", value: r.longitude != null ? String(r.longitude) : "—" },
          { label: "Validation", value: titleCase(r.validation_status) || "—" },
          { label: "Status", value: titleCase(r.status) || "—" },
          { label: "Submitted", value: new Date(r.created_at).toLocaleString() },
        ]
      : [];
  }
  if (key === "rescuers") {
    const u = state.rescuersPending.items.find((i) => i.id === id);
    return u
      ? [
          { label: "Name", value: u.full_name || "—" },
          { label: "Email", value: u.email || "—" },
          { label: "Contact", value: u.phone_number || "—" },
          { label: "Role", value: titleCase(u.role) || "—" },
          { label: "Status", value: titleCase(u.account_status) || "—" },
          { label: "Submitted", value: new Date(u.created_at).toLocaleString() },
        ]
      : [];
  }
  if (key === "health") {
    const h = state.healthUpdates.items.find((i) => i.id === id);
    return h
      ? [
          { label: "Update", value: shortId(h.id) },
          { label: "Animal", value: [h.animal_name, h.breed_type].filter(Boolean).join(", ") || "—" },
          { label: "Species", value: titleCase(h.species) || "—" },
          { label: "Rescue status", value: titleCase(h.rescue_status) || "—" },
          { label: "Health status", value: h.health_status === "healthy" ? "Stable" : "Needs Attention" },
          { label: "Logged by", value: h.logged_by_name || "—" },
          { label: "Logged at", value: new Date(h.logged_at).toLocaleString() },
        ]
      : [];
  }
  if (key === "adopt") {
    const a = state.adoptionsPending.items.find((i) => i.id === id);
    return a
      ? [
          { label: "Application", value: shortId(a.id) },
          { label: "Applicant", value: a.applicant_name || shortId(a.applicant_id) },
          { label: "Animal", value: a.animal_name || shortId(a.animal_id) },
          { label: "Status", value: titleCase(a.status) || "—" },
          { label: "Submitted", value: new Date(a.created_at).toLocaleString() },
        ]
      : [];
  }
  return [];
}

const ACTIONS = {
  verify: {
    queue: "reports",
    title: "Verify this report?",
    confirmText: "Verify",
    run: (id) => api.verifyReport(id),
  },
  dismiss: {
    queue: "reports",
    title: "Dismiss this report?",
    confirmText: "Dismiss",
    danger: true,
    withReason: true,
    reasonLabel: "Dismiss reason",
    reasonRequired: true,
    run: (id, reason) => api.dismissReport(id, reason),
  },
  "approve-rescuer": {
    queue: "rescuers",
    title: "Approve this rescuer?",
    confirmText: "Approve",
    run: (id) => api.approveRescuer(id),
  },
  "reject-rescuer": {
    queue: "rescuers",
    title: "Reject this rescuer?",
    confirmText: "Reject",
    danger: true,
    run: (id) => api.rejectRescuer(id),
  },
  "approve-adoption": {
    queue: "adopt",
    title: "Approve this adoption?",
    confirmText: "Approve",
    run: (id) => api.approveAdoption(id),
  },
  "decline-adoption": {
    queue: "adopt",
    title: "Decline this adoption?",
    confirmText: "Decline",
    danger: true,
    withReason: true,
    reasonLabel: "Decline reason",
    reasonRequired: true,
    run: (id, reason) => api.rejectAdoption(id, reason),
  },
};

export function initQueueTabs() {
  const tabs = document.querySelectorAll(".q-btn");
  const panels = document.querySelectorAll(".queue-panel");

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      panels.forEach((p) => p.classList.add("is-hidden"));
      const panel = document.getElementById("queue-" + btn.dataset.q);
      if (panel) panel.classList.remove("is-hidden");
      tabs.forEach((b) => b.classList.toggle("is-active", b === btn));
    });
  });
}

export function renderQueuePanel(key) {
  const panel = document.getElementById("queue-" + key);
  if (!panel) return;
  panel.innerHTML = QUEUE_INNERS[key]();
  createIcons({ icons });
}

export function initQueuePagination() {
  document.querySelectorAll(".queue-panel").forEach((panel) => {
    const key = panel.id.replace("queue-", "");
    panel.addEventListener("click", (e) => {
      const page = readPageClick(e.target, queueState[key]);
      if (!page) return;
      queueState[key] = page;
      renderQueuePanel(key);
    });
    panel.addEventListener("change", (e) => {
      const next = readPageSizeChange(e.target, queuePageSize[key]);
      if (!next) return;
      queuePageSize[key] = next;
      queueState[key] = 1;
      renderQueuePanel(key);
    });
  });
}

function updateTabCount(key) {
  const total =
    key === "reports"
      ? state.reportsPending.total
      : key === "rescuers"
        ? state.rescuersPending.total
        : key === "adopt"
          ? state.adoptionsPending.total
          : state.healthUpdates.total;
  const btn = document.querySelector(`.q-btn[data-q="${key}"]`);
  if (btn) btn.innerHTML = `${QUEUE_TAB_LABELS[key]} &middot; ${total}`;
}

function updateKpiGrid() {
  const el = document.getElementById("kpi-grid");
  if (!el) return;
  el.outerHTML = KpiGrid();
  createIcons({ icons });
}

function updateDecisionCount() {
  const el = document.getElementById("greeting-sub");
  if (el) {
    el.textContent = `${state.decisionCount} items need a decision today across reports, rescuers, health records, and adoptions.`;
  }
}

function confirmMessage(action, id) {
  if (action === "verify") {
    return `Verify report ${shortId(id)}? This will create a case.`;
  }
  if (action === "dismiss") {
    return `Dismiss report ${shortId(id)}? This report will be closed.`;
  }
  if (action === "approve-rescuer" || action === "reject-rescuer") {
    const u = state.rescuersPending.items.find((i) => i.id === id);
    const name = (u && u.full_name) || shortId(id);
    return action === "approve-rescuer"
      ? `Approve ${name} as a rescuer?`
      : `Reject ${name}'s rescuer application?`;
  }
  if (action === "approve-adoption" || action === "decline-adoption") {
    const a = state.adoptionsPending.items.find((i) => i.id === id);
    const applicant = (a && a.applicant_name) || shortId(id);
    const animal = (a && a.animal_name) || "this animal";
    return action === "approve-adoption"
      ? `Approve the adoption application for ${applicant} · ${animal}?`
      : `Decline the adoption application for ${applicant} · ${animal}?`;
  }
  return `Are you sure you want to ${ACTIONS[action].confirmText.toLowerCase()} ${shortId(id)}?`;
}

function buildInfo(action, id) {
  if (action === "verify" || action === "dismiss") {
    const r = state.reportsPending.items.find((i) => i.id === id);
    return r
      ? [
          { label: "Case", value: shortId(r.id) },
          { label: "Barangay", value: r.address_text || "—" },
          { label: "Reporter", value: shortId(r.resident_id) },
          { label: "Submitted", value: new Date(r.created_at).toLocaleString() },
        ]
      : [];
  }
  if (action === "approve-rescuer" || action === "reject-rescuer") {
    const u = state.rescuersPending.items.find((i) => i.id === id);
    return u
      ? [
          { label: "Name", value: u.full_name || "—" },
          { label: "Contact", value: u.phone_number || "—" },
          { label: "Submitted", value: new Date(u.created_at).toLocaleString() },
        ]
      : [];
  }
  if (action === "approve-adoption" || action === "decline-adoption") {
    const a = state.adoptionsPending.items.find((i) => i.id === id);
    return a
      ? [
          { label: "Applicant", value: a.applicant_name || shortId(a.applicant_id) },
          { label: "Animal", value: a.animal_name || shortId(a.animal_id) },
          { label: "Submitted", value: new Date(a.created_at).toLocaleString() },
        ]
      : [];
  }
  return [];
}

function toastMessage(action, id, res) {
  if (action === "verify") {
    const caseId = res && res.data && res.data.case_id;
    return caseId
      ? `Report ${shortId(id)} verified · Case ${shortId(caseId)} created.`
      : `Report ${shortId(id)} verified.`;
  }
  if (action === "dismiss") {
    return `Report ${shortId(id)} dismissed.`;
  }
  if (action === "approve-rescuer" || action === "reject-rescuer") {
    const u = state.rescuersPending.items.find((i) => i.id === id);
    const name = (u && u.full_name) || shortId(id);
    return action === "approve-rescuer"
      ? `${name} approved as rescuer.`
      : `Rescuer application rejected for ${name}.`;
  }
  if (action === "approve-adoption" || action === "decline-adoption") {
    const a = state.adoptionsPending.items.find((i) => i.id === id);
    const applicant = (a && a.applicant_name) || shortId(id);
    const animal = (a && a.animal_name) || "animal";
    return action === "approve-adoption"
      ? `Adoption approved for ${applicant} · ${animal}.`
      : `Adoption declined for ${applicant} · ${animal}.`;
  }
  return "Action completed.";
}

async function runAction(action, id) {
  const cfg = ACTIONS[action];
  if (!cfg || !id) return;
  const ok = await confirmDialog({
    title: cfg.title,
    message: confirmMessage(action, id),
    info: buildInfo(action, id),
    confirmText: cfg.confirmText,
    cancelText: "Cancel",
    danger: cfg.danger,
    withReason: cfg.withReason,
    reasonLabel: cfg.reasonLabel,
    reasonRequired: cfg.reasonRequired,
    run: ({ reason }) => cfg.run(id, reason),
  });
  if (!ok) return;

  toast(toastMessage(action, id, ok), { type: "success" });
  await refreshQueue(cfg.queue);
  renderQueuePanel(cfg.queue);
  updateTabCount(cfg.queue);
  updateKpiGrid();
  updateDecisionCount();
}

export function initQueueActions() {
  document.querySelectorAll(".queue-panel").forEach((panel) => {
    panel.addEventListener("click", async (e) => {
      const link = e.target.closest("a[data-action]");
      if (!link) return;
      e.preventDefault();
      const action = link.dataset.action;
      const id = link.dataset.id;
      if (action === "details") {
        const key = panel.id.replace("queue-", "");
        if (key === "reports") {
          openReportDetails(id);
        } else {
          openDetailsDrawer(key, id);
        }
        return;
      }
      await runAction(action, id);
    });
  });
}
