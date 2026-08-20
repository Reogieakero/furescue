import { Badge } from "../../../../../js/components/ui/badge.js";
import { esc, caseStampCls } from "./util.js";
import { shortId, titleCase, timeAgo } from "../../dashboard/helpers.js";
import { state } from "../state.js";

function InfoRow(label, value) {
  return `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(label)}</span>
      <span class="dialog-info-value">${value}</span>
    </div>`;
}

export function renderCaseInfo(caseData) {
  const c = caseData;
  const rescuer = c.assigned_rescuer_id
    ? Badge({ text: c.rescuer_name || "Unassigned", variant: "secondary", icon: "user" })
    : Badge({ text: "Unassigned", variant: "outline" });
  const rows = [
    InfoRow("Case", shortId(c.id)),
    InfoRow("Status", `<span class="stamp stamp--sm ${caseStampCls(c.status)}">${esc(titleCase(c.status))}</span>`),
    InfoRow("Rescuer", rescuer),
    InfoRow("Source report", c.report_id ? shortId(c.report_id) : "—"),
    InfoRow("Created", c.created_at ? timeAgo(c.created_at) : "—"),
    InfoRow("Updated", c.updated_at ? timeAgo(c.updated_at) : "—"),
  ];
  return `
    <div class="panel case-detail-panel">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="clipboard-list"></i>
          <h2 class="panel-title">Case details</h2>
        </div>
      </div>
      <div class="panel-body"><div class="dialog-info">${rows.join("")}</div></div>
    </div>`;
}

export function renderSourceReport(caseData) {
  const report = caseData.report || state.report;
  if (!report) {
    return `
      <div class="panel case-detail-panel">
        <div class="panel-head"><div class="panel-title-wrap"><i data-lucide="file-text"></i><h2 class="panel-title">Source report</h2></div></div>
        <div class="panel-body"><div class="empty-state"><i data-lucide="file-x"></i><span>No linked report.</span></div></div>
      </div>`;
  }
  const animal = report.animal_description || "—";
  const location = report.address_text || "—";
  return `
    <div class="panel case-detail-panel">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="file-text"></i>
          <h2 class="panel-title">Source report &middot; ${esc(shortId(report.id))}</h2>
        </div>
      </div>
      <div class="panel-body">
        <div class="dialog-info">
          ${InfoRow("Animal", esc(animal))}
          ${InfoRow("Location", location ? esc(location) : "—")}
          ${InfoRow("Validation", esc(report.validation_status || "—"))}
          ${InfoRow("Report status", esc(report.status || "—"))}
        </div>
      </div>
    </div>`;
}
