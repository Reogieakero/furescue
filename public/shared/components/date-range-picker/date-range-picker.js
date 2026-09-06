import { createIcons, icons } from "lucide";
import { esc } from "/assets/js/lib/format.js";
import {
  addMonths,
  clampISO,
  formatRangeLabel,
  monthGridHtml,
  orderedRange,
  placePopover,
  todayISO,
  viewFromISO,
} from "/shared/components/date-picker/calendar.js";

function startInput(wrap) {
  return wrap.querySelector("[data-range-start]");
}

function endInput(wrap) {
  return wrap.querySelector("[data-range-end]");
}

function bounds(wrap) {
  return { min: wrap.dataset.min || "", max: wrap.dataset.max || "" };
}

function paint() {
  createIcons({ icons });
}

function rangeMarkup({
  id = "",
  startId = "",
  endId = "",
  startName = "start",
  endName = "end",
  start = "",
  end = "",
  min = "",
  max = "",
  placeholder = "Pick dates",
  className = "",
} = {}) {
  const sid = startId || `${id}-start`;
  const eid = endId || `${id}-end`;
  const label = formatRangeLabel(start, end, placeholder);
  const errorId = id ? `${id}-error` : "";
  return `
  <div id="${esc(id)}" class="dp-range${className ? ` ${esc(className)}` : ""}" data-date-range data-min="${esc(min)}" data-max="${esc(max)}" data-placeholder="${esc(placeholder)}">
    <button type="button" class="dp-trigger" data-range-trigger aria-haspopup="dialog" aria-expanded="false" aria-label="Date range">
      <span class="dp-trigger-label${start ? "" : " is-placeholder"}" data-range-label>${esc(label)}</span>
      <i data-lucide="chevron-down" class="dp-trigger-caret"></i>
    </button>
    <div class="dp-popover" data-range-popover hidden role="dialog" aria-label="Choose date range">
      <div class="dp-cals" data-range-cals></div>
      <p class="dp-range-error" data-range-error${errorId ? ` id="${esc(errorId)}"` : ""} hidden>The start date must be on or before the end date.</p>
      <p class="dp-range-live" data-range-live aria-live="polite"></p>
      <div class="dp-range-actions">
        <button type="button" class="dp-range-today" data-range-today>Today</button>
        <button type="button" class="dp-range-clear" data-range-clear>Clear</button>
        <button type="button" class="dp-range-apply" data-range-apply>Apply</button>
      </div>
    </div>
    <input type="hidden" data-range-start id="${esc(sid)}" name="${esc(startName)}" value="${esc(start)}">
    <input type="hidden" data-range-end id="${esc(eid)}" name="${esc(endName)}" value="${esc(end)}">
  </div>`;
}

export function DateRangePicker(opts = {}) {
  return rangeMarkup(opts);
}

export function getDateRange(wrap) {
  if (!wrap) return { start: "", end: "" };
  return { start: startInput(wrap)?.value || "", end: endInput(wrap)?.value || "" };
}

function syncChrome(wrap) {
  const { start, end } = getDateRange(wrap);
  const placeholder = wrap.dataset.placeholder || "Pick dates";
  const label = wrap.querySelector("[data-range-label]");
  if (label) {
    label.textContent = formatRangeLabel(start, end, placeholder);
    label.classList.toggle("is-placeholder", !start);
  }
}

export function setDateRange(wrap, range = {}, { emit = false } = {}) {
  if (!wrap) return getDateRange(wrap);
  const startEl = startInput(wrap);
  const endEl = endInput(wrap);
  if (startEl) startEl.value = range.start || "";
  if (endEl) endEl.value = range.end || "";
  syncChrome(wrap);
  const next = getDateRange(wrap);
  if (emit) {
    wrap.dispatchEvent(new CustomEvent("daterangechange", { bubbles: true, detail: next }));
  }
  return next;
}

export function initDateRangePicker(root = document, handlers = {}) {
  const wraps =
    root && root.matches && root.matches("[data-date-range]")
      ? [root]
      : Array.from((root || document).querySelectorAll("[data-date-range]"));
  wraps.forEach((wrap) => bindRange(wrap, typeof handlers[wrap.id] === "function" ? handlers[wrap.id] : null));
}

