import { PaginationBar } from "/assets/js/components/ui/pagination.js";
import { Button } from "/assets/js/components/ui/button.js";
import { esc, stampCls, applicantName, animalName } from "./util.js";
import { shortId, timeAgo, titleCase, truncate } from "/admin/js/helpers.js";
import { state } from "../state.js";

export const PAGE_SIZE = 15;

export function filteredApplications() {
  const q = state.query.trim().toLowerCase();
  let list = state.items;
  if (state.filter !== "all") list = list.filter((a) => a.status === state.filter);
  if (q) {
    list = list.filter((a) =>
      [shortId(a.id), applicantName(a), animalName(a), a.message, shortId(a.applicant_id), shortId(a.animal_id)]
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  return list;
}

export function actionLinks(a) {
  const view = Button({
    text: "View application",
    variant: "outline",
    size: "sm",
    icon: "eye",
    attrs: `data-action="view" data-id="${a.id}"`,
  });
  if (a.status === "pending") {
    return [
      view,
      Button({
        text: "Reject",
        variant: "destructive",
        size: "sm",
        icon: "file-x",
        attrs: `data-action="reject" data-id="${a.id}"`,
      }),
    ].join("");
  }
  return view;
}

function emptyCopy() {
  if (state.loadError) return { icon: "alert-triangle", text: state.loadError };
  if (!state.items.length) return { icon: "home", text: "No adoption applications yet." };
  return { icon: "home", text: "No applications match." };
}

export function ApplicationTable() {
  if (state.loadError && !state.items.length) {
    const empty = emptyCopy();
    return `
    <div class="queue-empty">
      <div class="empty-state"><i data-lucide="${empty.icon}"></i><span>${esc(empty.text)}</span></div>
      ${Button({ text: "Retry", variant: "outline", size: "sm", icon: "refresh-cw", attrs: 'data-action="retry"' })}
    </div>`;
  }
  const list = filteredApplications();
  if (list.length === 0) {
    const empty = emptyCopy();
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="${empty.icon}"></i><span>${esc(empty.text)}</span></div></div>`;
  }
  const start = (state.page - 1) * PAGE_SIZE;
  const rows = list
    .slice(start, start + PAGE_SIZE)
    .map((a) => {
      const name = applicantName(a) || shortId(a.applicant_id);
      const animal = animalName(a) || shortId(a.animal_id);
      const msg = a.message && String(a.message).trim() ? truncate(a.message, 28) : "—";
      const selected = a.id === state.selectedId ? ' class="is-selected"' : "";
      return `
    <tr data-id="${esc(a.id)}"${selected}>
      <td class="table-cell table-cell--strong">${esc(name)}</td>
      <td class="table-cell">${esc(animal)}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${stampCls(a.status)}">${esc(titleCase(a.status))}</span></td>
      <td class="table-cell">${esc(msg)}</td>
      <td class="table-cell table-cell--mono table-cell--muted">${esc(timeAgo(a.created_at))}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">${actionLinks(a)}</span>
      </td>
    </tr>`;
    })
    .join("");
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Applicant</th><th>Animal</th><th>Status</th><th>Message</th><th>Submitted</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}
