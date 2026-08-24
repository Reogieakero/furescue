import { Button } from "/js/components/ui/button.js";
import { esc } from "../../health-records/components/util.js";
import { emptyState } from "../util.js";

export function VitalsPanel(vitals, meta, { editing = false, mode = null, openForm = false } = {}) {
  const item = (v) => `
    <li class="hr-vital"${editing ? ` data-action="edit-vital" data-label="${esc(v.label)}" style="cursor:pointer"` : ""}>
      <div class="hr-vital-left">
        <span class="hr-vital-label">${esc(v.label)}</span>
        <span class="hr-vital-value">${esc(v.value)}<small>${esc(v.unit)}</small></span>
      </div>
    </li>`;

  const headActions =
    editing && mode === "add"
      ? `<div class="panel-head-actions">${Button({
          text: "Add",
          variant: "default",
          size: "sm",
          attrs: `data-action="open-vital-modal"`,
        })}</div>`
      : "";

  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h3 class="panel-title">Vital Signs</h3></div>
      ${headActions}
    </div>
    <div class="panel-body">
      ${
        vitals && vitals.length
          ? `<ul class="hr-vital-list">${vitals.map(item).join("")}</ul><p class="hr-vital-meta">${esc(meta || "")}</p>`
          : emptyState("No vitals recorded")
      }
    </div>
  </section>`;
}
