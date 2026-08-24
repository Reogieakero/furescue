import { Badge } from "/js/components/ui/badge.js";
import { Button } from "/js/components/ui/button.js";
import { esc, photos } from "./util.js";

export function renderAttachments(caseData) {
  const files = photos(caseData.report && caseData.report.photo_urls);
  const gallery = files.length
    ? `<div class="cd-files">${
        files
          .map(
            (f) =>
              `<a class="cd-file" href="${esc(f)}" target="_blank" rel="noopener"><img src="${esc(f)}" alt="Case attachment" loading="lazy"></a>`
          )
          .join("")}</div>`
    : `<div class="empty-state"><i data-lucide="image-off"></i><span>No attachments submitted.</span></div>`;
  return `
    <div class="panel case-detail-panel">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="paperclip"></i>
          <h2 class="panel-title">Attached files</h2>
        </div>
        ${files && files.length ? `<span class="stamp stamp--sm stamp--muted">${files.length}</span>` : ""}
      </div>
      <div class="panel-body">${gallery}</div>
    </div>`;
}

export function renderProof(caseData) {
  const proof = photos(caseData.resolution_photos);
  const gallery = proof.length
    ? `<div class="cd-files">${
        proof
          .map(
            (p) =>
              `<a class="cd-file" href="${esc(p)}" target="_blank" rel="noopener"><img src="${esc(p)}" alt="Rescue proof" loading="lazy"></a>`
          )
          .join("")}</div>`
    : `<div class="empty-state"><i data-lucide="image-off"></i><span>No rescue proof uploaded.</span></div>`;
  const addForm = `<div class="cd-proof-add">
        <input id="cd-proof-input" class="cd-proof-input" type="url" placeholder="Paste proof photo URL…">
        ${Button({ text: "Add", variant: "outline", size: "sm", icon: "plus", attrs: 'data-cd-action="add-proof"' })}
      </div>`;
  return `
    <div class="panel case-detail-panel">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="camera"></i>
          <h2 class="panel-title">Rescue proof</h2>
        </div>
        ${proof && proof.length ? `<div class="cd-rescuer-meta">${Badge({ text: caseData.rescuer_name || "Rescuer", variant: "secondary", icon: "user" })}</div>` : ""}
      </div>
      <div class="panel-body">
        ${gallery}
        ${addForm}
      </div>
    </div>`;
}
