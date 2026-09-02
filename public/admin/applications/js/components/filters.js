import { esc } from "./util.js";
import { state } from "../state.js";
import { applicationCounts } from "./kpis.js";

export const FILTERS = [
  { key: "all", label: "All" },
  { key: "pending", label: "Pending" },
  { key: "approved", label: "Approved" },
  { key: "rejected", label: "Rejected" },
  { key: "completed", label: "Completed" },
  { key: "cancelled", label: "Cancelled" },
];

export function FilterTabs() {
  const c = applicationCounts();
  const count = {
    all: c.all,
    pending: c.pending,
    approved: c.approved,
    rejected: c.rejected,
    completed: c.completed,
    cancelled: c.cancelled,
  };
  return `
  <div class="report-toolbar">
    <div class="q-tabs" id="application-tabs">
      ${FILTERS.map(
        (f) =>
          `<button type="button" data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
      ).join("")}
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="application-search" type="text" placeholder="Search applicant, animal, message…" value="${esc(state.query)}">
    </div>
  </div>`;
}
