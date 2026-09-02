import { createIcons, icons } from "lucide";
import { Button } from "/assets/js/components/ui/button.js";
import { Spinner } from "/assets/js/components/ui/spinner.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { openDrawer, closeDrawer } from "/assets/js/components/ui/drawer.js";
import { uploadAnimalDocument, updateAnimalDocument } from "/assets/js/admin/admin-data.js";
import { record, reloadRecord } from "../context.js";
import { resolveDocUrl } from "../util.js";
import { esc } from "../../health-records/components/util.js";

export function openDocumentPreview(idx) {
  const doc = (record.documents || [])[idx];
  if (!doc) return;
  const rawUrl = doc.fileUrl || doc.url || doc.file_url || (doc.file && doc.file.url) || "";
  const fileUrl = resolveDocUrl(rawUrl);
  const nameHint = doc.fileUrl || doc.url || doc.file_url || doc.name || "";
  const isImage = /\.(jpe?g|png|gif|webp|avif|bmp)$/i.test(nameHint) || (fileUrl && /\.(jpe?g|png|gif|webp|avif|bmp)$/i.test(fileUrl));
  const body = fileUrl
    ? isImage
      ? `<img class="hr-doc-preview-img" src="${esc(fileUrl)}" alt="${esc(doc.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="empty-state" style="display:none"><i data-lucide="image-off"></i><span>Could not load image preview.</span></div>`
      : `<iframe class="hr-doc-preview-frame" src="${esc(fileUrl)}" title="${esc(doc.name)}"></iframe>`
    : `<div class="empty-state"><i data-lucide="file-x"></i><span>No file attached to this document.</span></div>`;
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="doc-prev-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="file-text" class="dialog-icon"></i>
          <h3 class="dialog-title" id="doc-prev-title">${esc(doc.name)}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body hr-doc-preview-body">
        ${body}
        ${doc.type || doc.meta ? `<p class="hr-doc-preview-meta">${esc([doc.type, doc.meta].filter(Boolean).join(" · "))}</p>` : ""}
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Close", variant: "outline", attrs: 'data-act="cancel"' })}
        ${fileUrl ? Button({ text: "Open original", variant: "default", attrs: `data-act="open-orig" data-url="${esc(fileUrl)}"` }) : ""}
      </div>`;
  document.body.appendChild(overlay);
  createIcons({ icons });

  const close = () => overlay.remove();
  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  const openOrig = overlay.querySelector('[data-act="open-orig"]');
  if (openOrig) {
    openOrig.addEventListener("click", () => window.open(openOrig.getAttribute("data-url"), "_blank", "noopener"));
  }
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

export function openAllDocumentsDrawer(docs) {
  const list = docs || [];
  const row = (d, i) => `
    <div class="hr-doc-row-card" data-action="view-document" data-idx="${i}" style="cursor:pointer">
      <div class="hr-doc-row-head">
        <span class="hr-doc-row-name"><i data-lucide="file-text"></i>${esc(d.name)}</span>
        ${d.fileUrl ? `<span class="hr-doc-open">View</span>` : ""}
      </div>
      <div class="hr-doc-row-meta">
        <span>Type: <strong>${esc(d.type || "Document")}</strong></span>
        <span>Meta: <strong>${esc(d.meta || "—")}</strong></span>
      </div>
    </div>`;
  openDrawer({
    title: `All documents (${list.length})`,
    body: list.length
      ? `<div class="hr-doc-list">${list.map(row).join("")}</div>`
      : `<div class="empty-state"><i data-lucide="file-x"></i><span>No documents uploaded.</span></div>`,
    onMount: (bodyEl) => {
      bodyEl.querySelectorAll(".hr-doc-row-card").forEach((card) => {
        card.addEventListener("click", () => {
          const idx = parseInt(card.getAttribute("data-idx") || "-1", 10);
          if (!Number.isNaN(idx) && idx >= 0) {
            closeDrawer();
            openDocumentPreview(idx);
          }
        });
      });
    },
  });
}

export function openDocumentDialog(editIdx = null) {
  const editing = editIdx !== null && Array.isArray(record.documents) && !!record.documents[editIdx];
  const current = editing ? record.documents[editIdx] : {};
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="doc-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="file-text" class="dialog-icon"></i>
          <h3 class="dialog-title" id="doc-title">${editing ? "Edit Document" : "Upload Document"}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        ${
          editing
            ? ""
            : `<label class="dialog-label">File (PDF or image)</label>
        <div class="aa-photo">
          <div class="aa-photo-preview" id="doc-file-preview"><i data-lucide="file-plus"></i></div>
          <input type="file" id="doc-file" class="aa-photo-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required />
          <span class="hr-doc-filename" id="doc-filename"></span>
        </div>`
        }
        <label class="dialog-label">Name<input class="hr-input" id="doc-name" value="${esc(current.name || "")}" placeholder="e.g. Vaccination Certificate"></label>
        <label class="dialog-label">Type<input class="hr-input" id="doc-type" value="${esc(current.type || "")}" placeholder="e.g. Certificate"></label>
        <label class="dialog-label">Notes / Meta<input class="hr-input" id="doc-meta" value="${esc(current.meta || "")}" placeholder="Optional"></label>
        <p class="dialog-error" id="doc-error" hidden></p>
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
        ${Button({ text: editing ? "Save" : "Upload", variant: "default", attrs: 'data-act="ok"' })}
      </div>`;
  document.body.appendChild(overlay);
  createIcons({ icons });

  const fileInput = overlay.querySelector("#doc-file");
  const fileNameEl = overlay.querySelector("#doc-filename");
  const filePreview = overlay.querySelector("#doc-file-preview");
  if (fileInput && fileNameEl) {
    fileInput.addEventListener("change", () => {
      const f = fileInput.files && fileInput.files[0];
      fileNameEl.textContent = f ? f.name : "";
      if (filePreview) {
        if (f && f.type.startsWith("image/")) {
          const reader = new FileReader();
          reader.onload = (e) => {
            filePreview.style.backgroundImage = "";
            filePreview.innerHTML = `<img src="${e.target.result}" alt="${esc(f.name)}" style="width:100%;height:100%;object-fit:cover;" />`;
          };
          reader.readAsDataURL(f);
        } else {
          filePreview.style.backgroundImage = "";
          filePreview.innerHTML = '<i data-lucide="file-plus"></i>';
          createIcons({ icons });
        }
      }
    });
  }

  const errorEl = overlay.querySelector("#doc-error");
  const okBtn = overlay.querySelector('[data-act="ok"]');
  const close = () => overlay.remove();

  const submit = async () => {
    const name = (overlay.querySelector("#doc-name")?.value || "").trim();
    const type = (overlay.querySelector("#doc-type")?.value || "").trim() || null;
    const meta = (overlay.querySelector("#doc-meta")?.value || "").trim() || null;
    if (!name) {
      errorEl.textContent = "Please enter a document name.";
      errorEl.hidden = false;
      return;
    }
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Saving…</span>`;
    try {
      if (editing) {
        await updateAnimalDocument(current.id, { name, doc_type: type, meta });
        toast("Document updated.", { type: "success" });
      } else {
        const fileInputEl = overlay.querySelector("#doc-file");
        if (!fileInputEl || !fileInputEl.files || !fileInputEl.files[0]) {
          errorEl.textContent = "Please choose a PDF or image file.";
          errorEl.hidden = false;
          okBtn.disabled = false;
          okBtn.innerHTML = `<span>Upload</span>`;
          return;
        }
        const fd = new FormData();
        fd.append("file", fileInputEl.files[0]);
        fd.append("name", name);
        if (type) fd.append("doc_type", type);
        if (meta) fd.append("meta", meta);
        await uploadAnimalDocument(record.id, fd);
        toast("Document uploaded.", { type: "success" });
      }
      close();
      await reloadRecord();
    } catch (err) {
      okBtn.disabled = false;
      okBtn.innerHTML = `<span>${editing ? "Save" : "Upload"}</span>`;
      createIcons({ icons });
      errorEl.textContent = err && err.message ? err.message : "Could not save document.";
      errorEl.hidden = false;
    }
  };

  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}
