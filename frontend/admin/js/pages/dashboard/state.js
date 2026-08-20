import * as api from "../../lib/admin-data.js";
import { safe, buildWeekChart } from "./helpers.js";

const EMPTY_OVERVIEW = {
  reports: 0,
  reports_verified: 0,
  cases: 0,
  cases_resolved: 0,
  animals: 0,
  animals_adopted: 0,
  adoptions_pending: 0,
  adoptions_completed: 0,
  rescuers_on_duty: 0,
  residents: 0,
};

export const state = {
  overview: { ...EMPTY_OVERVIEW },
  reports: [],
  reportsTotal: 0,
  reportsPending: { items: [], total: 0 },
  rescuersPending: { items: [], total: 0 },
  healthUpdates: { items: [], total: 0 },
  adoptionsPending: { items: [], total: 0 },
  rescuers: [],
  activity: [],
  chart: [],
  growth: null,
  elearning: { published: 0, drafts: 0, items: [] },
  notifications: { items: [], total: 0 },
  heatmap: [],
  decisionCount: 0,
  activityPage: 1,
};

export const queueState = { reports: 1, rescuers: 1, health: 1, adopt: 1 };

export async function loadDashboard() {
  const [overview, reports, allReports, rescuersPending, rescuers, adoptions, cases, notifications, elearning, trends, heatmap, healthUpdates] =
    await Promise.all([
      safe(api.fetchOverview(), null),
      safe(api.fetchReports("pending_verification"), { items: [], total: 0 }),
      safe(api.fetchAllReports(), { items: [], total: 0 }),
      safe(api.fetchRescuerApplicants(), { items: [], total: 0 }),
      safe(api.fetchRescuers(), { items: [] }),
      safe(api.fetchAdoptions("pending"), { items: [], total: 0 }),
      safe(api.fetchCases(), { items: [] }),
      safe(api.fetchNotifications(), { items: [] }),
      safe(api.fetchElearning(), null),
      safe(api.fetchAdoptionTrends(), []),
      safe(api.fetchHeatmap(), []),
      safe(api.fetchHealthUpdates(), []),
    ]);

  const updates = healthUpdates || [];
  const chart = buildWeekChart(trends);
  const reportList = allReports.items || [];
  const caseList = cases.items || [];
  const rescuerList = rescuers.items || [];

  state.reports = reportList;
  state.reportsTotal = allReports.total || reportList.length;
  state.reportsPending = { items: reports.items || [], total: reports.total || 0 };
  state.rescuersPending = { items: rescuersPending.items || [], total: rescuersPending.total || 0 };
  state.adoptionsPending = { items: adoptions.items || [], total: adoptions.total || 0 };
  state.healthUpdates = { items: updates, total: updates.length };
  state.rescuers = rescuerList;
  state.activity = caseList;
  state.elearning = elearning || { published: 0, drafts: 0, items: [] };
  state.notifications = { items: notifications.items || [], total: notifications.total || 0 };
  state.heatmap = heatmap || [];
  state.chart = chart.bars;
  state.growth = chart.growth;

  if (overview) {
    state.overview = { ...overview };
  } else {
    state.overview = {
      ...EMPTY_OVERVIEW,
      reports: state.reportsTotal,
      cases: caseList.length,
      cases_resolved: caseList.filter((c) => c.status === "resolved").length,
      adoptions_pending: state.adoptionsPending.total,
      rescuers_on_duty: rescuerList.filter((u) => (u.duty_status || "off_duty") === "on_duty").length,
    };
  }

  state.decisionCount =
    state.reportsPending.total + state.rescuersPending.total + state.healthUpdates.total + state.adoptionsPending.total;
}

export async function refreshQueue(key) {
  const fetchers = {
    reports: api.fetchReports("pending_verification"),
    rescuers: api.fetchRescuerApplicants(),
    adopt: api.fetchAdoptions("pending"),
  };
  const result = await safe(fetchers[key], { items: [], total: 0 });
  const overview = await safe(api.fetchOverview(), null);

  if (key === "reports") state.reportsPending = { items: result.items || [], total: result.total || 0 };
  if (key === "rescuers") state.rescuersPending = { items: result.items || [], total: result.total || 0 };
  if (key === "adopt") state.adoptionsPending = { items: result.items || [], total: result.total || 0 };
  if (overview) state.overview = overview;
  state.decisionCount =
    state.reportsPending.total + state.rescuersPending.total + state.healthUpdates.total + state.adoptionsPending.total;
}
