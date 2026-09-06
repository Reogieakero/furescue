import { PaginationBar } from "/shared/components/pagination/pagination.js";
import { Button } from "/shared/components/button/button.js";
import { shortId, timeAgo } from "/admin/js/helpers.js";
import { avatarImg } from "/admin/js/components/util.js";
import { state, currentUserId } from "../state.js";
import { esc, roleLabel, statusLabel, roleStamp, statusStamp, emptyMessage } from "./util.js";

export function filteredUsers() {
  const q = state.query.trim().toLowerCase();
  let list = state.users;
  if (state.filter === "admin" || state.filter === "rescuer" || state.filter === "resident") {
    list = list.filter((row) => row.role === state.filter);
  } else if (state.filter === "pending" || state.filter === "suspended" || state.filter === "active") {
    list = list.filter((row) => row.account_status === state.filter);
  }
  if (q) {
    list = list.filter((row) =>
      [row.full_name, row.email, row.phone_number, row.role, row.account_status, shortId(row.id)]
        .join(" ")
        .toLowerCase()
        .includes(q)
    );
  }
  return [...list].sort((a, b) => new Date(b.created_at || 0) - new Date(a.created_at || 0));
}

function actionLinks(row) {
  const id = esc(row.id);
  const self = row.id === currentUserId();
  const buttons = [
    Button({
      text: "Edit",
      variant: "outline",
      size: "sm",
      icon: "pencil",
      attrs: `data-action="edit" data-id="${id}"`,
    }),
  ];
  if (self) return buttons.join("");
  if (row.account_status === "suspended" || row.account_status === "rejected") {
    buttons.push(
      Button({
        text: "Activate",
        variant: "outline",
        size: "sm",
        icon: "user-check",
        attrs: `data-action="activate" data-id="${id}"`,
      })
    );
  } else if (row.account_status === "pending") {
    buttons.push(
      Button({
        text: "Activate",
        variant: "default",
        size: "sm",
        icon: "user-check",
        attrs: `data-action="activate" data-id="${id}"`,
      })
    );
  } else {
    buttons.push(
      Button({
        text: "Suspend",
        variant: "outline",
        size: "sm",
        icon: "slash",
        attrs: `data-action="suspend" data-id="${id}"`,
      })
    );
  }
  buttons.push(
    Button({
      text: "Delete",
      variant: "destructive",
      size: "sm",
      icon: "trash-2",
      attrs: `data-action="delete" data-id="${id}"`,
    })
  );
  return buttons.join("");
}

function userRow(row) {
  const name = (row.full_name && String(row.full_name).trim()) || "Unnamed";
  const email = (row.email && String(row.email).trim()) || "—";
  const self = row.id === currentUserId();
  const you = self ? ' <span class="stamp stamp--sm stamp--accent">You</span>' : "";
  return `
    <tr data-id="${esc(row.id)}">
      <td class="table-cell">
        <div class="table-avatar-name">
          ${avatarImg(row.profile_photo_url, name)}
          <div>
            <div class="table-name table-cell--strong">${esc(name)}${you}</div>
            <div class="table-cell--muted">${esc(email)}</div>
          </div>
        </div>
      </td>
      <td class="table-cell"><span class="stamp stamp--sm ${roleStamp(row.role)}">${esc(roleLabel(row.role))}</span></td>
      <td class="table-cell"><span class="stamp stamp--sm ${statusStamp(row.account_status)}">${esc(statusLabel(row.account_status))}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${esc(timeAgo(row.created_at))}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">
        <span class="table-actions">${actionLinks(row)}</span>
      </td>
    </tr>`;
}

export function UserTable() {
  if (state.error && !state.users.length) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="triangle-alert"></i><span>${esc(state.error)}</span></div></div>`;
  }
  const list = filteredUsers();
  if (list.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="users"></i><span>${esc(emptyMessage(state.filter, state.query))}</span></div></div>`;
  }
  const start = (state.page - 1) * state.pageSize;
  const rows = list.slice(start, start + state.pageSize).map(userRow).join("");
  const pagination = `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: state.pageSize, page: state.page })}</div>`;
  const banner = state.error
    ? `<div class="queue-empty"><div class="empty-state"><i data-lucide="triangle-alert"></i><span>${esc(state.error)}</span></div></div>`
    : "";
  return `
    ${banner}
    <div class="table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Account</th><th>Role</th><th>Status</th><th>Joined</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}
