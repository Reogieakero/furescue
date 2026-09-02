import { queueState } from "../state.js";
import { initials } from "../helpers.js";
import { PaginationBar } from "/assets/js/components/ui/pagination.js";

export const QUEUE_PAGE_SIZE = 7;
export const ACTIVITY_PAGE_SIZE = 5;

export const ChevronRight = () => '<i data-lucide="chevron-right" class="link-chevron"></i>';

export function EmptyState({ icon = "inbox", text = "No records." } = {}) {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${text}</span></div>`;
}

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

export function TableHead(cols) {
  return `
  <thead>
    <tr class="table-head">
      ${cols.map((c) => `<th>${c}</th>`).join("")}
    </tr>
  </thead>`;
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
