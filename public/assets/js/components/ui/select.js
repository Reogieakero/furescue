import { cn } from "/assets/js/lib/utils.js";

const CHEVRON =
  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m6 9 6 6 6-6"/></svg>';

const CHECK =
  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>';

function SelectItem({ value = "", label = "", selected = false } = {}) {
  return `
  <div role="option" aria-selected="${selected}" data-select-item data-value="${value}" class="${cn(
    "flex w-full cursor-pointer items-center justify-between gap-2 px-3 py-2 text-sm transition-colors",
    selected ? "bg-accent text-accent-foreground" : "hover:bg-accent hover:text-accent-foreground"
  )}">
    <span data-select-item-label>${label}</span>
    ${selected ? `<span data-select-check class="shrink-0">${CHECK}</span>` : ""}
  </div>`;
}

export function Select({
  id = "",
  options = [],
  value = "",
  placeholder = "Select",
  triggerClassName = "",
  contentClassName = "",
  className = "",
} = {}) {
  const selected = options.find((o) => o.value === value);
  const label = selected ? selected.label : placeholder;
  const items = options.map((o) => SelectItem({ value: o.value, label: o.label, selected: o.value === value })).join("");
  return `
  <div id="${id}" data-select class="${cn("relative inline-block", className)}">
    <button type="button" data-select-trigger aria-haspopup="listbox" aria-expanded="false" class="${cn(
      "flex h-8 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-1 focus:ring-ring",
      triggerClassName
    )}">
      <span data-select-value>${label}</span>
      <span class="shrink-0 opacity-50">${CHEVRON}</span>
    </button>
    <div data-select-content role="listbox" class="${cn(
      "absolute left-0 top-full z-50 mt-1 hidden min-w-full overflow-hidden rounded-md border border-input bg-card text-card-foreground shadow-md",
      contentClassName
    )}">
      ${items}
    </div>
  </div>`;
}

export function initSelect(root = document, handlers = {}) {
  const wraps = root && root.matches && root.matches("[data-select]") ? [root] : root.querySelectorAll("[data-select]");
  wraps.forEach((wrap) => {
    const trigger = wrap.querySelector("[data-select-trigger]");
    const content = wrap.querySelector("[data-select-content]");
    const valueEl = wrap.querySelector("[data-select-value]");
    if (!trigger || !content || !valueEl) return;
    const handler = typeof handlers[wrap.id] === "function" ? handlers[wrap.id] : null;

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
      if (e.key === "ArrowDown") {
        e.preventDefault();
        content.classList.remove("hidden");
        trigger.setAttribute("aria-expanded", "true");
        document.addEventListener("pointerdown", onDoc);
      }
    });

    content.querySelectorAll("[data-select-item]").forEach((item) => {
      item.addEventListener("click", () => {
        const val = item.getAttribute("data-value");
        const labelEl = item.querySelector("[data-select-item-label]");
        if (labelEl) valueEl.textContent = labelEl.textContent;
        content.querySelectorAll("[data-select-item]").forEach((it) => {
          const active = it === item;
          it.setAttribute("aria-selected", String(active));
          it.classList.toggle("bg-accent", active);
          it.classList.toggle("text-accent-foreground", active);
          const chk = it.querySelector("[data-select-check]");
          if (active && !chk) it.insertAdjacentHTML("beforeend", `<span data-select-check class="shrink-0">${CHECK}</span>`);
          else if (!active && chk) chk.remove();
        });
        close();
        if (handler) handler(val);
      });
    });
  });
}
