import { esc } from "./util.js";
import { state } from "../state.js";
import { rescuerCounts } from "./kpis.js";

export const FILTERS = [
  { key: "all", label: "All" },
  { key: "active", label: "Active" },
  { key: "on_duty", label: "On duty" },
  { key: "off_duty", label: "Off duty" },
  { key: "pending", label: "Pending" },
];

export function FilterTabs() {
  const c = rescuerCounts();
  const count = {
    all: c.total,
    active: c.active,
    on_duty: c.onDuty,
    off_duty: c.offDuty,
    pending: c.pending,
  };
  return `
  <div class="report-toolbar">
    <div class="q-tabs" id="rescuer-tabs">
      ${FILTERS.map(
        (f) =>
          `<button data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
      ).join("")}
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="rescuer-search" type="text" placeholder="Search name, email, phone…" value="${esc(state.query)}">
    </div>
  </div>`;
}
