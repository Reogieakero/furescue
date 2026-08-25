import { PaginationBar } from "/js/components/ui/pagination.js";
import { Button } from "/js/components/ui/button.js";
import { timeAgo } from "/admin/js/pages/dashboard/helpers.js";
import { state } from "../state.js";
import {
  PAGE_SIZE,
  categoryMeta,
  esc,
  emptyState,
  statusLabel,
  statusStampCls,
} from "./util.js";

export function filteredModules() {
  const q = state.query.trim().toLowerCase();
  return state.modules.filter((m) => {
    if (state.filter !== "all" && m.published_status !== state.filter) return false;
    if (state.category !== "all" && m.category !== state.category) return false;
    if (q && !String(m.title || "").toLowerCase().includes(q)) return false;
    return true;
  });
}

function rowActions(m) {
  const id = esc(m.id);
  const published = m.published_status === "published";
  const toggle = published
    ? Button({
        text: "Unpublish",
        variant: "outline",
        size: "sm",
        icon: "eye-off",
        attrs: `data-action="unpublish" data-id="${id}"`,
      })
    : Button({
        text: "Publish",
        variant: "default",
        size: "sm",
        icon: "upload",
        attrs: `data-action="publish" data-id="${id}"`,
      });
  return `
        <span class="table-actions">
          ${Button({ text: "Edit", variant: "outline", size: "sm", icon: "pencil", attrs: `data-action="edit" data-id="${id}"` })}
          ${toggle}
        </span>`;
}

function moduleCard(m) {
  const meta = categoryMeta(m.category);
  return `
  <article class="panel panel--padded elearn-mod-card" data-id="${esc(m.id)}">
    <div class="elearn-mod-card-top">
      <span class="stamp stamp--sm stamp--coral">${esc(meta.label)}</span>
      <span class="stamp stamp--sm ${statusStampCls(m.published_status)}">${statusLabel(m.published_status)}</span>
    </div>
    <h3 class="panel-title">${esc(m.title || "Untitled")}</h3>
    <p class="page-sub">${timeAgo(m.created_at)}</p>
    ${rowActions(m)}
  </article>`;
}

function moduleRow(m) {
  const meta = categoryMeta(m.category);
  return `
    <tr data-id="${esc(m.id)}">
      <td class="table-cell table-cell--strong">${esc(m.title || "Untitled")}</td>
      <td class="table-cell"><span class="stamp stamp--sm stamp--coral">${esc(meta.label)}</span></td>
      <td class="table-cell"><span class="stamp stamp--sm ${statusStampCls(m.published_status)}">${statusLabel(m.published_status)}</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${timeAgo(m.created_at)}</td>
      <td class="table-cell table-cell--right table-cell--nowrap">${rowActions(m)}</td>
    </tr>`;
}

function emptyCopy() {
  if (state.loadError) return { icon: "wifi-off", text: state.loadError };
  if (!state.modules.length) return { icon: "book-open", text: "No modules yet. Create your first lesson." };
  return { icon: "search", text: "No modules match." };
}

export function LibraryBody() {
  const list = filteredModules();
  if (state.loadError || list.length === 0) {
    const empty = emptyCopy();
    const block = `<div class="queue-empty">${emptyState(empty.icon, empty.text)}</div>`;
    return `<div class="elearn-cards">${block}</div><div class="elearn-table">${block}</div>`;
  }
  const start = (state.page - 1) * PAGE_SIZE;
  const pageRows = list.slice(start, start + PAGE_SIZE);
  const cards = pageRows.map(moduleCard).join("");
  const rows = pageRows.map(moduleRow).join("");
  const pagination =
    list.length > PAGE_SIZE
      ? `<div class="queue-pagination">${PaginationBar({ total: list.length, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="elearn-cards">${cards}</div>
    <div class="elearn-table table-wrap">
      <table class="table">
        <thead>
          <tr class="table-head">
            <th>Title</th><th>Category</th><th>Status</th><th>Created</th><th class="table-cell--right">Action</th>
          </tr>
        </thead>
        <tbody>${rows}</tbody>
      </table>
    </div>
    ${pagination}`;
}
