import { createIcons, icons } from "lucide";
import { esc } from "/assets/js/lib/format.js";
import {
  addMonths,
  formatPretty,
  monthGridHtml,
  placePopover,
  todayISO,
  viewFromISO,
} from "/shared/components/date-picker/calendar.js";

function paint() {
  createIcons({ icons });
}

export function DatePicker({
  id = "",
  name = "",
  value = "",
  placeholder = "Pick a date",
  min = "",
  max = "",
  className = "",
} = {}) {
  const label = value ? formatPretty(value) : placeholder;
  return `
  <div id="${esc(id)}" class="dp-range${className ? ` ${esc(className)}` : ""}" data-datepicker data-min="${esc(min)}" data-max="${esc(max)}" data-placeholder="${esc(placeholder)}">
    <button type="button" class="dp-trigger" data-date-trigger aria-haspopup="dialog" aria-expanded="false">
      <i data-lucide="calendar" class="dp-trigger-icon"></i>
      <span class="dp-trigger-label${value ? "" : " is-placeholder"}" data-date-value>${esc(label)}</span>
      <i data-lucide="chevron-down" class="dp-trigger-caret"></i>
    </button>
    <div data-date-content class="dp-popover" hidden role="dialog" aria-label="Choose date"></div>
    <input type="hidden" name="${esc(name)}" id="${esc(id)}-value" value="${esc(value || "")}">
  </div>`;
}

export function initDatePicker(root = document, handlers = {}) {
  const wraps =
    root && root.matches && root.matches("[data-datepicker]")
      ? [root]
      : Array.from((root || document).querySelectorAll("[data-datepicker]"));
  wraps.forEach((wrap) => bindPicker(wrap, typeof handlers[wrap.id] === "function" ? handlers[wrap.id] : null));
}

function bindPicker(wrap, handler) {
  if (!wrap || wrap.dataset.dpBound === "1") return;
  wrap.dataset.dpBound = "1";
  const trigger = wrap.querySelector("[data-date-trigger]");
  const content = wrap.querySelector("[data-date-content]");
  const valueEl = wrap.querySelector("[data-date-value]");
  const hidden = wrap.querySelector(`#${wrap.id}-value`) || wrap.querySelector("input[type=hidden]");
  if (!trigger || !content || !valueEl || !hidden) return;

  const bounds = () => ({ min: wrap.dataset.min || "", max: wrap.dataset.max || "" });

  const render = (view) => {
    const b = bounds();
    content.innerHTML = monthGridHtml(view, {
      selected: hidden.value,
      min: b.min,
      max: b.max,
      showNav: true,
    });
    paint();
  };

  const close = () => {
    if (content.parentElement === document.body) {
      if (wrap.isConnected) wrap.appendChild(content);
      else content.remove();
    }
    content.hidden = true;
    trigger.setAttribute("aria-expanded", "false");
    document.removeEventListener("pointerdown", onDoc);
    document.removeEventListener("keydown", onKey);
    window.removeEventListener("resize", onWin);
  };

  const onDoc = (e) => {
    if (!wrap.contains(e.target) && !content.contains(e.target)) close();
  };
  const onKey = (e) => {
    if (e.key === "Escape") close();
  };
  const onWin = () => {
    if (!content.hidden) placePopover(trigger, content);
  };

  const open = () => {
    render(viewFromISO(hidden.value || todayISO()));
    document.body.appendChild(content);
    placePopover(trigger, content);
    trigger.setAttribute("aria-expanded", "true");
    document.addEventListener("pointerdown", onDoc);
    document.addEventListener("keydown", onKey);
    window.addEventListener("resize", onWin);
  };

  trigger.addEventListener("click", () => {
    if (!content.hidden && content.parentElement === document.body) return close();
    open();
  });

  content.addEventListener("click", (e) => {
    if (e.target.closest("[data-date-prev]")) {
      const cur = viewFromISO(hidden.value || todayISO());
      const head = content.querySelector("[data-cal-y]");
      const view = head
        ? { y: Number(head.getAttribute("data-cal-y")), m: Number(head.getAttribute("data-cal-m")) }
        : cur;
      render(addMonths(view, -1));
      placePopover(trigger, content);
      return;
    }
    if (e.target.closest("[data-date-next]")) {
      const head = content.querySelector("[data-cal-y]");
      const view = head
        ? { y: Number(head.getAttribute("data-cal-y")), m: Number(head.getAttribute("data-cal-m")) }
        : viewFromISO(hidden.value || todayISO());
      render(addMonths(view, 1));
      placePopover(trigger, content);
      return;
    }
    const day = e.target.closest("[data-date-day]");
    if (!day || day.disabled) return;
    const val = day.getAttribute("data-date-day") || "";
    hidden.value = val;
    valueEl.textContent = formatPretty(val);
    valueEl.classList.toggle("is-placeholder", !val);
    close();
    if (handler) handler(val);
  });
}
