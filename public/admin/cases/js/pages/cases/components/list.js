import { state } from "../state.js";
import { createIcons, icons } from "lucide";
import { Button } from "/js/components/ui/button.js";
import { Select, initSelect } from "/js/components/ui/select.js";
import { PaginationBar } from "/js/components/ui/pagination.js";
import { esc, caseStampCls, enrich } from "./util.js";
import { shortId, timeAgo, titleCase, initials } from "/admin/js/pages/dashboard/helpers.js";
import { CaseFilterTabs, CaseToolbar } from "./kpi.js";

export const PAGE_SIZE = 6;

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

export function filteredCases() {
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

export function CasePanel() {
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
