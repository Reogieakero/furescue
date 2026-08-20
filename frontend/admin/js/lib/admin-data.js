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

export const fetchAllReports = () => list("/reports");

export const fetchAdoptions = (status = "pending") =>
  list(`/adoptions?status=${status}`);

export const fetchCases = () => list("/cases");

export const fetchCase = (id) =>
  raw(`/cases/${id}`).then((d) => (d && d.case) || null);

export const fetchReport = (id) =>
  raw(`/reports/${id}`).then((d) => (d && d.report) || null);

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

export const reverseGeocode = (lat, lng) =>
  raw(`/geo/reverse?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`);

async function post(path, body = {}) {
  return apiFetchFull(path, { method: "POST", body });
}

async function patch(path, body = {}) {
  return apiFetchFull(path, { method: "PATCH", body });
}

export const fetchRescuers = () =>
  list("/users?role=rescuer&account_status=active");

export const fetchRescuerApplicants = () =>
  list("/users?role=rescuer&account_status=pending");

export const fetchPendingRescuers = () =>
  list("/users?role=rescuer&account_status=pending");

export const fetchSuspendedRescuers = () =>
  list("/users?role=rescuer&account_status=suspended");

export const fetchUser = (id) => raw(`/users/${id}`);

export const setUserStatus = (id, status) =>
  patch(`/users/${id}`, { account_status: status });

export const assignRescuer = (caseId, rescuerId) =>
  post(`/cases/${caseId}/assign`, { rescuer_id: rescuerId });

export const updateCaseStatus = (caseId, status) =>
  patch(`/cases/${caseId}/status`, { status });

export const fetchCaseActivity = (caseId) =>
  raw(`/cases/${caseId}/activity`).then((d) => (d && d.activity) || []);

export const verifyReport = (id) => post(`/reports/${id}/verify`);
export const dismissReport = (id, reason) =>
  post(`/reports/${id}/dismiss`, { dismiss_reason: reason });
export const approveRescuer = (id, remarks = null) =>
  post(`/admin/rescuers/${id}/approve`, remarks ? { remarks } : {});
export const rejectRescuer = (id, remarks = null) =>
  post(`/admin/rescuers/${id}/reject`, remarks ? { remarks } : {});
export const approveAdoption = (id) => post(`/adoptions/${id}/approve`);
export const rejectAdoption = (id, reason) =>
  post(`/adoptions/${id}/reject`, { rejection_reason: reason });
