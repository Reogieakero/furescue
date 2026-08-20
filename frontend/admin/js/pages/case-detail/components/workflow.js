import { esc } from "./util.js";
import { titleCase, timeAgo } from "../../dashboard/helpers.js";

const WORKFLOW_LABELS = {
  assigned: { label: "Rescuer assigned", icon: "assigned" },
  status_change: { label: "Status updated", icon: "progress" },
};

function workflowLabel(type) {
  if (!type) return "";
  const entry = WORKFLOW_LABELS[type];
  if (entry && entry.label) return entry.label;
  return "";
}

function describeEvent(event, type) {
  if (event.type === "assigned") {
    return "";
  }
  if (event.type === "status_change") {
    const m = /^Status set to (.+)$/.exec(event.note || "");
    return m ? `Status changed to ${m[1]}` : "";
  }
  return event.note || "";
}

function buildActivity(caseData) {
  const base = [
    {
      title: "Case opened",
      note: "Case created from a verified report.",
      actor: caseData.rescuer,
      type: "open",
      time: "",
    },
  ];
  (caseData.events || []).forEach((ev) => {
    const label = WORKFLOW_LABELS[ev.type] || { label: titleCase(ev.type), icon: "default" };
    const note =
      ev.type === "assigned" && ev.rescuer
        ? ev.rescuer.name
        : describeEvent(ev, ev.type);
    base.push({
      title: label.label,
      actor: ev.actor || ev.rescuer,
      time: timeAgo(ev.created_at),
      note: note,
      type: ev.type,
    });
  });
  return base;
}

function TimelineItem(item) {
  return `
    <li class="cd-tl-item cd-tl--${esc(item.type)}">
      <span class="cd-tl-dot">${esc(item.type)}</span>
      <div class="cd-tl-body">
        <div class="cd-tl-title">${esc(item.title)}</div>
        ${item.note ? `<div class="cd-tl-notes">${esc(item.note)}</div>` : ""}
        <div class="cd-tl-meta">${
          item.actor
            ? `<span class="cd-tl-actor">${esc(item.actor.name || item.actor)}</span>`
            : ""
        }<span class="cd-tl-time">${esc(item.time || "")}</span></div>
      </div>
    </li>`;
}

export function renderWorkflow(caseData) {
  const events = buildActivity(caseData);
  const items = events.length
    ? `<ul class="cd-timeline">${events.map((e) => TimelineItem(e)).join("")}</ul>`
    : `<div class="empty-state"><i data-lucide="git-commit-vertical"></i><span>No activity recorded yet.</span></div>`;
  return `
    <div class="panel case-detail-panel">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="git-branch"></i>
          <h2 class="panel-title">Workflow &amp; transactions</h2>
        </div>
        <span class="stamp stamp--sm stamp--muted">${events.length} events</span>
      </div>
      <div class="panel-body">${items}</div>
    </div>`;
}
