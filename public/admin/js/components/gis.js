import { state } from "../state.js";
import { Select } from "/shared/components/select/select.js";
import { Button } from "/shared/components/button/button.js";
import { DateRangePicker } from "/shared/components/date-range-picker/date-range-picker.js";
import {
  categoryBreakdown,
  densitySummary,
  REPORT_TYPE_LABELS,
} from "../insights.js";

function lastUpdated(points) {
  const stamps = (points || [])
    .map((p) => p.created_at)
    .filter(Boolean)
    .map((v) => new Date(v).getTime())
    .filter((n) => !Number.isNaN(n));
  if (!stamps.length) return "—";
  return new Date(Math.max(...stamps)).toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

export function categoryLegendHtml(items) {
  return items
    .map(
      (item) => `
    <div class="dash-cat-item">
      <span><span class="dash-legend-dot dash-legend-dot--${item.key}"></span>${item.label}</span>
      <strong>${item.pct}%</strong>
    </div>`
    )
    .join("");
}

export function densityRowsHtml(summary) {
  const rows = [
    { key: "high", label: "High density", count: summary.high },
    { key: "mid", label: "Moderate density", count: summary.moderate },
    { key: "low", label: "Low density", count: summary.low },
  ];
  return rows
    .map(
      (r) => `
    <div class="dash-density-row">
      <span><span class="dash-legend-dot dash-legend-dot--${r.key}"></span>${r.label}</span>
      <strong>${r.count}</strong>
    </div>`
    )
    .join("");
}

export function GisToolbar() {
  const typeOptions = [
    { value: "", label: "All Report Types" },
    ...Object.entries(REPORT_TYPE_LABELS).map(([value, label]) => ({ value, label })),
  ];
  return `
    <div class="dash-gis-head">
      <h3 class="dash-side-title dash-gis-title">GIS Heatmap View</h3>
      <p class="dash-gis-sub">Geographic distribution of animal welfare reports across Mati City.</p>
    </div>
    <div class="dash-filters is-open" id="gis-filters">
      <div class="dash-filters-group dash-filters-group--view">
        <div class="dash-seg" role="group" aria-label="Map display">
          <button type="button" data-map-mode="markers">Markers</button>
          <button type="button" data-map-mode="heatmap" class="is-active">Heatmap</button>
        </div>
      </div>
      <div class="dash-filters-group dash-filters-group--data">
      ${DateRangePicker({
        id: "gis-date-range",
        startId: "gis-date-start",
        endId: "gis-date-end",
        placeholder: "Any dates",
        className: "dp-range--compact dash-date-range",
      })}
      ${Select({
        id: "gis-type",
        value: "",
        placeholder: "All Report Types",
        options: typeOptions,
        className: "dash-select",
      })}
      ${Select({
        id: "gis-status",
        value: "",
        placeholder: "All Status",
        options: [
          { value: "", label: "All Status" },
          { value: "pending", label: "Pending" },
          { value: "verified", label: "Verified" },
          { value: "in_progress", label: "In Progress" },
          { value: "resolved", label: "Resolved" },
        ],
        className: "dash-select",
      })}
      ${Button({ text: "Apply Filters", icon: "filter", className: "dash-apply", attrs: 'id="gis-apply"' })}
      ${Button({ text: "Reset", variant: "outline", icon: "rotate-ccw", className: "dash-reset", attrs: 'id="gis-reset"' })}
      </div>
    </div>`;
}

export function MapCard() {
  return `
  <section class="panel" id="case-density-panel">
    ${GisToolbar()}
    <div class="dash-map-wrap">
      <div id="case-density-map" class="map-canvas map-canvas--leaflet"></div>
      <aside class="dash-legend">
        <p class="dash-legend-title">What does this mean?</p>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--high"></span> High density</div>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--mid"></span> Moderate density</div>
        <div class="dash-legend-item"><span class="dash-legend-dot dash-legend-dot--low"></span> Low density</div>
        <p class="dash-legend-note">Red areas indicate locations with a higher number of reports. Last updated: <span id="gis-updated">${lastUpdated(state.heatmap)}</span></p>
      </aside>
    </div>
  </section>`;
}

export function HeatmapSummaryCard(points = state.heatmap) {
  return `
  <section class="panel dash-side-card">
    <h3 class="dash-side-title">Heatmap Summary</h3>
    <div id="gis-density">${densityRowsHtml(densitySummary(points))}</div>
  </section>`;
}

export function CategoryCard(reports = state.reports) {
  const items = categoryBreakdown(reports);
  const total = items.reduce((sum, item) => sum + item.count, 0);
  return `
  <section class="panel dash-side-card">
    <h3 class="dash-side-title">Reports by Category</h3>
    <div class="dash-cat-wrap">
      <div class="dash-donut">
        <canvas id="reports-category-donut"></canvas>
        <div class="dash-donut-center" id="reports-category-center">
          <strong>${total}</strong>
          <span>${total === 1 ? "Report" : "Reports"}</span>
        </div>
      </div>
      <div class="dash-cat-legend" id="gis-cat-legend">${categoryLegendHtml(items)}</div>
    </div>
  </section>`;
}

export function QuickActionsCard() {
  const pending = state.reportsPending.total || 0;
  const badge = pending ? `<span class="dash-action-badge">${pending}</span>` : "";
  return `
  <section class="panel dash-side-card">
    <h3 class="dash-side-title">Quick Actions</h3>
    <div class="dash-actions">
      <a class="dash-action" href="/admin/reports/"><i data-lucide="badge-check"></i> Validate Pending Reports ${badge}</a>
      <a class="dash-action" href="/admin/reports/"><i data-lucide="files"></i> View All Reports</a>
      <a class="dash-action" href="/admin/analytics/"><i data-lucide="bar-chart-3"></i> Generate Report</a>
      <button type="button" class="dash-action" id="gis-export"><i data-lucide="download"></i> Export Heatmap Data</button>
    </div>
  </section>`;
}

export function GisRow() {
  return `
  <div class="dash-gis">
    <div class="dash-gis-main">${MapCard()}</div>
    <div class="dash-gis-side">
      ${HeatmapSummaryCard()}
      ${CategoryCard()}
      ${QuickActionsCard()}
    </div>
  </div>`;
}
