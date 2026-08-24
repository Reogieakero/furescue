import { Button } from "/js/components/ui/button.js";
import { esc } from "../../health-records/components/util.js";
import { emptyState } from "../util.js";

export function DocumentsPanel(docs, { editing = false, mode = null } = {}) {
  const row = (d, i) => `
    <tr class="hr-doc-row" data-action="view-document" data-idx="${i}" style="cursor:pointer">
      <td class="table-cell table-cell--strong"><i data-lucide="file-text" class="hr-doc-ic"></i>${esc(d.name)}</td>
      <td class="table-cell table-cell--muted">${esc(d.type || "Document")}</td>
      <td class="table-cell table-cell--mono">${esc(d.meta || "—")}</td>
      <td class="table-cell table-cell--right">${
        editing
          ? Button({ text: "Edit", variant: "outline", size: "sm", attrs: `data-action="edit-document" data-idx="${i}"` })
          : d.fileUrl
            ? `<span class="hr-doc-open">View</span>`
            : '<span class="table-cell--muted">—</span>'
      }</td>
    </tr>`;
  const headActions = `
    ${editing && mode === "add" ? Button({ text: "Add", variant: "default", size: "sm", attrs: `data-action="open-document-modal"` }) : ""}
    ${docs && docs.length > 3 ? `<button type="button" class="hr-link" data-action="view-all-documents">View all ${docs.length} documents</button>` : ""}
  `;
  const list = (docs || []).slice(0, 3);
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="file-text"></i><h3 class="panel-title">Health Documents</h3></div>
      ${headActions ? `<div class="panel-head-actions">${headActions}</div>` : ""}
    </div>
    <div class="panel-body">
      ${
        list.length
          ? `<div class="table-wrap hr-doc-table-wrap"><table class="table"><thead class="table-head"><tr><th>Document</th><th>Type</th><th>Meta</th><th class="table-cell--right">File</th></tr></thead><tbody>${list.map(row).join("")}</tbody></table></div>`
          : emptyState("No documents uploaded")
      }
    </div>
  </section>`;
}
