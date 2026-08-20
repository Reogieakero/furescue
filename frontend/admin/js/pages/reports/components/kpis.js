import { state } from "../state.js";

export function reportCounts() {
  const count = (s) => state.reports.filter((r) => r.status === s).length;
  return {
    all: state.reports.length,
    pending: count("pending_verification"),
    verified: count("verified"),
    dismissed: count("dismissed"),
    activeCases: state.cases.filter((c) => c.status !== "resolved").length,
    resolvedCases: state.cases.filter((c) => c.status === "resolved").length,
  };
}

export function buildKpis() {
  const c = reportCounts();
  const o = state.overview || {};
  return [
    { icon: "map-pin", value: c.all, label: "Total reports", note: null },
    { icon: "badge-check", value: c.pending, label: "Pending verify", note: c.pending ? { text: "Needs You", cls: "kpi-note--coral" } : null },
    { icon: "file-check", value: c.verified, label: "Verified", note: null },
    { icon: "file-x", value: c.dismissed, label: "Dismissed", note: null },
    { icon: "clipboard-list", value: c.activeCases, label: "Active cases", note: null },
    { icon: "check-circle-2", value: c.resolvedCases, label: "Resolved cases", dark: true },
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
    <div class="kpi-value">${k.value}</div>
    <div class="kpi-label">${k.label}</div>
  </div>`;
}
