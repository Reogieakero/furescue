import { esc } from "../../health-records/components/util.js";
import { TONE, ICON, emptyState } from "../util.js";

export function HistoryPanel(history) {
  const item = (it) => {
    const tone = TONE[it.tone] || TONE.green;
    return `
    <li class="hr-tl-item">
      <span class="hr-tl-dot ${tone.split(" ")[0]}"><i data-lucide="${ICON[it.tone] || "circle"}"></i></span>
      <div class="hr-tl-content">
        <div class="hr-tl-row"><span class="hr-tl-date">${esc(it.date)}</span><span class="hr-tl-doctor">${esc(it.doctor)}</span></div>
        <div class="hr-tl-title">${esc(it.title)}</div>
        <div class="hr-tl-desc">${esc(it.description)}</div>
      </div>
    </li>`;
  };
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h3 class="panel-title">Medical History</h3></div>
    </div>
    <div class="panel-body">
      ${
        history && history.length
          ? `<ul class="hr-timeline">${history.map(item).join("")}</ul>`
          : emptyState("No medical history recorded yet")
      }
    </div>
  </section>`;
}
