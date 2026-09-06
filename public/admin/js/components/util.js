import { queueState } from "../state.js";
import { initials } from "../helpers.js";
import { PaginationBar } from "/shared/components/pagination/pagination.js";

export { EmptyState } from "/shared/components/empty-state/empty-state.js";
export { TableHead } from "/shared/components/table/table.js";

export const QUEUE_PAGE_SIZE = 7;
export const ACTIVITY_PAGE_SIZE = 5;

export const ChevronRight = () => '<i data-lucide="chevron-right" class="link-chevron"></i>';

export function avatarImg(src, name) {
  return src
    ? `<img class="table-avatar" src="${src}" alt="">`
    : `<span class="table-avatar table-avatar--initial">${initials(name)}</span>`;
}

export function rescuerAvatar(src, name) {
  return src
    ? `<img class="rescuer-avatar" src="${src}" alt="">`
    : `<span class="rescuer-avatar rescuer-avatar--initial">${initials(name)}</span>`;
}

export function slicePage(items, key) {
  const page = queueState[key] || 1;
  const start = (page - 1) * QUEUE_PAGE_SIZE;
  return items.slice(start, start + QUEUE_PAGE_SIZE);
}

export function paginationBar(key, total) {
  if (total <= QUEUE_PAGE_SIZE) return "";
  return `<div class="queue-pagination">${PaginationBar({ total, perPage: QUEUE_PAGE_SIZE, page: queueState[key] || 1 })}</div>`;
}
