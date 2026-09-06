import { cn } from "/assets/js/lib/utils.js";

export const TABLE_PAGE_SIZES = [10, 20, 50, 100];
export const DEFAULT_TABLE_PAGE_SIZE = 20;

export function clampPageSize(value, fallback = DEFAULT_TABLE_PAGE_SIZE) {
  const n = Number(value);
  return TABLE_PAGE_SIZES.includes(n) ? n : fallback;
}

export function pageSizeOptions(current) {
  const n = Number(current);
  if (!n || TABLE_PAGE_SIZES.includes(n)) return TABLE_PAGE_SIZES;
  return [...TABLE_PAGE_SIZES, n].sort((a, b) => a - b);
}

export function readPageClick(target, currentPage) {
  const btn = target && target.closest && target.closest("button[data-page]");
  if (!btn || btn.getAttribute("aria-disabled") === "true") return null;
  const page = parseInt(btn.dataset.page, 10);
  if (!page || page === currentPage) return null;
  return page;
}

export function readPageSizeChange(target, currentSize) {
  const sel = target && target.closest && target.closest("[data-per-page]");
  if (!sel) return null;
  const next = Number(sel.value);
  if (!Number.isFinite(next) || next < 1 || next === currentSize) return null;
  return next;
}

export function Pagination({ children = "", className = "" } = {}) {
  return `<nav class="${cn("flex w-auto justify-end", className)}" aria-label="Pagination">${children}</nav>`;
}

export function PaginationContent({ children = "", className = "" } = {}) {
  return `<ul class="${cn("flex flex-wrap items-center gap-1", className)}">${children}</ul>`;
}

export function PaginationItem(childrenOrObj = "", className = "") {
  const children =
    typeof childrenOrObj === "string"
      ? childrenOrObj
      : (childrenOrObj && childrenOrObj.children) || "";
  return `<li class="${cn("", className)}">${children}</li>`;
}

export function PaginationLink({ page = 1, isActive = false, className = "" } = {}) {
  const cls = cn(
    "inline-flex h-8 min-w-8 items-center justify-center rounded-md border px-2 text-sm font-medium transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring",
    isActive
      ? "border-primary bg-primary text-primary-foreground"
      : "border-input bg-background text-foreground hover:bg-accent hover:text-accent-foreground",
    className
  );
  return `<button data-page="${page}" class="${cls}"${isActive ? ' aria-current="page"' : ""}>${page}</button>`;
}

export function PaginationPrevious({ page = 1, disabled = false, className = "" } = {}) {
  const cls = cn(
    "inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground",
    disabled && "pointer-events-none opacity-50",
    className
  );
  return `<button data-page="${page}" class="${cls}"${disabled ? ' aria-disabled="true"' : ""}><i data-lucide="chevron-left" class="h-4 w-4"></i>Previous</button>`;
}

export function PaginationNext({ page = 1, disabled = false, className = "" } = {}) {
  const cls = cn(
    "inline-flex h-8 items-center gap-1 rounded-md border border-input bg-background px-2.5 text-sm font-medium transition-colors hover:bg-accent hover:text-accent-foreground",
    disabled && "pointer-events-none opacity-50",
    className
  );
  return `<button data-page="${page}" class="${cls}"${disabled ? ' aria-disabled="true"' : ""}>Next<i data-lucide="chevron-right" class="h-4 w-4"></i></button>`;
}

export function PaginationEllipsis({ className = "" } = {}) {
  return `<span class="${cn("flex h-8 w-8 items-center justify-center", className)}"><i data-lucide="ellipsis" class="h-4 w-4"></i></span>`;
}

function pageItems(current, total) {
  const set = new Set([1, total, current - 1, current, current + 1]);
  const pages = [...set].filter((p) => p >= 1 && p <= total).sort((a, b) => a - b);
  const out = [];
  let prev = 0;
  for (const p of pages) {
    if (p - prev > 1) out.push("ellipsis");
    out.push(p);
    prev = p;
  }
  return out;
}

function PageSizeControl({ perPage, total = 0, page = 1 } = {}) {
  const sizes = pageSizeOptions(perPage);
  const pageTotal = Math.max(1, Math.ceil(total / Math.max(1, perPage)));
  const cur = Math.min(Math.max(1, page), pageTotal);
  const from = total === 0 ? 0 : (cur - 1) * perPage + 1;
  const to = Math.min(total, cur * perPage);
  const options = sizes
    .map((n) => `<option value="${n}"${n === perPage ? " selected" : ""}>${n}</option>`)
    .join("");
  return `
  <div class="pagination-meta">
    <label class="pagination-page-size">
      <span class="pagination-page-size-label">Rows per page</span>
      <select class="pagination-page-size-select" data-per-page aria-label="Rows per page">${options}</select>
    </label>
    <span class="pagination-range">${from}–${to} of ${total}</span>
  </div>`;
}

export function PaginationBar({ total = 0, perPage = 10, page = 1, className = "" } = {}) {
  const pageTotal = Math.max(1, Math.ceil(total / Math.max(1, perPage)));
  const cur = Math.min(Math.max(1, page), pageTotal);
  const items = pageItems(cur, pageTotal).map((p) =>
    p === "ellipsis"
      ? PaginationItem(PaginationEllipsis())
      : PaginationItem(PaginationLink({ page: p, isActive: p === cur }))
  );
  return `<div class="${cn("pagination-bar", className)}">${PageSizeControl({ perPage, total, page: cur })}${Pagination({
    children: PaginationContent({
      children: [
        PaginationItem(PaginationPrevious({ page: Math.max(1, cur - 1), disabled: cur <= 1 })),
        ...items,
        PaginationItem(PaginationNext({ page: Math.min(pageTotal, cur + 1), disabled: cur >= pageTotal })),
      ].join(""),
    }),
  })}</div>`;
}
