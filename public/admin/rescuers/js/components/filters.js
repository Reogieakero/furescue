import { Search } from "/shared/components/search/search.js";
import { Toolbar } from "/shared/components/toolbar/toolbar.js";
import { FilterTabs as TabStrip, FilterTab } from "/shared/components/filter-tabs/filter-tabs.js";
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
  return Toolbar({
    children: `
    ${TabStrip({
      id: "rescuer-tabs",
      children: FILTERS.map((f) =>
        FilterTab({ key: f.key, label: `${f.label} &middot; ${count[f.key]}`, active: state.filter === f.key })
      ).join(""),
    })}
    ${Search({ id: "rescuer-search", placeholder: "Search name, email, phone…", value: state.query })}`,
  });
}
