import { createIcons, icons } from "lucide";
import { esc } from "/assets/js/lib/format.js";
import {
  RANGE_PRESETS,
  addMonths,
  formatRangeLabel,
  monthGridHtml,
  orderedRange,
  placePopover,
  presetRange,
  todayISO,
  viewFromISO,
} from "/shared/components/date-picker/calendar.js";

function dualMonths() {
  return window.matchMedia("(min-width: 768px)").matches;
}

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

export function DateRangePicker({
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
  presets = true,
} = {}) {
  const sid = startId || `${id}-start`;
  const eid = endId || `${id}-end`;
  const label = formatRangeLabel(start, end, placeholder);
  const presetHtml = presets
    ? `<div class="dp-presets">${RANGE_PRESETS.map(
        (p) => `<button type="button" class="dp-preset" data-range-preset="${p.id}">${esc(p.label)}</button>`
      ).join("")}<button type="button" class="dp-preset dp-preset--clear" data-range-clear>Clear</button></div>`
    : "";
  return `
  <div id="${esc(id)}" class="dp-range${className ? ` ${esc(className)}` : ""}" data-date-range data-min="${esc(min)}" data-max="${esc(max)}" data-placeholder="${esc(placeholder)}">
    <button type="button" class="dp-trigger" data-range-trigger aria-haspopup="dialog" aria-expanded="false" aria-label="Date range">
      <i data-lucide="calendar-range" class="dp-trigger-icon"></i>
      <span class="dp-trigger-label${start ? "" : " is-placeholder"}" data-range-label>${esc(label)}</span>
      <i data-lucide="chevron-down" class="dp-trigger-caret"></i>
    </button>
    <div class="dp-popover" data-range-popover hidden role="dialog" aria-label="Choose date range">
      ${presetHtml}
      <div class="dp-cals-toolbar">
        <button type="button" class="dp-nav" data-range-prev aria-label="Previous month"><i data-lucide="chevron-left"></i></button>
        <span class="dp-cals-caption" data-range-caption></span>
        <button type="button" class="dp-nav" data-range-next aria-label="Next month"><i data-lucide="chevron-right"></i></button>
      </div>
      <div class="dp-cals" data-range-cals></div>
      <p class="dp-hint" data-range-hint>Choose a start date, then an end date.</p>
    </div>
    <input type="hidden" data-range-start id="${esc(sid)}" name="${esc(startName)}" value="${esc(start)}">
    <input type="hidden" data-range-end id="${esc(eid)}" name="${esc(endName)}" value="${esc(end)}">
  </div>`;
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
  wrap.querySelectorAll("[data-range-preset]").forEach((btn) => {
    const next = presetRange(btn.dataset.rangePreset, bounds(wrap));
    btn.classList.toggle("is-active", Boolean(start && end && next.start === start && next.end === end));
  });
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
  let hover = "";

  const hint = () => {
    const el = wrap.querySelector("[data-range-hint]");
    if (!el) return;
    const { start, end } = getDateRange(wrap);
    if (!start) el.textContent = "Choose a start date, then an end date.";
    else if (!end) el.textContent = "Choose an end date.";
    else el.textContent = `${formatRangeLabel(start, end)} selected.`;
  };

  const renderCals = () => {
    const mount = popover.querySelector("[data-range-cals]");
    const caption = popover.querySelector("[data-range-caption]");
    const { start, end } = getDateRange(wrap);
    const b = bounds(wrap);
    const months = dualMonths() ? [view, addMonths(view, 1)] : [view];
    if (mount) {
      mount.innerHTML = months
        .map((m) => monthGridHtml(m, { start, end, hover, min: b.min, max: b.max, showNav: false }))
        .join("");
    }
    if (caption) caption.textContent = "";
    hint();
    syncChrome(wrap);
    paint();
  };

  const close = () => {
    hover = "";
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
    if (e.key === "Escape") close();
  };
  const onWin = () => {
    if (popover.hidden) return;
    renderCals();
    placePopover(trigger, popover);
  };

  const open = () => {
    view = viewFromISO(getDateRange(wrap).start || todayISO());
    hover = "";
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

  trigger.addEventListener("click", () => {
    if (!popover.hidden && popover.parentElement === document.body) return close();
    open();
  });

  popover.addEventListener("click", (e) => {
    const preset = e.target.closest("[data-range-preset]");
    if (preset) {
      const next = setDateRange(wrap, presetRange(preset.dataset.rangePreset, bounds(wrap)));
      emit(next);
      close();
      return;
    }
    if (e.target.closest("[data-range-clear]")) {
      const next = setDateRange(wrap, { start: "", end: "" });
      emit(next);
      close();
      return;
    }
    if (e.target.closest("[data-range-prev]")) {
      view = addMonths(view, -1);
      renderCals();
      return;
    }
    if (e.target.closest("[data-range-next]")) {
      view = addMonths(view, 1);
      renderCals();
      return;
    }
    const day = e.target.closest("[data-date-day]");
    if (!day || day.disabled) return;
    const iso = day.getAttribute("data-date-day");
    const { start, end } = getDateRange(wrap);
    if (!start || end) {
      setDateRange(wrap, { start: iso, end: "" });
      hover = "";
      renderCals();
      return;
    }
    const next = setDateRange(wrap, orderedRange(start, iso));
    emit(next);
    close();
  });

  popover.addEventListener("pointerover", (e) => {
    const day = e.target.closest("[data-date-day]");
    const { start, end } = getDateRange(wrap);
    if (!day || !start || end) return;
    const nextHover = day.getAttribute("data-date-day");
    if (nextHover === hover) return;
    hover = nextHover;
    renderCals();
  });
}
