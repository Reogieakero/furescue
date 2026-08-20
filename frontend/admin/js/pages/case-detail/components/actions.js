import { AppShell } from "../../../layout/app-shell.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { esc } from "./util.js";
import { titleCase } from "../../dashboard/helpers.js";
import { renderWorkflow } from "./workflow.js";
import { renderCaseInfo, renderSourceReport } from "./info.js";
import { renderAttachments } from "./files.js";
import { renderProof } from "./files.js";

export function renderActions(caseData) {
  const isResolved = caseData.status === "resolved";
  const locationBtn = Button({
    text: "See location",
    variant: "outline",
    size: "sm",
    icon: "map-pin",
    "data-cd-action": "location",
  });
  if (isResolved) {
    return `<div class="cd-actions">${locationBtn}${Button({ text: "Resolved", variant: "outline", size: "sm", icon: "check-circle-2", disabled: true })}</div>`;
  }
  const assignLabel = caseData.assigned_rescuer_id ? "Reassign" : "Assign rescuer";
  return `
    <div class="cd-actions">
      ${locationBtn}
      ${Button({ text: assignLabel, variant: "outline", size: "sm", icon: "user-plus", "data-cd-action": "assign" })}
      ${Button({ text: "Resolve", variant: "default", size: "sm", icon: "check-circle-2", "data-cd-action": "resolve" })}
    </div>`;
}

function renderHeader(caseData) {
  const title = caseData.animal_description
    ? titleCase(caseData.animal_description)
    : "Rescue case";
  const sub = caseData.report && caseData.report.address_text
    ? caseData.report.address_text
    : "Location pending";
  return `
    <div class="page-head">
      <div>
        <a href="cases.html" class="cd-back"><i data-lucide="chevron-left"></i> Back to cases</a>
        <p class="page-sub">${esc(title)} &middot; ${esc(sub)}</p>
      </div>
      ${renderActions(caseData)}
    </div>`;
}

export function CaseDetailPage(caseData) {
  return AppShell({
    user: caseData.user,
    title: "Case detail",
    activeNav: "cases",
    children: [
      renderHeader(caseData),
      `<div class="case-detail-grid">`,
      `<div class="cd-col-workflow">${renderWorkflow(caseData)}</div>`,
      `<div class="cd-col-info">${renderCaseInfo(caseData)}${renderSourceReport(caseData)}</div>`,
      `<div class="cd-col-files">${renderAttachments(caseData)}</div>`,
      `<div class="cd-col-rescuer">${renderProof(caseData)}</div>`,
      `</div>`,
    ].join(""),
  });
}
