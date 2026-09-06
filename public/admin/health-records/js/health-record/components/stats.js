import { esc } from "../../health-records/components/util.js";
import { TONE } from "../util.js";

function statBlock(num, label, icon, tone) {
  return `
  <div class="hr-stat">
    <span class="tint-circle ${TONE[tone].split(" ")[0]}"><i data-lucide="${icon}"></i></span>
    <div class="hr-stat-text">
      <div class="hr-stat-num">${esc(String(num))}</div>
      <div class="hr-stat-label">${esc(label)}</div>
    </div>
  </div>`;
}

export function StatsPanel(r) {
  const checkups = (r.history || []).filter((h) => /check/i.test(h.title)).length;
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h3 class="panel-title">Health Statistics</h3></div>
    </div>
    <div class="panel-body">
      <div class="hr-stat-strip">
        ${statBlock(checkups, "Check-ups", "stethoscope", "green")}
        ${statBlock((r.vaccinations || []).length, "Vaccinations", "syringe", "blue")}
        ${statBlock((r.reminders || []).length, "Reminders", "bell", "yellow")}
        ${statBlock((r.vitals || []).length, "Vitals logged", "heart-pulse", "purple")}
      </div>
    </div>
  </section>`;
}
