import * as api from "../../lib/admin-data.js";
import { safe } from "../../pages/dashboard/helpers.js";
import { readCache, writeCache, setNavBadge } from "../../../../js/lib/swr.js";

const CACHE_KEY = "page:rescuers";

export const state = {
  rescuers: [],
  pending: [],
  reports: [],
  caseActivity: {},
  filter: "all",
  query: "",
  page: 1,
  selectedId: null,
  selectedRescuer: undefined,
  selectedRescuerCases: [],
};

export async function loadRescuers() {
  const [active, suspended, pending, reports] = await Promise.all([
    safe(api.fetchRescuers(), { items: [], total: 0 }),
    safe(api.fetchSuspendedRescuers(), { items: [], total: 0 }),
    safe(api.fetchPendingRescuers(), { items: [], total: 0 }),
    safe(api.fetchAllReports(), { items: [] }),
  ]);
  state.rescuers = [...(active.items || []), ...(suspended.items || [])];
  state.pending = pending.items || [];
  state.reports = reports.items || [];
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
  setNavBadge("rescuers", state.pending.length);
}
