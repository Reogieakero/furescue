import { esc } from "/assets/js/lib/format.js";

export const WEEKDAYS = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
export const MONTHS = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

export const RANGE_PRESETS = [
  { id: "today", label: "Today" },
  { id: "7d", label: "7 days" },
  { id: "30d", label: "30 days" },
  { id: "month", label: "This month" },
];

export function pad(n) {
  return String(n).padStart(2, "0");
}

export function toISO(y, m, d) {
  return `${y}-${pad(m + 1)}-${pad(d)}`;
}

export function parseISO(v) {
  if (!v) return null;
  const parts = String(v).split("-").map((x) => parseInt(x, 10));
  if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return null;
  return { y: parts[0], m: parts[1] - 1, d: parts[2] };
}

export function todayISO() {
  const n = new Date();
  return toISO(n.getFullYear(), n.getMonth(), n.getDate());
}

export function cmpISO(a, b) {
  if (!a || !b) return 0;
  return a < b ? -1 : a > b ? 1 : 0;
}

export function addMonths(view, delta) {
  const d = new Date(view.y, view.m + delta, 1);
  return { y: d.getFullYear(), m: d.getMonth() };
}

export function viewFromISO(iso) {
  const p = parseISO(iso);
  if (p) return { y: p.y, m: p.m };
  const n = new Date();
  return { y: n.getFullYear(), m: n.getMonth() };
}

export function formatPretty(iso) {
  const p = parseISO(iso);
  if (!p) return iso || "";
  return `${MONTHS[p.m].slice(0, 3)} ${p.d}, ${p.y}`;
}

export function formatRangeLabel(start, end, placeholder = "Pick dates") {
  if (start && end) {
    return start === end ? formatPretty(start) : `${formatPretty(start)} \u2013 ${formatPretty(end)}`;
  }
  if (start) return `From ${formatPretty(start)}`;
  if (end) return `Through ${formatPretty(end)}`;
  return placeholder;
}

export function shiftISO(iso, days) {
  const p = parseISO(iso);
  if (!p) return iso;
  const d = new Date(p.y, p.m, p.d + days);
  return toISO(d.getFullYear(), d.getMonth(), d.getDate());
}

export function clampISO(iso, min, max) {
  if (!iso) return iso;
  if (min && cmpISO(iso, min) < 0) return min;
  if (max && cmpISO(iso, max) > 0) return max;
  return iso;
}

export function isDisabledISO(iso, min, max) {
  if (min && cmpISO(iso, min) < 0) return true;
  if (max && cmpISO(iso, max) > 0) return true;
  return false;
}

export function orderedRange(a, b) {
  if (!a || !b) return { start: a || "", end: b || "" };
  return cmpISO(a, b) <= 0 ? { start: a, end: b } : { start: b, end: a };
}

export function presetRange(id, { min = "", max = "" } = {}) {
  const today = todayISO();
  const endCap = max && cmpISO(today, max) > 0 ? max : today;
  let start = "";
  let end = "";
  if (id === "today") {
    start = end = endCap;
  } else if (id === "7d") {
    end = endCap;
    start = shiftISO(end, -6);
  } else if (id === "30d") {
    end = endCap;
    start = shiftISO(end, -29);
  } else if (id === "month") {
    const p = parseISO(endCap);
    start = toISO(p.y, p.m, 1);
    end = endCap;
  }
  return orderedRange(clampISO(start, min, max), clampISO(end, min, max));
}

function dayClass({ iso, selected, start, end, hover, today, disabled, outside }) {
  const { start: lo, end: hi } = orderedRange(start, end || hover);
  const isStart = iso === start;
  const isEnd = Boolean(end) && iso === end;
  const isIn = Boolean(lo && hi && lo !== hi && cmpISO(iso, lo) > 0 && cmpISO(iso, hi) < 0);
  const isSel = Boolean(selected) && iso === selected;
  const classes = ["dp-day"];
  if (disabled) classes.push("dp-day--disabled");
  if (outside) classes.push("dp-day--outside");
  if (iso === today) classes.push("dp-day--today");
  if (isSel || isStart || isEnd) classes.push("dp-day--selected");
  if (isStart) classes.push("dp-day--start");
  if (isEnd) classes.push("dp-day--end");
  if (isIn) classes.push("dp-day--in");
  return classes.join(" ");
}

