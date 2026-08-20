import { state } from "../state.js";

function buildKpis() {
  const onDuty = state.rescuers.filter((u) => (u.duty_status || "off_duty") === "on_duty").length;
  const resolved = state.activity.filter((c) => c.status === "resolved").length;
  return [
    { icon: "map-pin", value: state.reportsTotal, label: "Total reports", note: null },
    { icon: "badge-check", value: state.reportsPending.total, label: "Pending verify", note: state.reportsPending.total ? { text: "Needs You", cls: "kpi-note--coral" } : null },
    { icon: "siren", value: onDuty, label: "Rescuers on duty", note: null },
    { icon: "heart-pulse", value: state.healthUpdates.total, label: "Health updates", note: state.healthUpdates.total ? { text: "Recent", cls: "kpi-note--muted" } : null },
    { icon: "home", value: state.adoptionsPending.total, label: "Pending adoptions" },
    { icon: "check-circle-2", value: resolved, label: "Resolved cases", dark: true },
  ];
}

function KpiTile(k) {
  const note = k.note
    ? `<span class="kpi-note ${k.note.cls}">${k.note.icon ? `<i data-lucide="${k.note.icon}"></i>` : ""}${k.note.text}</span>`
    : "";
  return `
  <div class="kpi-tile${k.dark ? " kpi-tile--dark" : ""}">
    <div class="kpi-top">
      <div class="kpi-icon"><i data-lucide="${k.icon}"></i></div>
      ${note}
    </div>
    <div class="kpi-value">${k.value}</div>
    <div class="kpi-label">${k.label}</div>
  </div>`;
}

export function KpiGrid() {
  return `<div class="kpi-grid" id="kpi-grid">${buildKpis().map(KpiTile).join("")}</div>`;
}
