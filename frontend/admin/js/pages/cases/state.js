import * as api from "../../lib/admin-data.js";
import { safe } from "../dashboard/helpers.js";

export const state = {
  cases: [],
  reports: [],
  rescuers: [],
  filter: "in_progress",
  query: "",
  sort: "",   page: 1,
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

export function loadFilterPref() {
  try {
    const v = localStorage.getItem(FILTER_PREF_KEY);
    if (v && VALID_FILTERS.includes(v)) state.filter = v;
  } catch {

  }
}

export function saveFilterPref(filter) {
  try {
    localStorage.setItem(FILTER_PREF_KEY, filter);
  } catch {

  }
}
