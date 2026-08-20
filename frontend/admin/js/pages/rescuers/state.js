import * as api from "../../lib/admin-data.js";
import { safe } from "../../pages/dashboard/helpers.js";

export const state = {
  rescuers: [],
  pending: [],
  filter: "all",
  query: "",
  page: 1,
};

export async function loadRescuers() {
  const [active, suspended, pending] = await Promise.all([
    safe(api.fetchRescuers(), { items: [], total: 0 }),
    safe(api.fetchSuspendedRescuers(), { items: [], total: 0 }),
    safe(api.fetchPendingRescuers(), { items: [], total: 0 }),
  ]);
  state.rescuers = [...(active.items || []), ...(suspended.items || [])];
  state.pending = pending.items || [];
}
