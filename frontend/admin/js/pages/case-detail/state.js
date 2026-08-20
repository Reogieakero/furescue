import * as api from "../../lib/admin-data.js";
import { safe } from "../dashboard/helpers.js";

export const state = {
  caseId: null,
  caseData: null,
  report: null,
  activity: [],
  rescuers: [],
  attachments: [],
  proof: [],
  error: null,
};

export async function loadCaseDetail(caseId) {
  state.caseId = caseId;
  state.caseData = null;
  state.error = null;

  const [caseData, rescuers, activity] = await Promise.all([
    safe(api.fetchCases().then((r) => (r.items || []).find((c) => c.id === caseId) || null), null),
    safe(api.fetchRescuers(), { items: [] }),
    safe(api.fetchCaseActivity(caseId), { items: [] }),
  ]);

  state.caseData = caseData;
  state.rescuers = rescuers.items || [];
  state.activity = activity.items || [];

  if (!state.caseData) {
    state.error = "Case not found.";
    return;
  }

  if (state.caseData.report_id) {
    state.report =
      (state.activity || []).find((a) => a.id === state.caseData.report_id) || null;
  }
  if (state.caseData.assigned_rescuer_id) {
    state.rescuer =
      state.rescuers.find((u) => u.id === state.caseData.assigned_rescuer_id) || null;
  }

  state.attachments = state.caseData.attachments || [];
  state.proof = state.caseData.proof || [];
  state.error = null;
}
