import { Button } from "/shared/components/button/button.js";
import { record } from "../context.js";
import { HEALTH_READY_HINT, isHealthReady } from "../actions.js";

export function PageHead(hasRecord, editing, mode) {
  const ready = isHealthReady(record);
  const postAttrs = ready
    ? 'data-action="post-for-adoption"'
    : `data-action="post-for-adoption" disabled aria-disabled="true" title="${HEALTH_READY_HINT}"`;
  const actions = hasRecord
    ? `${Button({ text: editing ? "Done" : "Edit", variant: "outline", attrs: `data-action="edit-record"` })}
       ${editing ? Button({ text: uiSaveLabel(mode), variant: "default", attrs: `data-action="save-record"` }) : ""}
       ${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}
       ${Button({ text: "Post for adoption", variant: "outline", attrs: postAttrs })}
       ${Button({ text: "Delete", variant: "destructive", attrs: `data-action="delete-record"` })}`
    : `${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}`;
  return `
  <div class="page-head">
    <div>
      ${Button({ text: "Back to health records", variant: "outline", size: "sm", icon: "arrow-left", href: "/admin/health-records/" })}
    </div>
    <div class="page-head-actions">
      ${actions}
    </div>
  </div>`;
}

function uiSaveLabel(mode) {
  return mode === "add" ? "Save" : "Save";
}
