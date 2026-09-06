import { state } from "../state.js";
import { shortId, timeAgo, titleCase } from "../helpers.js";
import { TableHead, EmptyState, ACTIVITY_PAGE_SIZE, ChevronRight } from "./util.js";
import { PaginationBar } from "/shared/components/pagination/pagination.js";
import { mapCase } from "./queues.js";

export function ActivityInner() {
  const list = state.activity.map(mapCase);
  if (list.length === 0) {
    return `<div class="activity-empty">${EmptyState({ icon: "list", text: "No records." })}</div>`;
  }
  const page = state.activityPage || 1;
  const perPage = state.activityPageSize || ACTIVITY_PAGE_SIZE;
  const start = (page - 1) * perPage;
  const rows =
    list.slice(start, start + perPage).map(
      (r) => `
    <tr>
      <td class="table-cell table-cell--mono table-cell--strong">${r.id}</td>
      <td class="table-cell">${r.animal}</td>
      <td class="table-cell">${r.brgy}</td>
      <td class="table-cell">${r.rescuer}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${r.statusCls}">${r.status}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${r.when}</td>
    </tr>`
    ).join("");
  const pagination = `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage, page })}</div>`;
  return `
    <div class="table-wrap">
      <table class="table">
        ${TableHead(["Case", "Animal", "Barangay", "Rescuer", "Status", "Updated"])}
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}

export function ActivityTable() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="list"></i><h2 class="panel-title">Recent case activity</h2></div>
      <a href="/admin/cases/" class="btn-link">View all cases ${ChevronRight()}</a>
    </div>
    <div id="activity-table" class="activity-table">${ActivityInner()}</div>
  </div>`;
}
