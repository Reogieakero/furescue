import { KpiCard } from "/assets/js/components/kpi-card.js";
import { esc } from "/assets/js/lib/format.js";
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
    { icon: "users", value: c.total, label: "Total rescuers", tone: "jungle" },
    { icon: "badge-check", value: c.active, label: "Active", tone: "jungle" },
    {
      icon: "siren",
      value: c.onDuty,
      label: "On duty",
      tone: "sky",
      trend: c.onDuty ? "On duty" : "",
      trendTone: "up",
    },
    {
      icon: "clock",
      value: c.pending,
      label: "Pending",
      tone: "coral",
      trend: c.pending ? "Needs You" : "",
      trendTone: "down",
    },
    { icon: "slash", value: c.suspended, label: "Suspended", tone: "amber" },
  ];
}

export function toKpiCardProps(k) {
  const aria = k.desc ? `${k.label}: ${k.value}. ${k.desc}` : `${k.label}: ${k.value}`;
  const extra = [`aria-label="${esc(aria)}"`];
  if (k.desc) extra.push(`title="${esc(k.desc)}"`);
  return {
    icon: k.icon,
    tone: k.tone,
    label: k.label,
    value: k.value,
    trend: k.trend || "",
    trendTone: k.trendTone || "neutral",
    attrs: extra.join(" "),
  };
}

export function KpiTile(k) {
  return KpiCard(toKpiCardProps(k));
}
