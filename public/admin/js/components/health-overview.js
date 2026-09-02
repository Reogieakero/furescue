import { state } from "../state.js";
import { healthOverview, healthPill, esc } from "../insights.js";
import { EmptyState } from "./util.js";

function initials(name) {
  return String(name || "?")
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0])
    .join("")
    .toUpperCase();
}

export function HealthOverviewCard() {
  const data = healthOverview(state.healthRecords || []);
  const summary = data.summary
    .map(
      (row) => `
    <div class="dash-health-row dash-health-row--${row.key}">
      <span><i data-lucide="${row.icon}"></i>${esc(row.label)}</span>
      <strong>${row.count}</strong>
    </div>`
    )
    .join("");

  const vaxLegend = data.vax
    .map(
      (item) => `
    <div class="dash-cat-item">
      <span><span class="dash-legend-dot dash-legend-dot--${item.key}"></span>${esc(item.label)}</span>
      <strong>${item.pct}%</strong>
    </div>`
    )
    .join("");

  const reminders = data.reminders.length
    ? data.reminders
        .map(
          (item) => `
    <div class="dash-reminder">
      <div>
        <div class="dash-reminder-label">${esc(item.label)}</div>
        <div class="dash-reminder-detail">${esc(item.detail)}</div>
      </div>
      <span class="dash-reminder-count">${item.count}</span>
    </div>`
        )
        .join("")
    : EmptyState({ icon: "bell", text: "No upcoming reminders." });

  const checkups = data.checkups.length
    ? data.checkups
        .map((c) => {
          const photo = c.photo
            ? `<img class="dash-checkup-photo" src="${esc(c.photo)}" alt="">`
            : `<span class="dash-checkup-fallback">${esc(initials(c.name))}</span>`;
          const href = c.animalId ? `/admin/health-records/health-record.php?id=${encodeURIComponent(c.animalId)}` : "/admin/health-records/";
          return `
    <a class="dash-checkup" href="${href}">
      ${photo}
      <div class="dash-checkup-body">
        <div class="dash-checkup-name">${esc(c.name)}</div>
        <div class="dash-checkup-meta">${esc(c.meta)}</div>
      </div>
      ${healthPill(c.statusKey, c.status)}
    </a>`;
        })
        .join("")
    : EmptyState({ icon: "stethoscope", text: "No recent check-ups." });

  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h2 class="panel-title">Health Monitoring Overview</h2></div>
      <a href="/admin/health-records/" class="dash-link">Open records</a>
    </div>
    <div class="dash-health-grid">
      <div class="dash-subcard">
        <h3>Health Summary</h3>
        ${summary}
        <div class="dash-health-total">Total Animals: ${data.totalAnimals}</div>
      </div>
      <div class="dash-subcard">
        <h3>Vaccination Status</h3>
        <div class="dash-cat-wrap">
          <div class="dash-donut">
            <canvas id="vax-status-donut"></canvas>
          </div>
          <div class="dash-cat-legend">${vaxLegend}</div>
        </div>
      </div>
      <div class="dash-subcard">
        <h3>Upcoming Reminders</h3>
        ${reminders}
      </div>
      <div class="dash-subcard">
        <h3>Recent Check-ups</h3>
        ${checkups}
      </div>
    </div>
  </section>`;
}

export function ReportsTrendCard() {
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="trending-up"></i><h2 class="panel-title">Reports Over Time</h2></div>
    </div>
    <div class="dash-trend-body">
      <canvas id="reports-trend-chart"></canvas>
    </div>
  </section>`;
}

export function HealthTrendRow() {
  return `
  <div class="dash-bottom">
    ${HealthOverviewCard()}
    ${ReportsTrendCard()}
  </div>`;
}
