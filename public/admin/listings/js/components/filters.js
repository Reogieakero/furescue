import { esc } from "./util.js";
import { state } from "../state.js";
import { listingCounts } from "./kpis.js";

export const FILTERS = [
  { key: "all", label: "All" },
  { key: "pending_review", label: "In review" },
  { key: "approved", label: "Live" },
  { key: "rejected", label: "Rejected" },
];

export function FilterTabs() {
  const c = listingCounts();
  const count = {
    all: c.all,
    pending_review: c.pending,
    approved: c.live,
    rejected: c.rejected,
  };
  return `
  <div class="report-toolbar">
    <div class="q-tabs" id="listing-tabs">
      ${FILTERS.map(
        (f) =>
          `<button data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
      ).join("")}
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="listing-search" type="text" placeholder="Search animal, poster…" value="${esc(state.query)}">
    </div>
  </div>`;
}
