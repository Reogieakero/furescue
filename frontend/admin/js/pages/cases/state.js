import * as api from "../../lib/admin-data.js";
import { safe } from "../dashboard/helpers.js";
import { readCache, writeCache, setNavBadge } from "../../../../js/lib/swr.js";

const CACHE_KEY = "page:cases";

export const state = {
  cases: [],
  reports: [],
  rescuers: [],
  heatmap: [],
  filter: "in_progress",
  query: "",
  sort: "",   page: 1,
};

export async function loadCases() {
  const [cases, rescuers, reports, heatmap] = await Promise.all([
    safe(api.fetchCases(), { items: [] }),
    safe(api.fetchRescuers(), { items: [] }),
    safe(api.fetchAllReports(), { items: [] }),
    safe(api.fetchHeatmap(), []),
  ]);
  state.cases = cases.items || [];
  state.rescuers = rescuers.items || [];
  state.reports = reports.items || [];
  state.heatmap = heatmap || [];
  persistCache();
}

export function hydrateFromCache() {
  const snap = readCache(CACHE_KEY);
  if (!snap) return false;
  Object.assign(state, snap);
  return true;
}

export function persistCache() {
  try {
    writeCache(CACHE_KEY, JSON.parse(JSON.stringify(state)));
  } catch {}
  setNavBadge("cases", state.cases.length);
}

export async function reloadData() {
  const [cases, rescuers, reports, heatmap] = await Promise.all([
    safe(api.fetchCases(), { items: [] }),
    safe(api.fetchRescuers(), { items: [] }),
    safe(api.fetchAllReports(), { items: [] }),
    safe(api.fetchHeatmap(), []),
  ]);
  state.cases = cases.items || [];
  state.rescuers = rescuers.items || [];
  state.reports = reports.items || [];
  state.heatmap = heatmap || [];
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
