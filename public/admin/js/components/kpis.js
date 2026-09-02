import { state } from "../state.js";
import { trendLabel } from "../insights.js";
import { KpiGrid as renderKpiGrid } from "/assets/js/components/kpi-card.js";

function buildKpis() {
  const pending = state.reportsPending.total || state.overview.reports_pending || 0;
  const inProgress = state.overview.cases_in_progress ?? state.activity.filter((c) => c.status === "assigned" || c.status === "in_progress").length;
  const resolved = state.overview.cases_resolved ?? state.activity.filter((c) => c.status === "resolved").length;
  const reportsTrend = trendLabel(state.overview.reports_today || 0);
  const pendingTrend = trendLabel(state.overview.pending_today || 0);
  const progressTrend = trendLabel(state.overview.in_progress_today || 0);
  const resolvedTrend = trendLabel(state.overview.resolved_today || 0);
  return [
    {
      icon: "folder-kanban",
      tone: "jungle",
      value: state.reportsTotal || state.overview.reports || 0,
      label: "Total Reports",
      trend: reportsTrend.text,
      trendTone: reportsTrend.tone,
    },
    {
      icon: "file-warning",
      tone: "coral",
      value: pending,
      label: "Pending Reports",
      trend: pendingTrend.text,
      trendTone: pendingTrend.tone,
    },
    {
      icon: "refresh-cw",
      tone: "sky",
      value: inProgress,
      label: "In Progress",
      trend: progressTrend.text,
      trendTone: progressTrend.tone,
    },
    {
      icon: "check-circle-2",
      tone: "amber",
      value: resolved,
      label: "Resolved",
      trend: resolvedTrend.text,
      trendTone: resolvedTrend.tone,
    },
  ];
}

export function KpiGrid() {
  return renderKpiGrid({ items: buildKpis(), id: "kpi-grid" });
}
