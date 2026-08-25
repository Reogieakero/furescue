import { state } from "../state.js";
import { categoryBreakdown, healthOverview, hslToken, cssVar } from "../insights.js";

const charts = { category: null, vax: null, trend: null };

function donut(Chart, canvas, labels, data, colors) {
  if (!canvas) return null;
  return new Chart(canvas, {
    type: "doughnut",
    data: {
      labels,
      datasets: [
        {
          data,
          backgroundColor: colors,
          borderColor: hslToken("--card") || "#fff",
          borderWidth: 2,
          hoverOffset: 4,
        },
      ],
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      cutout: "68%",
      plugins: { legend: { display: false }, tooltip: { enabled: true } },
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

  const health = healthOverview(state.healthRecords || []);
  charts.vax = donut(
    Chart,
    document.getElementById("vax-status-donut"),
    health.vax.map((v) => v.label),
    health.vax.map((v) => v.count),
    [colors.green, colors.pending, colors.danger, colors.progress]
  );

  const trendCanvas = document.getElementById("reports-trend-chart");
  const trend = state.reportTrend || state.overview.reports_monthly || [];
  if (trendCanvas && trend.length) {
    charts.trend = new Chart(trendCanvas, {
      type: "line",
      data: {
        labels: trend.map((d) => d.month),
        datasets: [
          {
            label: "Reports",
            data: trend.map((d) => d.count),
            borderColor: colors.green,
            backgroundColor: "transparent",
            pointBackgroundColor: colors.green,
            pointBorderColor: "#fff",
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
            ticks: { precision: 0, color: hslToken("--muted-foreground") },
            grid: { color: hslToken("--border") },
            border: { display: false },
          },
        },
      },
    });
  }
}
