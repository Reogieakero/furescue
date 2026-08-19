import { cn } from "../../lib/utils.js";

// shadcn/ui DropdownMenu primitives (Tailwind). Markup is rendered as HTML
// strings; initDropdownMenu() wires toggle, outside-click and Escape behaviour.

export function DropdownMenuLabel({ text = "", className = "" } = {}) {
  return `<div class="${cn("px-2 py-1.5 text-xs font-semibold text-muted-foreground", className)}">${text}</div>`;
}

export function DropdownMenuSeparator({ className = "" } = {}) {
  return `<div class="${cn("-mx-1 my-1 h-px bg-muted", className)}"></div>`;
}

export function DropdownMenuItem({ label = "", icon = "", href = "#", danger = false, className = "" } = {}) {
  return `
  <a href="${href}" class="${cn(
    "relative flex cursor-pointer select-none items-center gap-2 rounded-sm px-2 py-1.5 text-sm outline-none transition-colors hover:bg-accent hover:text-accent-foreground",
    danger ? "text-destructive hover:bg-destructive/10 hover:text-destructive" : "",
    className
  )}">
    ${icon ? `<i data-lucide="${icon}" class="h-4 w-4"></i>` : ""}
    <span>${label}</span>
  </a>`;
}

function DropdownMenuContent({ children = "", align = "right", className = "" } = {}) {
  const pos = align === "right" ? "right-0" : "left-0";
  return `
  <div data-dropdown-content role="menu" class="${cn(
    "absolute top-full z-50 mt-1 hidden min-w-56 overflow-hidden rounded-md border border-input bg-card p-1 text-card-foreground shadow-md",
    pos,
    className
  )}">
    ${children}
  </div>`;
}

export function DropdownMenu({
  id = "",
  trigger = "",
  items = [],
  align = "right",
  className = "",
  contentClassName = "",
} = {}) {
  const rows = items
    .map((it) => {
      switch (it.type) {
        case "label":
          return DropdownMenuLabel({ text: it.text });
        case "separator":
          return DropdownMenuSeparator();
        default:
          return DropdownMenuItem({ label: it.label, icon: it.icon, href: it.href, danger: it.danger });
      }
    })
    .join("");
  return `
  <div id="${id}" data-dropdown class="${cn("relative", className)}">
    ${trigger}
    ${DropdownMenuContent({ children: rows, align, className: contentClassName })}
  </div>`;
}

export function initDropdownMenu(root = document) {
  const wraps = root && root.matches && root.matches("[data-dropdown]") ? [root] : root.querySelectorAll("[data-dropdown]");
  wraps.forEach((wrap) => {
    const trigger = wrap.querySelector("[data-dropdown-trigger]");
    const content = wrap.querySelector("[data-dropdown-content]");
    if (!trigger || !content) return;

    const close = () => {
      content.classList.add("hidden");
      trigger.setAttribute("aria-expanded", "false");
      document.removeEventListener("pointerdown", onDoc);
    };
    const onDoc = (e) => {
      if (!wrap.contains(e.target)) close();
    };

    trigger.addEventListener("click", () => {
      if (!content.classList.contains("hidden")) return close();
      content.classList.remove("hidden");
      trigger.setAttribute("aria-expanded", "true");
      document.addEventListener("pointerdown", onDoc);
    });

    trigger.addEventListener("keydown", (e) => {
      if (e.key === "Escape") close();
      if (e.key === "ArrowDown" || e.key === "Enter" || e.key === " ") {
        e.preventDefault();
        content.classList.remove("hidden");
        trigger.setAttribute("aria-expanded", "true");
        document.addEventListener("pointerdown", onDoc);
      }
    });

    content.addEventListener("click", () => close());
  });
}