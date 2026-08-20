import { AppShell } from "../../../layout/app-shell.js";
import { Button } from "../../../../../js/components/ui/button.js";
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
    attrs: 'data-cd-action="location"',
  });
  if (isResolved) {
    return `<div class="cd-actions">${locationBtn}${Button({ text: "Resolved", variant: "outline", size: "sm", icon: "check-circle-2", disabled: true })}</div>`;
  }
  const assignLabel = caseData.assigned_rescuer_id ? "Reassign" : "Assign rescuer";
  // Resolve only after the workflow reached the active stage (rescuer accepted).
  // While still `assigned` (waiting for the rescuer to accept) it must flow the
  // workflow and cannot be bypassed.
  const showResolve = caseData.status === "in_progress";
  return `
    <div class="cd-actions">
      ${locationBtn}
      ${Button({ text: assignLabel, variant: "outline", size: "sm", icon: "user-plus", attrs: 'data-cd-action="assign"' })}
      ${showResolve ? Button({ text: "Resolve", variant: "default", size: "sm", icon: "check-circle-2", attrs: 'data-cd-action="resolve"' }) : ""}
    </div>`;
}

function renderHeader(caseData) {
  return `
    <div class="page-head">
      <div>
        <a href="cases.html" class="cd-back"><i data-lucide="chevron-left"></i> Back to cases</a>
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
