import { attentionItems, attentionBreakdown, state } from "../state.js";
import { esc, fmtDate } from "./util.js";
import { shortId } from "../../dashboard/helpers.js";

const QUEUE_LIMIT = 6;

const TIER = {
  critical: "hr-queue-card--critical",
  warn: "hr-queue-card--warn",
  soon: "hr-queue-card--soon",
};

function cap(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

function daysLabel(d) {
  if (d < 0) return `${Math.abs(d)}d overdue`;
  if (d === 0) return "Due today";
  return `${d}d left`;
}

function Card(it) {
  return `
  <button type="button" class="hr-queue-card ${TIER[it.tier]}" data-queue-card data-animal="${esc(it.animalName)}" title="${esc(
    it.animalName
  )} · ${esc(it.barangay)} · ${esc(it.text)} — open in records">
    <span class="hr-qc-head">
      <span class="hr-qc-kind"><i data-lucide="${it.icon}"></i></span>
      <span class="stamp stamp--sm hr-qc-days hr-qc-days--${it.tier}">${daysLabel(it.days)}</span>
    </span>
    <span class="hr-qc-name">${esc(it.animalName)}</span>
    <span class="hr-qc-meta">${esc(cap(it.species))} · ${esc(it.barangay)}</span>
    <span class="hr-qc-reason">${esc(it.text)}<span class="hr-qc-id">${esc(shortId(it.id))}</span></span>
    <span class="hr-qc-foot">
      <span class="hr-qc-date"><i data-lucide="calendar"></i>${fmtDate(it.date, "short")}</span>
      <span class="hr-qc-go"><i data-lucide="chevron-right"></i></span>
    </span>
  </button>`;
}

export function AttentionPanel() {
  const items = attentionItems();
  const b = attentionBreakdown();
  const shown = state.queueExpanded ? items : items.slice(0, QUEUE_LIMIT);
  const body = items.length
    ? shown.map(Card).join("")
    : `<div class="empty-state hr-queue-empty"><i data-lucide="check-circle-2"></i><span>Nothing needs urgent attention.</span></div>`;
  const tally = b.total
    ? `<span class="stamp stamp--sm stamp--coral">${b.overdue} overdue</span>
       <span class="stamp stamp--sm stamp--muted">${b.expiring} expiring</span>`
    : `<span class="stamp stamp--sm stamp--accent">All clear</span>`;
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="bell"></i>
        <h2 class="panel-title">Attention queue</h2>
      </div>
      <div class="hr-queue-tally">${tally}</div>
    </div>
    <div class="hr-queue-grid${state.queueExpanded ? " is-expanded" : ""}">${body}</div>
    ${
      b.total > QUEUE_LIMIT
        ? `<button class="hr-queue-all" data-queue-all type="button">${
            state.queueExpanded ? "Show less" : `View all ${b.total}`
          }</button>`
        : ""
    }
  </div>`;
}
