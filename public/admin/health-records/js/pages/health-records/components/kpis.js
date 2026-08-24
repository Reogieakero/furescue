import { state, visibleRecords, recordCounts, avgHeartRate } from "../state.js";
import { esc } from "./util.js";

export function buildKpis() {
  const list = visibleRecords();
  const total = list.length;
  const complete = list.filter((r) => r.vaccinationStatus === "complete").length;
  const partial = list.filter((r) => r.vaccinationStatus === "partial").length;
  const overdue = list.filter((r) => {
    const d = new Date(r.nextCheckupDue);
    return d.getTime() < Date.now();
  }).length;
  const under = list.filter((r) => r.healthStatus === "not_healthy").length;
  const pct = total ? Math.round((complete / total) * 100) : 0;
  return [
    {
      icon: "clipboard-list",
      value: total,
      label: "Records",
      desc: "Filtered animal health records in view.",
    },
    {
      icon: "shield-check",
      value: complete,
      label: "Fully vaccinated",
      note: { text: `+${pct}%`, cls: "kpi-note--accent" },
      desc: "Complete vaccination coverage within the current filter.",
    },
    {
      icon: "syringe",
      value: partial,
      label: "Partially vaccinated",
      desc: "Animals with an incomplete vaccination course.",
    },
    {
      icon: "alert-triangle",
      value: overdue,
      label: "Overdue checkups",
      note: overdue ? { text: "Action", cls: "kpi-note--coral" } : null,
      desc: "Checkups whose due date has passed.",
    },
    {
      icon: "stethoscope",
      value: under,
      label: "Under treatment",
      dark: true,
      desc: "Animals flagged not healthy and being monitored.",
    },
    {
      icon: "heart-pulse",
      value: `${avgHeartRate()}`,
      label: "Avg heart rate",
      desc: "Mean bpm across the filtered records.",
    },
  ];
}

export function KpiTile(k) {
  const note = k.note
    ? `<span class="kpi-note ${k.note.cls}">${k.note.icon ? `<i data-lucide="${k.note.icon}"></i>` : ""}${k.note.text}</span>`
    : "";
  return `
  <div class="kpi-tile${k.dark ? " kpi-tile--dark" : ""}">
    <div class="kpi-top">
      <div class="kpi-icon"><i data-lucide="${k.icon}"></i></div>
      ${note}
    </div>
    <div class="kpi-value">${esc(k.value)}</div>
    <div class="kpi-label">${esc(k.label)}</div>
    <div class="kpi-desc">${esc(k.desc)}</div>
  </div>`;
}

export function KpiStrip() {
  return `<div class="kpi-grid">${buildKpis().map(KpiTile).join("")}</div>`;
}

export { recordCounts };
