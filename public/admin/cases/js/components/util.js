import { shortId, timeAgo, titleCase, initials } from "/admin/js/helpers.js";
import { state } from "../state.js";

export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function caseStampCls(status) {
  if (status === "in_progress" || status === "resolved") return "stamp--accent";
  return "stamp--coral";
}

export function enrich(c) {
  const report = c.report_id ? state.reports.find((r) => r.id === c.report_id) : null;
  const rescuer = c.assigned_rescuer_id
    ? state.rescuers.find((u) => u.id === c.assigned_rescuer_id)
    : null;
  const status = String(c.status || "open");
  const lat = Number(c.latitude != null ? c.latitude : report && report.latitude);
  const lng = Number(c.longitude != null ? c.longitude : report && report.longitude);
  return {
    id: c.id,
    shortId: shortId(c.id),
    status: titleCase(status),
    statusCls: caseStampCls(status),
    statusRaw: status,
    report,
    rescuer,
    brgy: report ? report.address_text || "—" : "—",
    animal: report ? report.animal_description || "—" : "—",
    lat: Number.isFinite(lat) ? lat : null,
    lng: Number.isFinite(lng) ? lng : null,
    when: timeAgo(c.created_at),
    updated: timeAgo(c.updated_at || c.created_at),
    createdAt: c.created_at,
    updatedAt: c.updated_at || c.created_at,
  };
}

export function getCase(id) {
  return state.cases.find((c) => c.id === id) || null;
}
