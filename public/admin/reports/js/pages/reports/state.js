import * as api from "/admin/js/lib/admin-data.js";
import { safe } from "/admin/js/pages/dashboard/helpers.js";

export const state = {
  overview: null,
  reports: [],
  cases: [],
  rescuers: [],
  filter: "all",
  query: "",
  sort: "assigned",
  page: 1,
};

const EMPTY_OVERVIEW = {
  reports: 0,
  cases: 0,
  cases_resolved: 0,
  rescuers_on_duty: 0,
  reports_verified: 0,
};

export async function loadReports() {
  const [overview, reports, cases, rescuers] = await Promise.all([
    safe(api.fetchOverview(), null),
    safe(api.fetchAllReports(), { items: [], total: 0 }),
    safe(api.fetchCases(), { items: [] }),
    safe(api.fetchRescuers(), { items: [] }),
  ]);
  state.overview = overview || { ...EMPTY_OVERVIEW };
  state.reports = reports.items || [];
  state.cases = cases.items || [];
  state.rescuers = rescuers.items || [];
}

export async function reloadData() {
  const [overview, reports, cases] = await Promise.all([
    safe(api.fetchOverview(), null),
    safe(api.fetchAllReports(), { items: [], total: 0 }),
    safe(api.fetchCases(), { items: [] }),
  ]);
  state.overview = overview || state.overview || { ...EMPTY_OVERVIEW };
  state.reports = reports.items || [];
  state.cases = cases.items || [];
}
