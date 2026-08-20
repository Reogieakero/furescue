import { createIcons, icons } from "lucide";
import { esc } from "./util.js";
import { shortId, timeAgo, titleCase } from "../../dashboard/helpers.js";
import { rescuerAvatar } from "../../dashboard/components/util.js";
import { state } from "../state.js";
import * as api from "../../../../js/lib/admin-data.js";

function caseStampCls(status) {
  if (status === "in_progress" || status === "resolved") return "stamp--accent";
  return "stamp--coral";
}

function enrichCase(c) {
  const report = c.report_id ? state.reports.find((r) => r.id === c.report_id) : null;
  const status = String(c.status || "open");
  return {
    id: c.id,
    shortId: shortId(c.id),
    status: titleCase(status),
    statusRaw: status,
    statusCls: caseStampCls(status),
    animal: report ? report.animal_description || "—" : "—",
    brgy: report ? report.address_text || "—" : "—",
    created: timeAgo(c.created_at),
    createdAt: c.created_at,
    updated: timeAgo(c.updated_at || c.created_at),
  };
}

function rescuerCases() {
  return (state.selectedRescuerCases || [])
    .map(enrichCase)
    .sort((a, b) => new Date(b.createdAt) - new Date(a.createdAt));
}

function activityLeaf(a) {
  return `
  <li class="tree-leaf">
    <span class="tree-dot"></span>
    <div class="tree-leaf-body">
      <div class="tree-leaf-head">
        <span class="stamp stamp--sm ${caseStampCls(a.action === "resolved" ? "resolved" : "open")}">${esc(titleCase(a.action))}</span>
        <span class="tree-leaf-role">${esc(a.actor_role || "—")}</span>
        <span class="tree-leaf-time">${timeAgo(a.created_at)}</span>
      </div>
      ${a.notes ? `<div class="tree-leaf-note">${esc(a.notes)}</div>` : ""}
    </div>
  </li>`;
}

function caseNode(c) {
  return `
  <li class="tree-node" data-case="${esc(c.id)}">
    <button class="tree-toggle" data-case-toggle="${esc(c.id)}" aria-expanded="false">
      <i data-lucide="chevron-right" class="tree-chevron"></i>
      <span class="stamp stamp--sm ${c.statusCls}">${esc(c.status)}</span>
      <span class="tree-node-id">${esc(c.shortId)}</span>
      <span class="tree-node-animal">${esc(c.animal)}</span>
      <span class="tree-node-meta">${esc(c.brgy)}</span>
    </button>
    <ul class="tree-children" data-case-children="${esc(c.id)}" hidden></ul>
  </li>`;
}

function caseTree(cases) {
  if (cases.length === 0) {
    return `<div class="empty-state empty-state--sm"><i data-lucide="folder-open"></i><span>No past cases for this rescuer.</span></div>`;
  }
  return `<ul class="tree">${cases.map(caseNode).join("")}</ul>`;
}

function RescuerInfo(r) {
  const duty = r.duty_status || "off_duty";
  const dutyCls = duty === "on_duty" ? "stamp--accent" : "stamp--muted";
  const rows = [
    ["Email", r.email || "—"],
    ["Phone", r.phone_number || "—"],
    ["Duty", `<span class="stamp stamp--sm ${dutyCls}">${duty === "on_duty" ? "On duty" : "Off duty"}</span>`],
    ["Status", `<span class="stamp stamp--sm ${r.account_status === "suspended" ? "stamp--muted" : "stamp--accent"}">${esc(titleCase(r.account_status))}</span>`],
    ["Joined", r.created_at ? new Date(r.created_at).toLocaleDateString() : "—"],
  ];
  return `
  <div class="rescuer-detail-head">
    ${rescuerAvatar(r.profile_photo_url, r.full_name)}
    <div class="rescuer-detail-id">
      <div class="rescuer-detail-name">${esc(r.full_name || "Unnamed")}</div>
      <div class="rescuer-detail-sub">${esc(shortId(r.id))}</div>
    </div>
    <button class="rescuer-expand map-expand" data-act="expand" title="Open in drawer" aria-label="Open in drawer">
      <i data-lucide="maximize"></i>
    </button>
  </div>
  ${RescuerMeta(r, rows)}`;
}

