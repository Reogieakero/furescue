import { cn } from "../../lib/utils.js";

const CAL = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg>';
const CHEVRON_L =
  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m15 18-6-6 6-6"/></svg>';
const CHEVRON_R =
  '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="shrink-0"><path d="m9 18 6-6-6-6"/></svg>';

const WEEKDAYS = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];
const MONTHS = [
  "January", "February", "March", "April", "May", "June",
  "July", "August", "September", "October", "November", "December",
];

function pad(n) {
  return String(n).padStart(2, "0");
}
function toISO(y, m, d) {
  return `${y}-${pad(m + 1)}-${pad(d)}`;
}
function parseISO(v) {
  if (!v) return null;
  const parts = v.split("-").map((x) => parseInt(x, 10));
  if (parts.length !== 3 || parts.some((n) => Number.isNaN(n))) return null;
  return { y: parts[0], m: parts[1] - 1, d: parts[2] };
}

function triggerMarkup({ id, value, placeholder }) {
  const label = value ? formatPretty(value) : placeholder;
  return `
  <button type="button" data-date-trigger aria-haspopup="dialog" aria-expanded="false" class="${cn(
    "flex h-8 w-full items-center justify-between gap-2 whitespace-nowrap rounded-md border border-input bg-background px-3 text-sm font-medium text-foreground shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-1 focus:ring-ring"
  )}">
    <span data-date-value>${cn(label)}</span>
    <span class="shrink-0 opacity-50">${CAL}</span>
  </button>`;
}

function formatPretty(v) {
  const p = parseISO(v);
  if (!p) return v;
  return `${MONTHS[p.m]} ${p.d}, ${p.y}`;
}

function calendarMarkup(id, view) {
  const first = new Date(view.y, view.m, 1);
  const startWeekday = first.getDay();
  const daysInMonth = new Date(view.y, view.m + 1, 0).getDate();
  const cells = [];
  for (let i = 0; i < startWeekday; i++) cells.push(`<div></div>`);
  for (let d = 1; d <= daysInMonth; d++) {
    const iso = toISO(view.y, view.m, d);
    cells.push(
      `<button type="button" data-date-day="${iso}" class="${cn(
        "flex h-8 w-8 items-center justify-center rounded-md text-sm transition-colors hover:bg-accent hover:text-accent-foreground"
      )}">${d}</button>`
    );
  }
  return `
  <div class="hr-dp-cal">
    <div class="hr-dp-head">
      <button type="button" data-date-prev class="hr-dp-nav">${CHEVRON_L}</button>
      <span class="hr-dp-title">${MONTHS[view.m]} ${view.y}</span>
      <button type="button" data-date-next class="hr-dp-nav">${CHEVRON_R}</button>
    </div>
    <div class="hr-dp-grid">
      ${WEEKDAYS.map((w) => `<span class="hr-dp-wd">${w}</span>`).join("")}
      ${cells.join("")}
    </div>
  </div>`;
}

export function DatePicker({ id = "", name = "", value = "", placeholder = "Pick a date" } = {}) {
  const initial = parseISO(value);
  const view = initial || { y: new Date().getFullYear(), m: new Date().getMonth() };
  const hidden = `<input type="hidden" name="${name}" id="${id}-value" value="${cn(value || "")}">`;
  return `
  <div id="${id}" data-datepicker class="relative inline-block w-full">
    ${triggerMarkup({ id, value, placeholder })}
    <div data-date-content role="dialog" class="hr-dp-popover absolute left-0 top-full z-50 mt-1 hidden rounded-md border border-input bg-card p-3 text-card-foreground shadow-md">
      ${calendarMarkup(id, view)}
    </div>
    ${hidden}
  </div>`;
}

export function initDatePicker(root = document, handlers = {}) {
  const wraps =
    root && root.matches && root.matches("[data-datepicker]")
      ? [root]
      : root.querySelectorAll("[data-datepicker]");
  wraps.forEach((wrap) => {
    const id = wrap.id;
    const trigger = wrap.querySelector("[data-date-trigger]");
    const content = wrap.querySelector("[data-date-content]");
    const valueEl = wrap.querySelector("[data-date-value]");
    const hidden = wrap.querySelector(`#${id}-value`);
    if (!trigger || !content || !valueEl || !hidden) return;

    const handler = typeof handlers[id] === "function" ? handlers[id] : null;

    const current = () => parseISO(hidden.value) || { y: new Date().getFullYear(), m: new Date().getMonth() };
    const render = (view) => {
      const cal = content.querySelector(".hr-dp-cal");
      if (cal) cal.outerHTML = calendarMarkup(id, view);
      bindDays(view);
    };
    const bindDays = (view) => {
      content.querySelectorAll("[data-date-day]").forEach((day) => {
        day.addEventListener("click", () => {
          const val = day.getAttribute("data-date-day");
          hidden.value = val;
          valueEl.textContent = formatPretty(val);
          close();
          if (handler) handler(val);
        });
      });
      const prev = content.querySelector("[data-date-prev]");
      const next = content.querySelector("[data-date-next]");
      if (prev) prev.onclick = () => { const v = current(); render({ y: v.m === 0 ? v.y - 1 : v.y, m: v.m === 0 ? 11 : v.m - 1 }); };
      if (next) next.onclick = () => { const v = current(); render({ y: v.m === 11 ? v.y + 1 : v.y, m: v.m === 11 ? 0 : v.m + 1 }); };
    };

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
      render(current());
      content.classList.remove("hidden");
      trigger.setAttribute("aria-expanded", "true");
      document.addEventListener("pointerdown", onDoc);
    });

    bindDays(current());
  });
}
