// Cases page state — populated entirely from the FurEscue API.
import * as api from "../../lib/admin-data.js";
import { safe } from "../dashboard/helpers.js";

export const state = {
  cases: [],
  reports: [],
  rescuers: [],
  filter: "in_progress",
  query: "",
  sort: "", // empty = default (newest); the select shows "Sort" as its placeholder
  page: 1,
};

export async function loadCases() {
  const [cases, rescuers, reports] = await Promise.all([
    safe(api.fetchCases(), { items: [] }),
    safe(api.fetchRescuers(), { items: [] }),
    safe(api.fetchAllReports(), { items: [] }),
  ]);
  state.cases = cases.items || [];
  state.rescuers = rescuers.items || [];
  state.reports = reports.items || [];
}

// Re-fetches cases + rescuers + reports after an admin action, keeping the
// current filter/query/page intact.
export async function reloadData() {
  const [cases, rescuers, reports] = await Promise.all([
    safe(api.fetchCases(), { items: [] }),
    safe(api.fetchRescuers(), { items: [] }),
    safe(api.fetchAllReports(), { items: [] }),
  ]);
  state.cases = cases.items || [];
  state.rescuers = rescuers.items || [];
  state.reports = reports.items || [];
}

const FILTER_PREF_KEY = "furescue.cases.filter";
const VALID_FILTERS = ["all", "open", "assigned", "in_progress", "resolved"];

// Restore the last-used status tab from localStorage so the view persists
// across page reloads.
export function loadFilterPref() {
  try {
    const v = localStorage.getItem(FILTER_PREF_KEY);
    if (v && VALID_FILTERS.includes(v)) state.filter = v;
  } catch {
    /* localStorage unavailable (private mode / file://) — keep default */
  }
}

export function saveFilterPref(filter) {
  try {
    localStorage.setItem(FILTER_PREF_KEY, filter);
  } catch {
    /* ignore */
  }
}
