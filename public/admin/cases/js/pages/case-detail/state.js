import * as api from "/admin/js/lib/admin-data.js";
import { safe } from "/admin/js/pages/dashboard/helpers.js";
import { photos } from "./components/util.js";
import { readCache, writeCache } from "/js/lib/swr.js";

const CACHE_PREFIX = "page:case-detail:";

export const state = {
  caseId: null,
  caseData: null,
  report: null,
  rescuer: null,
  activity: [],
  attachments: [],
  proof: [],
  error: null,
};

export async function loadCaseDetail(caseId) {
  state.caseId = caseId;
  state.caseData = null;
  state.report = null;
  state.rescuer = null;
  state.activity = [];
  state.attachments = [];
  state.proof = [];
  state.error = null;

  const [caseData, activity] = await Promise.all([
    safe(api.fetchCase(caseId), null),
    safe(api.fetchCaseActivity(caseId), []),
  ]);

  state.caseData = caseData;
  state.activity = Array.isArray(activity) ? activity : [];

  if (!state.caseData) {
    state.error = "Case not found.";
    return;
  }

  const [report, rescuer] = await Promise.all([
    caseData.report_id
      ? safe(api.fetchReport(caseData.report_id), null)
      : Promise.resolve(null),
    caseData.assigned_rescuer_id
      ? safe(api.fetchUser(caseData.assigned_rescuer_id), null)
      : Promise.resolve(null),
  ]);

  // fetchUser returns { user: {...} }; unwrap once so derived fields read the
  // user object (not the raw payload) — the cases list reads state.rescuers,
  // so this keeps the detail page consistent with the cases page.
  const user = rescuer ? (rescuer.user || rescuer) : null;
  state.report = report;
  state.rescuer = user;
  state.attachments = photos(report && report.photo_urls);
  state.proof = photos(caseData.resolution_photos);

  // expose derived fields for components that read caseData.*
  state.caseData.report = report;
  state.caseData.rescuer = user;
  state.caseData.rescuer_name = user ? user.full_name : null;
  state.caseData.rescuer_photo = user ? user.profile_photo_url : null;

  state.error = null;
  persistCache(caseId);
}

export function hydrateFromCache(caseId) {
  const snap = readCache(CACHE_PREFIX + caseId);
  if (!snap) return false;
  Object.assign(state, snap);
  return true;
}

export function persistCache(caseId) {
  try {
    writeCache(CACHE_PREFIX + caseId, JSON.parse(JSON.stringify(state)));
  } catch {}
}
