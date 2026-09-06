import { state } from "../state.js";
import { categoryBreakdown, healthOverview, hslToken, cssVar } from "../insights.js";

const charts = { category: null, vax: null, trend: null };

function setCenter(id, value, label) {
  const el = document.getElementById(id);
  if (!el) return;
  const strong = el.querySelector("strong");
  const span = el.querySelector("span");
  if (strong) strong.textContent = String(value);
  if (span) span.textContent = label;
}

function donut(Chart, canvas, labels, data, colors) {
  if (!canvas) return null;
  const total = data.reduce((sum, n) => sum + n, 0);
  const muted = hslToken("--border") || "hsl(220 14% 88%)";
  return new Chart(canvas, {
    type: "doughnut",
    data: {
      labels: total ? labels : ["No data"],
      datasets: [
        {
          data: total ? data : [1],
          backgroundColor: total ? colors : [muted],
          borderColor: hslToken("--card") || "#fff",
          borderWidth: 2,
          hoverOffset: total ? 4 : 0,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: {
        legend: { display: false },
        tooltip: { enabled: Boolean(total) },
      },
    },
  });
}

function palette() {
  return {
    green: hslToken("--primary"),
    pending: hslToken("--status-pending"),
    danger: hslToken("--status-danger"),
    progress: hslToken("--status-progress"),
    care: hslToken("--status-care"),
  };
}

export function refreshCategoryChart(reports) {
  const canvas = document.getElementById("reports-category-donut");
  if (!canvas || !charts.category) return;
  const cats = categoryBreakdown(reports || []);
  const total = cats.reduce((sum, item) => sum + item.count, 0);
  const colors = palette();
  const muted = hslToken("--border") || "hsl(220 14% 88%)";
  charts.category.data.labels = total ? cats.map((c) => c.label) : ["No data"];
  charts.category.data.datasets[0].data = total ? cats.map((c) => c.count) : [1];
  charts.category.data.datasets[0].backgroundColor = total
    ? [colors.green, colors.pending, colors.danger, colors.progress]
    : [muted];
  charts.category.options.plugins.tooltip.enabled = Boolean(total);
  charts.category.update();
  setCenter("reports-category-center", total, total === 1 ? "Report" : "Reports");
}

export async function mountDashboardCharts() {
  let Chart;
  try {
    ({ Chart } = await import("chart.js"));
  } catch (err) {
    console.warn("Chart.js failed to load", err);
    return;
  }

  Object.values(charts).forEach((c) => c && c.destroy());
  charts.category = charts.vax = charts.trend = null;

  Chart.defaults.font.family = cssVar("--font-dash") || "DM Sans, sans-serif";
  Chart.defaults.color = hslToken("--muted-foreground");

  const colors = palette();
  const cats = categoryBreakdown(state.reports || []);
  charts.category = donut(
    Chart,
    document.getElementById("reports-category-donut"),
    cats.map((c) => c.label),
    cats.map((c) => c.count),
    [colors.green, colors.pending, colors.danger, colors.progress]
  );
  setCenter(
    "reports-category-center",
    cats.reduce((sum, item) => sum + item.count, 0),
    cats.reduce((sum, item) => sum + item.count, 0) === 1 ? "Report" : "Reports"
  );

  const health = healthOverview(state.healthRecords || []);
  charts.vax = donut(
    Chart,
    document.getElementById("vax-status-donut"),
    health.vax.map((v) => v.label),
    health.vax.map((v) => v.count),
    [colors.green, colors.pending, colors.danger, colors.progress]
  );
  setCenter(
    "vax-status-center",
    health.totalAnimals,
    health.totalAnimals === 1 ? "Animal" : "Animals"
  );

  const trendCanvas = document.getElementById("reports-trend-chart");
  const trend = state.reportTrend || state.overview.reports_monthly || [];
  if (trendCanvas) {
    const counts = trend.map((d) => d.count);
    const empty = !trend.length || counts.every((n) => !n);
    charts.trend = new Chart(trendCanvas, {
      type: "line",
      data: {
        labels: trend.length ? trend.map((d) => d.month) : ["—"],
        datasets: [
          {
            label: "Reports",
            data: trend.length ? counts : [0],
            borderColor: colors.green,
            backgroundColor: "transparent",
            pointBackgroundColor: colors.green,
            pointBorderColor: hslToken("--card") || "#fff",
            pointBorderWidth: 2,
            pointRadius: 5,
            tension: 0.35,
            borderWidth: 3,
          },
        ],
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
          x: { grid: { display: false }, ticks: { color: hslToken("--muted-foreground") } },
          y: {
            beginAtZero: true,
            suggestedMax: empty ? 4 : undefined,
            ticks: { precision: 0, color: hslToken("--muted-foreground") },
            grid: { color: hslToken("--border") },
            border: { display: false },
          },
        },
      },
    });
  }
}