function RescuerMeta(r, rows) {
  return `
  <dl class="rescuer-detail-meta">
    ${(rows || metaRows(r))
      .map(
        ([k, v]) =>
          `<div class="rescuer-detail-row"><dt>${esc(k)}</dt><dd>${v}</dd></div>`
      )
      .join("")}
  </dl>`;
}

function metaRows(r) {
  const duty = r.duty_status || "off_duty";
  const dutyCls = duty === "on_duty" ? "stamp--accent" : "stamp--muted";
  return [
    ["Email", r.email || "—"],
    ["Phone", r.phone_number || "—"],
    ["Duty", `<span class="stamp stamp--sm ${dutyCls}">${duty === "on_duty" ? "On duty" : "Off duty"}</span>`],
    ["Status", `<span class="stamp stamp--sm ${r.account_status === "suspended" ? "stamp--muted" : "stamp--accent"}">${esc(titleCase(r.account_status))}</span>`],
    ["Joined", r.created_at ? new Date(r.created_at).toLocaleDateString() : "—"],
  ];
}

export function RescuerDetail() {
  if (!state.selectedId) {
    return `<div class="rescuer-detail-empty"><i data-lucide="user-round-search"></i><span>Select a rescuer to view their profile and past cases.</span></div>`;
  }
  if (state.selectedRescuer === undefined) {
    return `<div class="rescuer-detail-empty"><i data-lucide="loader" class="spin"></i><span>Loading rescuer…</span></div>`;
  }
  if (!state.selectedRescuer) {
    return `<div class="rescuer-detail-empty"><i data-lucide="user-x"></i><span>Rescuer no longer available.</span></div>`;
  }
  const r = state.selectedRescuer;
  const cases = rescuerCases();
  return `
  <div class="rescuer-detail">
    ${RescuerInfo(r)}
    <div class="rescuer-detail-section">
      <div class="rescuer-detail-section-head">
        <i data-lucide="history"></i>
        <h3>Past cases</h3>
        <span class="stamp stamp--sm stamp--accent">${cases.length}</span>
      </div>
      <div class="rescuer-detail-tree">${caseTree(cases)}</div>
    </div>
  </div>`;
}

export function renderRescuerDetail() {
  const el = document.getElementById("rescuer-detail");
  if (!el) return;
  el.innerHTML = RescuerDetail();
  createIcons({ icons });
}

function rCaseCard(c) {
  return `
  <div class="r-case" data-case="${esc(c.id)}">
    <button type="button" class="r-case-summary" aria-expanded="false">
      <i data-lucide="chevron-right" class="r-case-chevron"></i>
      <span class="stamp stamp--sm ${c.statusCls}">${esc(c.status)}</span>
      <span class="r-case-id">${esc(c.shortId)}</span>
      <span class="r-case-animal">${esc(c.animal)}</span>
      <span class="r-case-meta">${esc(c.brgy)}</span>
      <span class="r-case-when">${esc(c.created)}</span>
    </button>
    <div class="r-case-details" hidden></div>
  </div>`;
}

function rescuerModalCases() {
  const cases = rescuerCases();
  if (cases.length === 0) {
    return `<div class="empty-state empty-state--sm"><i data-lucide="folder-open"></i><span>No past cases for this rescuer.</span></div>`;
  }
  return `<div class="r-case-list">${cases.map(rCaseCard).join("")}</div>`;
}

function caseActivityView(activity) {
  if (!activity || activity.length === 0) {
    return `<div class="empty-state empty-state--sm"><i data-lucide="clock"></i><span>No activity recorded for this case.</span></div>`;
  }
  return `<ul class="tree">${activity.map(activityLeaf).join("")}</ul>`;
}

