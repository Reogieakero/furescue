import { state } from "../state.js";
import { TableHead, EmptyState, ChevronRight } from "./util.js";
import {
  displayStatus,
  formatDateTime,
  formatReportId,
  reportTypeLabel,
  statusPill,
  esc,
} from "../insights.js";

export function RecentReportsInner() {
  const list = (state.reports || []).slice(0, 8);
  if (!list.length) {
    return `<div class="queue-empty">${EmptyState({ icon: "file-text", text: "No reports yet." })}</div>`;
  }
  const rows = list
    .map((r) => {
      const href = r.case_id
        ? `/admin/cases/case-detail.php?id=${encodeURIComponent(r.case_id)}`
        : "/admin/reports/";
      return `
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">${esc(formatReportId(r.id, r.created_at))}</td>
      <td class="table-cell">${esc(reportTypeLabel(r.animal_description))}</td>
      <td class="table-cell">${esc(r.address_text || "—")}</td>
      <td class="table-cell">${esc(r.resident_name || "Resident")}</td>
      <td class="table-cell table-cell--nowrap">${esc(formatDateTime(r.created_at))}</td>
      <td class="table-cell">${statusPill(displayStatus(r))}</td>
      <td class="table-cell table-cell--right">
        <a class="dash-icon-btn" href="${href}" aria-label="View report"><i data-lucide="eye"></i></a>
      </td>
    </tr>`;
    })
    .join("");
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Report ID", "Type", "Location", "Submitted By", "Date & Time", "Status", "Action"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    <div class="panel-foot"><a href="/admin/reports/" class="dash-link">View All Reports ${ChevronRight()}</a></div>`;
}

export function RecentReportsCard() {
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h2 class="panel-title">Recent Reports</h2></div>
    </div>
    <div id="recent-reports">${RecentReportsInner()}</div>
  </section>`;
}
