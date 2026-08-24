import { PaginationBar } from "/js/components/ui/pagination.js";
import { Button } from "/js/components/ui/button.js";
import { stampCls } from "./util.js";
import { shortId, timeAgo, titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { state } from "../state.js";

const PAGE_SIZE = 15;

export function enrich(r) {
  const c = state.cases.find((x) => x.report_id === r.id) || null;
  const rescuer =
    c && c.assigned_rescuer_id ? state.rescuers.find((u) => u.id === c.assigned_rescuer_id) : null;
  return {
    rid: r.id,
    id: shortId(r.id),
    brgy: r.address_text || "—",
    reporter: shortId(r.resident_id),
    status: titleCase(r.status),
    statusCls: stampCls(r.status),
    when: timeAgo(r.created_at),
    caseStatus: c ? titleCase(c.status) : null,
    caseStatusCls: c ? stampCls(c.status) : "",
    resolved: !!(c && c.status === "resolved"),
    rescuer: rescuer ? rescuer.full_name : c && c.assigned_rescuer_id ? "Assigned" : "—",
  };
}

export function actionLinks(r) {
  if (r.status === "pending_verification") {
    return [
      Button({ text: "Verify", variant: "default", size: "sm", icon: "badge-check", attrs: `data-action="verify" data-id="${r.id}"` }),
      Button({ text: "Dismiss", variant: "destructive", size: "sm", icon: "file-x", attrs: `data-action="dismiss" data-id="${r.id}"` }),
    ].join("");
  }
  if (r.status === "verified") {
    const c = state.cases.find((x) => x.report_id === r.id) || null;
    if (!c) return "";
    const timeline = Button({ text: "Timeline", variant: "outline", size: "sm", icon: "history", attrs: `data-action="timeline" data-id="${r.id}" data-case="${c.id}"` });
    if (!c.assigned_rescuer_id) {
      return `${Button({ text: "Assign rescuer", variant: "default", size: "sm", icon: "user-plus", attrs: `data-action="assign" data-id="${r.id}" data-case="${c.id}"` })}${timeline}`;
    }
    if (c.status === "assigned") {
      return `${Button({ text: "Mark in progress", variant: "default", size: "sm", icon: "play", attrs: `data-action="progress" data-id="${r.id}" data-case="${c.id}"` })}${timeline}`;
    }
    if (c.status === "in_progress") {
      return `${Button({ text: "Resolve", variant: "default", size: "sm", icon: "check-circle-2", attrs: `data-action="resolve" data-id="${r.id}" data-case="${c.id}"` })}${timeline}`;
    }
    return timeline;
  }
  return "";
}

function caseStatusRank(c) {
  if (!c) return 0;
  return { open: 1, assigned: 2, in_progress: 3, resolved: 4 }[c.status] ?? 4;
}

function sortKey(r) {
  const c = state.cases.find((x) => x.report_id === r.id) || null;
  const ts = -new Date(r.created_at).getTime();
  if (state.sort === "assigned") {
    const assigned = c && c.assigned_rescuer_id ? 0 : 1;
    return [assigned, caseStatusRank(c), ts];
  }
  if (state.sort === "verified") {
    const verified = r.status === "verified" ? 0 : 1;
    return [verified, ts];
  }
  return [ts];
}

function cmp(a, b) {
  for (let i = 0; i < a.length; i++) {
    if (a[i] < b[i]) return -1;
    if (a[i] > b[i]) return 1;
  }
  return 0;
}

function filteredReports() {
  const q = state.query.trim().toLowerCase();
  let list = state.reports;
  if (state.filter !== "all") list = list.filter((r) => r.status === state.filter);
  if (q) {
    list = list.filter((r) =>
      [shortId(r.id), r.address_text, r.animal_description, shortId(r.resident_id)]
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  return list.sort((a, b) => cmp(sortKey(a), sortKey(b)));
}

export function ReportTable() {
  const list = filteredReports();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="file-text"></i><span>No reports match.</span></div></div>`;
  }
  const start = (state.page - 1) * PAGE_SIZE;
  const rows = list
    .slice(start, start + PAGE_SIZE)
    .map((r) => {
      const v = enrich(r);
      return `
    <tr data-id="${r.id}" class="${v.resolved ? "row--resolved" : ""}">
      <td class="table-cell table-cell--mono table-cell--strong">${v.id}</td>
      <td class="table-cell">${v.brgy}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${v.reporter}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${v.statusCls}">${v.status}</span></td>
      <td class="table-cell">${v.caseStatus ? `<span class="stamp stamp--sm ${v.caseStatusCls}">${v.caseStatus}</span>` : "—"}</td>
      <td class="table-cell">${v.rescuer}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${v.when}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">${actionLinks(r)}</span>
      </td>
    </tr>`;
    })
    .join("");
  const pageTotal = Math.max(1, Math.ceil(list.length / PAGE_SIZE));
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Case</th><th>Barangay</th><th>Reporter</th><th>Status</th><th>Case status</th><th>Rescuer</th><th>Submitted</th><th>Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}
