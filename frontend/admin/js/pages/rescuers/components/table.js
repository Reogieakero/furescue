import { PaginationBar } from "../../../../../js/components/ui/pagination.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { esc, dutyStampCls, dutyLabel } from "./util.js";
import { shortId, timeAgo } from "../../dashboard/helpers.js";
import { rescuerAvatar } from "../../dashboard/components/util.js";
import { state } from "../state.js";

const PAGE_SIZE = 15;

function filteredRescuers() {
  const q = state.query.trim().toLowerCase();
  let list = state.rescuers;
  if (state.filter === "active") list = list.filter((r) => r.account_status === "active");
  else if (state.filter === "on_duty")
    list = list.filter((r) => r.account_status === "active" && (r.duty_status || "off_duty") === "on_duty");
  else if (state.filter === "off_duty")
    list = list.filter((r) => r.account_status === "active" && (r.duty_status || "off_duty") === "off_duty");
  if (q) {
    list = list.filter((r) =>
      [r.full_name, r.email, r.phone_number, shortId(r.id)].join(" ").toLowerCase().includes(q)
    );
  }
  return list;
}

function filteredPending() {
  const q = state.query.trim().toLowerCase();
  let list = state.pending;
  if (q) {
    list = list.filter((r) =>
      [r.full_name, r.email, shortId(r.id)].join(" ").toLowerCase().includes(q)
    );
  }
  return list;
}

function rescuerRow(r) {
  const duty = r.duty_status || "off_duty";
  const suspended = r.account_status === "suspended";
  const toggle = suspended
    ? Button({ text: "Activate", variant: "outline", size: "sm", icon: "user-check", attrs: `data-action="activate" data-id="${r.id}"` })
    : Button({ text: "Suspend", variant: "destructive", size: "sm", attrs: `data-action="suspend" data-id="${r.id}"` });
  return `
    <tr data-id="${r.id}"${r.id === state.selectedId ? ' class="is-selected"' : ""}>
      <td class="table-cell table-cell--strong">${esc(r.full_name || "Unnamed")}</td>
      <td class="table-cell">${esc(r.email || "—")}</td>
      <td class="table-cell table-cell--mono">${esc(r.phone_number || "—")}</td>
      <td class="table-cell"><span class="stamp stamp--sm ${dutyStampCls(duty)}">${dutyLabel(duty)}</span></td>
      <td class="table-cell">${timeAgo(r.created_at)}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          ${toggle}
        </span>
      </td>
    </tr>`;
}

function applicantRow(r) {
  return `
    <tr data-id="${r.id}">
      <td class="table-cell">
        <div class="rescuer-cell">
          ${rescuerAvatar(r.profile_photo_url, r.full_name)}
          <div>
            <div class="table-cell--strong">${esc(r.full_name || "Unnamed")}</div>
            <div class="table-cell--muted table-cell--mono">${shortId(r.id)}</div>
          </div>
        </div>
      </td>
      <td class="table-cell">${esc(r.email || "—")}</td>
      <td class="table-cell">${timeAgo(r.created_at)}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">
          ${Button({ text: "Approve", variant: "default", size: "sm", icon: "user-check", attrs: `data-action="approve" data-id="${r.id}"` })}
          ${Button({ text: "Reject", variant: "destructive", size: "sm", icon: "slash", attrs: `data-action="reject" data-id="${r.id}"` })}
        </span>
      </td>
    </tr>`;
}

function paged(list) {
  const start = (state.page - 1) * PAGE_SIZE;
  return list.slice(start, start + PAGE_SIZE);
}

function ActiveTable() {
  const list = filteredRescuers();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="siren"></i><span>No rescuers match.</span></div></div>`;
  }
  const rows = paged(list).map(rescuerRow).join("");
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Rescuer</th><th>Email</th><th>Phone</th><th>Duty</th><th>Joined</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}

function PendingTable() {
  const list = filteredPending();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="user-check"></i><span>No applications match.</span></div></div>`;
  }
  const rows = paged(list).map(applicantRow).join("");
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Applicant</th><th>Email</th><th>Applied</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}

export function RescuerTable() {
  if (state.filter === "pending") return PendingTable();
  return ActiveTable();
}
