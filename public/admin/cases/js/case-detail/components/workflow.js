import { esc } from "./util.js";
import { titleCase } from "/admin/js/helpers.js";
import { Badge } from "/assets/js/components/ui/badge.js";
import { state } from "../state.js";

const STATUS_LABELS = {
  pending_verification: "PENDING",
  verified: "VERIFIED",
  assigned: "ASSIGNED",
  in_progress: "IN PROGRESS",
  resolved: "RESOLVED",
  open: "OPEN",
};

const WORKFLOW_LABELS = {
  assigned: { label: "Rescuer assigned", icon: "user-plus" },
  status_change: { label: "Status updated", icon: "refresh-cw" },
  accepted: { label: "Rescue accepted", icon: "badge-check" },
  declined: { label: "Rescue declined", icon: "file-x" },
  proof_added: { label: "Rescue proof added", icon: "camera" },
};

function statusDisplay(status) {
  return STATUS_LABELS[status] || titleCase(status);
}

function statusNote(status) {
  if (status === "in_progress") return "Rescuer accepted and started the rescue";
  if (status === "resolved") return "Admin marked the case resolved";
  if (status === "assigned") return "Rescuer re-assigned to the case";
  if (status === "open") return "Case returned to open";
  return null;
}

function describeEvent(ev) {
  if (ev.action === "status_change" || ev.type === "status_change") {
    const m = /^Status set to (.+)$/.exec(ev.notes || ev.note || "");
    if (m) {
      const status = m[1];
      return statusNote(status) || `Status changed to ${statusDisplay(status)}`;
    }
    return "";
  }
  return ev.notes || ev.note || "";
}

function rescuerBadge(caseData) {
  return Badge({ text: caseData.rescuer_name || "Rescuer", variant: "secondary", icon: "user" });
}

function eventActorBadge(ev, caseData) {
  const role = (ev.actor_role || "").toLowerCase();
  if (role === "rescuer" || !role) return rescuerBadge(caseData);
  return Badge({
    text: titleCase(ev.actor_role || "Admin"),
    variant: "outline",
    icon: "shield",
  });
}

function rescueTailNote(type) {
  if (type === "accepted") return "Rescuer accepted the assignment";
  if (type === "declined") return "Rescuer declined the assignment";
  if (type === "proof_added") return "Rescuer uploaded rescue proof";
  return "";
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
      actorBadge = eventActorBadge({ ...ev, actor_role: ev.actor_role || "admin" }, caseData);
    } else if (type === "accepted" || type === "declined" || type === "proof_added") {
      title = (WORKFLOW_LABELS[type] || { label: titleCase(type) }).label;
      actorBadge = eventActorBadge(ev, caseData);
      note = rescueTailNote(type);
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
