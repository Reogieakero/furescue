import { Button } from "/js/components/ui/button.js";

export function PageHead(hasRecord, editing, mode) {
  const actions = hasRecord
    ? `${Button({ text: editing ? "Done" : "Edit", variant: "outline", attrs: `data-action="edit-record"` })}
       ${editing ? Button({ text: uiSaveLabel(mode), variant: "default", attrs: `data-action="save-record"` }) : ""}
       ${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}
       ${Button({ text: "Post for adoption", variant: "outline", attrs: `data-action="post-for-adoption"` })}
       ${Button({ text: "Delete", variant: "destructive", attrs: `data-action="delete-record"` })}`
    : `${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}`;
  return `
  <div class="page-head">
    <div>
      <a href="/admin/health-records/" class="cd-back"><i data-lucide="chevron-left"></i> Back to health records</a>
    </div>
    <div class="page-head-actions">
      ${actions}
    </div>
  </div>`;
}

function uiSaveLabel(mode) {
  return mode === "add" ? "Save" : "Save";
}