export function openRescuerModal() {
  const r = state.selectedRescuer;
  if (!r) return;
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog dialog--wide" role="dialog" aria-modal="true" aria-labelledby="rescuer-modal-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          ${rescuerAvatar(r.profile_photo_url, r.full_name)}
          <div class="dialog-title-block">
            <h3 class="dialog-title" id="rescuer-modal-title">${esc(r.full_name || "Rescuer")}</h3>
            <div class="dialog-sub">${esc(shortId(r.id))}</div>
          </div>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body rescuer-modal-body">
        ${RescuerMeta(r)}
        <div class="rescuer-detail-section">
          <div class="rescuer-detail-section-head">
            <i data-lucide="history"></i>
            <h3>Past cases</h3>
            <span class="stamp stamp--sm stamp--accent">${state.selectedRescuerCases.length}</span>
          </div>
          <div class="rescuer-modal-cases">${rescuerModalCases()}</div>
        </div>
      </div>
    </div>`;

  document.body.appendChild(overlay);
  createIcons({ icons });

  const close = () => overlay.remove();
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });

  const casesWrap = overlay.querySelector(".rescuer-modal-cases");
  casesWrap.addEventListener("click", async (e) => {
    const card = e.target.closest(".r-case");
    if (!card) return;
    e.preventDefault();
    const id = card.dataset.case;
    const details = card.querySelector(".r-case-details");
    const summary = card.querySelector(".r-case-summary");
    const open = !card.classList.contains("is-open");
    card.classList.toggle("is-open", open);
    summary.setAttribute("aria-expanded", String(open));
    if (open && !details.dataset.loaded) {
      details.dataset.loaded = "1";
      details.innerHTML = `<div class="r-case-loading"><i data-lucide="loader" class="spin"></i> Loading case details…</div>`;
      createIcons({ icons });
      let activity = [];
      try {
        activity = (await api.fetchCaseActivity(id)) || [];
      } catch {
        activity = [];
      }
      details.innerHTML = caseActivityView(activity);
      createIcons({ icons });
    }
    details.hidden = !open;
  });
}

export async function selectRescuer(id) {
  state.selectedId = id;
  state.selectedRescuer = undefined;
  document
    .querySelectorAll('#rescuer-table tr[data-id]')
    .forEach((tr) => tr.classList.toggle("is-selected", tr.dataset.id === id));
  renderRescuerDetail();

  let rescuer = null;
  let casesRes = { items: [] };
  try {
    const [userRes, cases] = await Promise.all([
      api.fetchUser(id),
      api.fetchRescuerCases(id),
    ]);
    rescuer = (userRes && userRes.user) || null;
    casesRes = cases || { items: [] };
  } catch {
    rescuer = null;
    casesRes = { items: [] };
  }
  if (state.selectedId !== id) return;
  state.selectedRescuer = rescuer;
  state.selectedRescuerCases = casesRes.items || [];
  renderRescuerDetail();
}

export async function toggleCaseNode(caseId) {
  const node = document.querySelector(`.tree-node[data-case="${caseId}"]`);
  if (!node) return;
  const children = node.querySelector(".tree-children");
  const toggle = node.querySelector(".tree-toggle");
  const expanded = toggle.getAttribute("aria-expanded") === "true";
  if (expanded) {
    toggle.setAttribute("aria-expanded", "false");
    children.hidden = true;
    return;
  }
  if (!state.caseActivity[caseId]) {
    try {
      state.caseActivity[caseId] = await api.fetchCaseActivity(caseId);
    } catch {
      state.caseActivity[caseId] = [];
    }
  }
  const activity = state.caseActivity[caseId] || [];
  children.innerHTML =
    activity.length === 0
      ? `<li class="tree-leaf tree-leaf--empty">No activity recorded.</li>`
      : activity.map(activityLeaf).join("");
  createIcons({ icons });
  toggle.setAttribute("aria-expanded", "true");
  children.hidden = false;
}
