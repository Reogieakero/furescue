import { esc } from "./util.js";
import { titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { Badge } from "/js/components/ui/badge.js";
import { state } from "../state.js";

const WORKFLOW_LABELS = {
  assigned: { label: "Rescuer assigned", icon: "user-plus" },
  status_change: { label: "Status updated", icon: "refresh-cw" },
};

function statusNote(status) {
  if (status === "in_progress") return "Rescuer accepted and started the rescue";
  if (status === "resolved") return "Rescuer marked the case resolved";
  if (status === "assigned") return "Rescuer re-assigned to the case";
  return null;
}

function describeEvent(ev) {
  if (ev.action === "status_change" || ev.type === "status_change") {
    const m = /^Status set to (.+)$/.exec(ev.notes || ev.note || "");
    if (m) {
      const status = m[1];
      return statusNote(status) || `Status changed to ${titleCase(status)}`;
    }
    return "";
  }
  return ev.notes || ev.note || "";
}

function formatEventTime(iso) {
  if (!iso) return { date: "", time: "" };
  const d = new Date(iso);
  if (isNaN(d.getTime())) return { date: "", time: "" };
  return {
    date: d.toLocaleDateString(undefined, { year: "numeric", month: "short", day: "numeric" }),
    time: d.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" }),
  };
}

function buildActivity(caseData) {
  const openTime = formatEventTime(caseData.created_at);
  const base = [
    {
      title: "Case opened",
      note: "Case created from a verified report.",
      actorBadge: "",
      type: "open",
      date: openTime.date,
      time: openTime.time,
    },
  ];
  let assignedCount = 0;
  (state.activity || []).forEach((ev) => {
    const type = ev.action || ev.type;
    let title = (WORKFLOW_LABELS[type] || { label: titleCase(type || "event") }).label;
    let actorBadge = "";
    let note = "";
    if (type === "assigned") {
      assignedCount += 1;
      // First assignment reads "assigned"; any later one is a re-assignment.
      const isReassign = assignedCount > 1;
      title = isReassign ? "Rescuer reassigned" : "Rescuer assigned";
      actorBadge = Badge({ text: caseData.rescuer_name || "Rescuer", variant: "secondary", icon: "user" });
      // Still "assigned" means the rescuer has not accepted yet.
      note = caseData.status === "assigned"
        ? "Waiting for rescuer to accept"
        : (isReassign ? "Rescuer reassigned to the case" : "Rescuer assigned to the case");
    } else if (type === "status_change") {
      note = describeEvent(ev);
      const role = (ev.actor_role || "admin").toLowerCase();
      const byRescuer = role === "rescuer";
      actorBadge = Badge({
        text: byRescuer ? (caseData.rescuer_name || "Rescuer") : titleCase(ev.actor_role || "Admin"),
        variant: byRescuer ? "secondary" : "outline",
        icon: byRescuer ? "user" : "shield",
      });
    } else {
      note = describeEvent(ev);
    }
    const t = formatEventTime(ev.created_at);
    base.push({
      title,
      actorBadge,
      date: t.date,
      time: t.time,
      note,
      type,
    });
  });
  return base;
}

function TimelineItem(item, index) {
  return `
    <li class="cd-tl-item cd-tl--${esc(item.type)}">
      <span class="cd-tl-dot">${esc(String(index))}</span>
      <div class="cd-tl-body">
        <div class="cd-tl-title">${esc(item.title)}</div>
        ${item.note ? `<div class="cd-tl-notes">${esc(item.note)}</div>` : ""}
        <div class="cd-tl-meta">
          ${item.actorBadge ? `<span class="cd-tl-actor">${item.actorBadge}</span>` : ""}
          <span class="cd-tl-time">
            ${item.date ? `<span class="cd-tl-date">${esc(item.date)}</span>` : ""}
            ${item.time ? `<span class="cd-tl-clock">${esc(item.time)}</span>` : ""}
          </span>
        </div>
      </div>
    </li>`;
}

export function renderWorkflow(caseData) {
  const data = caseData || state.caseData;
  const events = buildActivity(data);
  const items = events.length
    ? `<ul class="cd-timeline">${events.map((e, i) => TimelineItem(e, i + 1)).join("")}</ul>`
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