function bindRange(wrap, handler) {
  if (!wrap || wrap.dataset.dpBound === "1") return;
  wrap.dataset.dpBound = "1";
  const trigger = wrap.querySelector("[data-range-trigger]");
  const popover = wrap.querySelector("[data-range-popover]");
  if (!trigger || !popover) return;

  let view = viewFromISO(getDateRange(wrap).start || todayISO());
  let draft = getDateRange(wrap);
  let hover = "";

  const announce = (text) => {
    const live = wrap.querySelector("[data-range-live]");
    if (live) live.textContent = text;
  };

  const setError = (on) => {
    const el = wrap.querySelector("[data-range-error]");
    if (el) el.hidden = !on;
  };

  const renderCals = () => {
    const mount = popover.querySelector("[data-range-cals]");
    const b = bounds(wrap);
    if (mount) {
      mount.innerHTML = monthGridHtml(view, {
        start: draft.start,
        end: draft.end,
        hover,
        min: b.min,
        max: b.max,
        showNav: true,
        fillWeeks: true,
      });
    }
    paint();
  };

  const close = () => {
    hover = "";
    setError(false);
    draft = getDateRange(wrap);
    if (popover.parentElement === document.body) {
      if (wrap.isConnected) wrap.appendChild(popover);
      else popover.remove();
    }
    popover.hidden = true;
    trigger.setAttribute("aria-expanded", "false");
    document.removeEventListener("pointerdown", onDoc);
    document.removeEventListener("keydown", onKey);
    window.removeEventListener("resize", onWin);
  };

  const onDoc = (e) => {
    if (!wrap.contains(e.target) && !popover.contains(e.target)) close();
  };
  const onKey = (e) => {
    if (e.key === "Escape") {
      close();
      trigger.focus();
    }
  };
  const onWin = () => {
    if (popover.hidden) return;
    renderCals();
    placePopover(trigger, popover);
  };

  const open = () => {
    draft = getDateRange(wrap);
    view = viewFromISO(draft.start || todayISO());
    hover = "";
    setError(false);
    renderCals();
    document.body.appendChild(popover);
    placePopover(trigger, popover);
    trigger.setAttribute("aria-expanded", "true");
    document.addEventListener("pointerdown", onDoc);
    document.addEventListener("keydown", onKey);
    window.addEventListener("resize", onWin);
    paint();
  };

  const emit = (next) => {
    wrap.dispatchEvent(new CustomEvent("daterangechange", { bubbles: true, detail: next }));
    if (handler) handler(next);
  };

  const commit = (range) => {
    const next = setDateRange(wrap, range);
    draft = next;
    setError(false);
    emit(next);
    close();
    return next;
  };

  trigger.addEventListener("click", () => {
    if (!popover.hidden && popover.parentElement === document.body) return close();
    open();
  });

  popover.addEventListener("click", (e) => {
    if (e.target.closest("[data-range-today]")) {
      const b = bounds(wrap);
      const iso = clampISO(todayISO(), b.min, b.max) || todayISO();
      announce(`Today ${iso}`);
      commit({ start: iso, end: iso });
      return;
    }
    if (e.target.closest("[data-range-clear]")) {
      announce("All dates");
      commit({ start: "", end: "" });
      return;
    }
    if (e.target.closest("[data-range-apply]")) {
      let { start, end } = draft;
      if (start && !end) end = start;
      if (start && end && start > end) {
        setError(true);
        return;
      }
      if (start && end) announce(`Range ${start} to ${end}`);
      else announce("All dates");
      commit({ start, end });
      return;
    }
    if (e.target.closest("[data-date-prev]")) {
      view = addMonths(view, -1);
      renderCals();
      return;
    }
    if (e.target.closest("[data-date-next]")) {
      view = addMonths(view, 1);
      renderCals();
      return;
    }
    const day = e.target.closest("[data-date-day]");
    if (!day || day.disabled) return;
    const iso = day.getAttribute("data-date-day");
    if (!draft.start || draft.end) {
      draft = { start: iso, end: "" };
      announce(`Start ${iso}. Choose an end date.`);
    } else {
      draft = orderedRange(draft.start, iso);
      announce(`Range ${draft.start} to ${draft.end}`);
    }
    hover = "";
    setError(false);
    renderCals();
  });

  popover.addEventListener("pointerover", (e) => {
    const day = e.target.closest("[data-date-day]");
    if (!day || !draft.start || draft.end) return;
    const nextHover = day.getAttribute("data-date-day");
    if (nextHover === hover) return;
    hover = nextHover;
    renderCals();
  });

  popover.addEventListener("pointerout", (e) => {
    if (!hover || popover.contains(e.relatedTarget)) return;
    hover = "";
    renderCals();
  });
}
