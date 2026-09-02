import { esc } from "../../health-records/components/util.js";
import { TONE, emptyState } from "../util.js";

export function RemindersPanel(rem) {
  const item = (r) => `
    <li class="hr-reminder">
      <div class="hr-reminder-left">
        <span class="tint-circle ${TONE[r.tone] ? TONE[r.tone].split(" ")[0] : "tint-blue"}"><i data-lucide="${esc(r.icon)}"></i></span>
        <div>
          <div class="hr-reminder-title">${esc(r.title)}</div>
          <div class="hr-reminder-due">Due ${esc(r.dueDate)}</div>
        </div>
      </div>
      <span class="pill pill--${r.tone}">${esc(r.days + " days")}</span>
    </li>`;
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bell"></i><h3 class="panel-title">Upcoming Reminders</h3></div>
    </div>
    <div class="panel-body">
      ${rem && rem.length ? `<ul class="hr-reminder-list">${rem.map(item).join("")}</ul>` : emptyState("No upcoming reminders")}
    </div>
  </section>`;
}
