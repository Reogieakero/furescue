import { state } from "../state.js";

export function rescuerCounts() {
  const active = state.rescuers.filter((r) => r.account_status === "active");
  const onDuty = active.filter((r) => (r.duty_status || "off_duty") === "on_duty").length;
  const suspended = state.rescuers.filter((r) => r.account_status === "suspended").length;
  return {
    total: state.rescuers.length + state.pending.length,
    active: active.length,
    onDuty,
    offDuty: active.length - onDuty,
    suspended,
    pending: state.pending.length,
  };
}

export function buildKpis() {
  const c = rescuerCounts();
  return [
    { icon: "users", value: c.total, label: "Total rescuers", note: null },
    { icon: "badge-check", value: c.active, label: "Active", note: null },
    {
      icon: "siren",
      value: c.onDuty,
      label: "On duty",
      note: c.onDuty ? { text: "On duty", cls: "kpi-note--accent" } : null,
    },
    {
      icon: "clock",
      value: c.pending,
      label: "Pending",
      note: c.pending ? { text: "Needs You", cls: "kpi-note--coral" } : null,
    },
    { icon: "slash", value: c.suspended, label: "Suspended", note: null },
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
