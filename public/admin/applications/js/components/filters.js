import { Search } from "/shared/components/search/search.js";
import { Toolbar } from "/shared/components/toolbar/toolbar.js";
import { FilterTabs as TabStrip, FilterTab } from "/shared/components/filter-tabs/filter-tabs.js";
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
  return TabStrip({
    id: "application-tabs",
    children: FILTERS.map((f) =>
      FilterTab({
        key: f.key,
        label: `${f.label} &middot; ${count[f.key]}`,
        active: state.filter === f.key,
        attrs: 'type="button"',
      })
    ).join(""),
  });
}

export function FilterToolbar() {
  return Toolbar({
    children: Search({ id: "application-search", placeholder: "Search applicant, animal, message…", value: state.query }),
  });
}
