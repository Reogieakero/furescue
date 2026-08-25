import { KpiCard } from "/js/components/kpi-card.js";
import { esc } from "/js/lib/format.js";
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
  return [
    { icon: "map-pin", value: c.all, label: "Total reports", tone: "jungle" },
    {
      icon: "badge-check",
      value: c.pending,
      label: "Pending verify",
      tone: "coral",
      trend: c.pending ? "Needs You" : "",
      trendTone: "down",
    },
    { icon: "file-check", value: c.verified, label: "Verified", tone: "ink" },
    { icon: "file-x", value: c.dismissed, label: "Dismissed", tone: "ink" },
    { icon: "clipboard-list", value: c.activeCases, label: "Active cases", tone: "sky" },
    { icon: "check-circle-2", value: c.resolvedCases, label: "Resolved cases", tone: "jungle" },
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