export function monthGridHtml(view, opts = {}) {
  const {
    selected = "",
    start = "",
    end = "",
    hover = "",
    min = "",
    max = "",
    today = todayISO(),
    showNav = true,
    fillWeeks = false,
  } = opts;
  const cells = [];
  if (fillWeeks) {
    const cursor = new Date(view.y, view.m, 1);
    cursor.setDate(1 - cursor.getDay());
    for (let i = 0; i < 42; i++) {
      const y = cursor.getFullYear();
      const m = cursor.getMonth();
      const d = cursor.getDate();
      const iso = toISO(y, m, d);
      const disabled = isDisabledISO(iso, min, max);
      const outside = m !== view.m;
      cells.push(
        `<button type="button" class="${dayClass({ iso, selected, start, end, hover, today, disabled, outside })}" data-date-day="${iso}"${disabled ? " disabled" : ""} aria-label="${esc(formatPretty(iso))}">${d}</button>`
      );
      cursor.setDate(cursor.getDate() + 1);
    }
  } else {
    const startWeekday = new Date(view.y, view.m, 1).getDay();
    const daysInMonth = new Date(view.y, view.m + 1, 0).getDate();
    for (let i = 0; i < startWeekday; i++) cells.push('<span class="dp-day-pad"></span>');
    for (let d = 1; d <= daysInMonth; d++) {
      const iso = toISO(view.y, view.m, d);
      const disabled = isDisabledISO(iso, min, max);
      cells.push(
        `<button type="button" class="${dayClass({ iso, selected, start, end, hover, today, disabled })}" data-date-day="${iso}"${disabled ? " disabled" : ""} aria-label="${esc(formatPretty(iso))}">${d}</button>`
      );
    }
  }
  const title = `${MONTHS[view.m]} ${view.y}`;
  const head = showNav
    ? `<div class="dp-cal-head">
        <button type="button" class="dp-nav" data-date-prev aria-label="Previous month"><i data-lucide="chevron-left"></i></button>
        <span class="dp-cal-title">${title}</span>
        <button type="button" class="dp-nav" data-date-next aria-label="Next month"><i data-lucide="chevron-right"></i></button>
      </div>`
    : `<p class="dp-cal-title">${title}</p>`;
  return `
    <div class="dp-cal" data-cal-y="${view.y}" data-cal-m="${view.m}">
      ${head}
      <div class="dp-cal-grid">
        ${WEEKDAYS.map((w) => `<span class="dp-wd">${w}</span>`).join("")}
        ${cells.join("")}
      </div>
    </div>`;
}

export function placePopover(trigger, popover) {
  popover.hidden = false;
  popover.style.visibility = "hidden";
  popover.style.position = "fixed";
  const tw = trigger.getBoundingClientRect();
  const pw = popover.offsetWidth;
  const ph = popover.offsetHeight;
  const pad = 8;
  let left = tw.left;
  if (left + pw > window.innerWidth - pad) left = window.innerWidth - pw - pad;
  if (left < pad) left = pad;
  let top = tw.bottom + 6;
  if (top + ph > window.innerHeight - pad && tw.top - ph - 6 >= pad) {
    top = tw.top - ph - 6;
  } else if (top + ph > window.innerHeight - pad) {
    top = Math.max(pad, window.innerHeight - ph - pad);
  }
  popover.style.top = `${Math.round(top)}px`;
  popover.style.left = `${Math.round(left)}px`;
  popover.style.zIndex = "10000";
  popover.style.visibility = "";
}
