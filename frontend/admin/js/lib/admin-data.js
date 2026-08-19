// Backend data access for the admin console.
// Uses apiFetchFull so paginated endpoints keep meta.total alongside items.
import { apiFetchFull } from "../../../js/lib/api.js";

async function list(path, perPage = 100) {
  const sep = path.includes("?") ? "&" : "?";
  const payload = await apiFetchFull(`${path}${sep}per_page=${perPage}`);
  const items = Array.isArray(payload.data) ? payload.data : [];
  return { items, total: (payload.meta && payload.meta.total) ?? items.length };
}

async function raw(path) {
  const payload = await apiFetchFull(path);
  return payload.data;
}

export const fetchOverview = () =>
  raw("/analytics/overview").then((d) => (d && d.stats) || null);

export const fetchReports = (status = "pending_verification") =>
  list(`/reports?status=${status}`);

export const fetchRescuerApplicants = () =>
  list("/users?role=rescuer&account_status=pending");

export const fetchRescuers = () =>
  list("/users?role=rescuer&account_status=active");

export const fetchAdoptions = (status = "pending") =>
  list(`/adoptions?status=${status}`);

export const fetchCases = () => list("/cases");

export const fetchNotifications = () =>
  list("/notifications?is_read=false");

export const fetchElearning = async () => {
  const published = await list("/elearning/modules?published_status=published");
  const drafts = await list("/elearning/modules?published_status=draft");
  return { published: published.total, drafts: drafts.total, items: published.items };
};

export const fetchAdoptionTrends = () =>
  raw("/analytics/adoption-trends").then((d) => (d && d.trends) || []);

export const fetchHealthUpdates = () =>
  raw("/health/updates").then((d) => (d && d.updates) || []);

export const fetchHeatmap = () =>
  raw("/reports/map/heatmap").then((d) => (d && d.points) || []);