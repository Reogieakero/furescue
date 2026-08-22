import * as api from "../../lib/admin-data.js";
import { safe } from "../../pages/dashboard/helpers.js";
import { readCache, writeCache, setNavBadge } from "../../../../js/lib/swr.js";

const CACHE_KEY = "page:rescuers";
const SELECTION_KEY = "furescue:rescuer:selection";

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
  persistSelection();
  setNavBadge("rescuers", state.pending.length);
}

export function persistSelection() {
  try {
    localStorage.setItem(
      SELECTION_KEY,
      JSON.stringify({
        selectedId: state.selectedId,
        page: state.page,
        selectedRescuer: state.selectedRescuer ?? null,
        selectedRescuerCases: state.selectedRescuerCases ?? [],
      })
    );
  } catch {}
}

export function hydrateSelection() {
  try {
    const raw = localStorage.getItem(SELECTION_KEY);
    if (!raw) return false;
    const data = JSON.parse(raw);
    if (data.selectedId) state.selectedId = data.selectedId;
    if (typeof data.page === "number") state.page = data.page;
    if (data.selectedRescuer) state.selectedRescuer = data.selectedRescuer;
    if (Array.isArray(data.selectedRescuerCases)) state.selectedRescuerCases = data.selectedRescuerCases;
    return Boolean(state.selectedId);
  } catch {
    return false;
  }
}
