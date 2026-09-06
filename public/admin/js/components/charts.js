import { state } from "../state.js";
import { loadChart } from "/assets/js/lib/load-chart.js";
import { whenVisible } from "/assets/js/lib/when-visible.js";
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

export async function refreshCategoryChart(reports) {
  const canvas = document.getElementById("reports-category-donut");
  if (!canvas) return;
  const cats = categoryBreakdown(reports || []);
  const total = cats.reduce((sum, item) => sum + item.count, 0);
  const colors = palette();
  const muted = hslToken("--border") || "hsl(220 14% 88%)";
  setCenter("reports-category-center", total, total === 1 ? "Report" : "Reports");
  if (!charts.category) {
    if (!total) return;
    const Chart = await loadChart();
    if (!Chart || !canvas.isConnected) return;
    charts.category = donut(
      Chart,
      canvas,
      cats.map((c) => c.label),
      cats.map((c) => c.count),
      [colors.green, colors.pending, colors.danger, colors.progress]
    );
    return;
  }
  charts.category.data.labels = total ? cats.map((c) => c.label) : ["No data"];
  charts.category.data.datasets[0].data = total ? cats.map((c) => c.count) : [1];
  charts.category.data.datasets[0].backgroundColor = total
    ? [colors.green, colors.pending, colors.danger, colors.progress]
    : [muted];
  charts.category.options.plugins.tooltip.enabled = Boolean(total);
  charts.category.update();
}

function seriesHasData(values) {
  return values.some((n) => n);
}

export async function mountDashboardCharts() {
  Object.values(charts).forEach((c) => c && c.destroy());
  charts.category = charts.vax = charts.trend = null;

  const colors = palette();
  const cats = categoryBreakdown(state.reports || []);
  const catCounts = cats.map((c) => c.count);
  const catTotal = catCounts.reduce((sum, n) => sum + n, 0);
  const health = healthOverview(state.healthRecords || []);
  const healthCounts = health.vax.map((v) => v.count);
  const healthTotal = healthCounts.reduce((sum, n) => sum + n, 0);
  const trend = state.reportTrend || state.overview.reports_monthly || [];
  const trendCounts = trend.map((d) => d.count);
  const trendHasData = trend.length && seriesHasData(trendCounts);

  setCenter("reports-category-center", catTotal, catTotal === 1 ? "Report" : "Reports");
  setCenter("vax-status-center", health.totalAnimals, health.totalAnimals === 1 ? "Animal" : "Animals");

  const jobs = [];
  if (catTotal) {
    jobs.push({
      el: document.getElementById("reports-category-donut"),
      mount: (Chart) => {
        charts.category = donut(
          Chart,
          document.getElementById("reports-category-donut"),
          cats.map((c) => c.label),
          catCounts,
          [colors.green, colors.pending, colors.danger, colors.progress]
        );
      },
    });
  }
  if (healthTotal) {
    jobs.push({
      el: document.getElementById("vax-status-donut"),
      mount: (Chart) => {
        charts.vax = donut(
          Chart,
          document.getElementById("vax-status-donut"),
          health.vax.map((v) => v.label),
          healthCounts,
          [colors.green, colors.pending, colors.danger, colors.progress]
        );
      },
    });
  }
  if (trendHasData) {
    jobs.push({
      el: document.getElementById("reports-trend-chart"),
      mount: (Chart) => {
        const trendCanvas = document.getElementById("reports-trend-chart");
        if (!trendCanvas) return;
        charts.trend = new Chart(trendCanvas, {
          type: "line",
          data: {
            labels: trend.map((d) => d.month),
            datasets: [
              {
                label: "Reports",
                data: trendCounts,
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
                ticks: { precision: 0, color: hslToken("--muted-foreground") },
                grid: { color: hslToken("--border") },
                border: { display: false },
              },
            },
          },
        });
      },
    });
  }

  const readyJobs = jobs.filter((job) => job.el);
  if (!readyJobs.length) return;

  await Promise.all(
    readyJobs.map(async (job) => {
      await whenVisible(job.el);
      if (!job.el.isConnected) return;
      const Chart = await loadChart();
      if (!Chart || !job.el.isConnected) return;
      Chart.defaults.font.family = cssVar("--font-dash") || "DM Sans, sans-serif";
      Chart.defaults.color = hslToken("--muted-foreground");
      job.mount(Chart);
    })
  );
}
