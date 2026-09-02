import { Select } from "/assets/js/components/ui/select.js";
import { esc } from "./util.js";
import { state } from "../state.js";
import { reportCounts } from "./kpis.js";

export const FILTERS = [
  { key: "all", label: "All" },
  { key: "pending_verification", label: "Pending verification" },
  { key: "verified", label: "Verified" },
  { key: "dismissed", label: "Dismissed" },
];

export function FilterTabs() {
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
