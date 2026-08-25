import { KpiGrid } from "/js/components/kpi-card.js";
import { esc } from "/js/lib/format.js";
import { state, recordCounts } from "../state.js";
import { daysUntil } from "./util.js";

function dueSoonCount(list) {
  return list.filter((r) => {
    if (!r.nextCheckupDue) return false;
    const parsed = new Date(r.nextCheckupDue);
    if (Number.isNaN(parsed.getTime())) return false;
    const d = daysUntil(r.nextCheckupDue);
    return d >= 0 && d <= 14;
  }).length;
}

export function buildKpis() {
  const c = recordCounts();
  const dueSoon = dueSoonCount(state.records);
  const pct = c.all ? Math.round((c.complete / c.all) * 100) : 0;
  return [
    {
      icon: "alert-triangle",
      value: c.overdue,
      label: "Overdue",
      tone: "coral",
      filter: "overdue",
      trend: c.overdue ? "Needs attention" : "",
      trendTone: "down",
      desc: "Checkups whose due date has already passed.",
    },
    {
      icon: "calendar-clock",
      value: dueSoon,
      label: "Due soon",
      tone: "amber",
      trend: "Next 14 days",
      trendTone: "neutral",
      desc: "Checkups due today or within the next 14 days.",
    },
    {
      icon: "shield-check",
      value: c.complete,
      label: "Current",
      tone: "jungle",
      filter: "complete",
      trend: `${pct}% of records`,
      trendTone: "neutral",
      desc: "Animals with complete vaccination coverage.",
    },
    {
      icon: "clipboard-x",
      value: c.none,
      label: "Missing vaccines",
      tone: "ink",
      filter: "none",
      desc: "Animals with no vaccination on file.",
    },
    {
      icon: "stethoscope",
      value: c.under_treatment,
      label: "In treatment",
      tone: "sky",
      filter: "under_treatment",
      desc: "Animals flagged not healthy and being monitored.",
    },
  ];
}

export function toKpiCardProps(k) {
  const extra = [];
  if (k.desc) extra.push(`title="${esc(k.desc)}"`);
  if (k.filter) extra.push(`data-filter="${esc(k.filter)}"`);
  return {
    icon: k.icon,
    tone: k.tone,
    label: k.label,
    value: k.value,
    trend: k.trend || "",
    trendTone: k.trendTone || "neutral",
    interactive: Boolean(k.filter),
    attrs: extra.join(" "),
  };
}

export function KpiStrip() {
  return KpiGrid({ items: buildKpis().map(toKpiCardProps) });
}

export { recordCounts };
