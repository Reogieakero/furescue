import { Search } from "/shared/components/search/search.js";
import { Toolbar } from "/shared/components/toolbar/toolbar.js";
import { FilterTabs as TabStrip, FilterTab } from "/shared/components/filter-tabs/filter-tabs.js";
import { state } from "../state.js";
import { userCounts } from "./kpis.js";

export const FILTERS = [
  { key: "all", label: "All" },
  { key: "admin", label: "Admin" },
  { key: "rescuer", label: "Rescuer" },
  { key: "resident", label: "Resident" },
  { key: "pending", label: "Pending" },
  { key: "suspended", label: "Suspended" },
];

export function FilterTabs() {
  const c = userCounts();
  return Toolbar({
    children: `
    ${TabStrip({
      id: "user-tabs",
      children: FILTERS.map((f) =>
        FilterTab({
          key: f.key,
          label: `${f.label} &middot; ${c[f.key] ?? 0}`,
          active: state.filter === f.key,
        })
      ).join(""),
    })}
    ${Search({ id: "user-search", placeholder: "Search name, email, phone…", value: state.query })}`,
  });
}
