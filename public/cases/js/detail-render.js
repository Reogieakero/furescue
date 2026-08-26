import { Button } from "../../js/components/ui/button.js";
import { esc, timeAgo } from "../../js/lib/format.js";
import { shortId, statusChip } from "./status.js";

function spec(label, value) {
  return `<dt>${esc(label)}</dt><dd>${value}</dd>`;
}

function photoGrid(urls, emptyText) {
  if (!urls.length) {
    return `<div class="rempty"><i data-lucide="image-off"></i><p class="rempty-text">${esc(emptyText)}</p></div>`;
  }
  const main = urls[0];
  const thumbs = urls
    .map(
      (url, i) =>
        `<a class="rgallery-thumb${i === 0 ? " is-active" : ""}" href="${esc(url)}" target="_blank" rel="noopener">
          <img src="${esc(url)}" alt="Case photo" loading="lazy">
        </a>`
    )
    .join("");
  return `
    <div class="min-w-0">
      <a class="rgallery-main" href="${esc(main)}" target="_blank" rel="noopener">
        <img src="${esc(main)}" alt="Case photo">
      </a>
      ${urls.length > 1 ? `<div class="rgallery-thumbs">${thumbs}</div>` : ""}
    </div>`;
}

function actionBar(item) {
  if (item.status === "assigned") {
    return `
      <div class="rpage-actions" style="flex-wrap:wrap">
        ${Button({ text: "Accept", icon: "check", attrs: `data-case-act="accept" data-id="${esc(item.id)}"` })}
        ${Button({ text: "Decline", variant: "destructive", icon: "x", attrs: `data-case-act="decline" data-id="${esc(item.id)}"` })}
      </div>`;
  }
  return "";
}

function proofPanel(item) {
  const canUpload = item.status === "in_progress";
  const photos = item.resolution_photos || [];
  if (!canUpload && !photos.length && item.status !== "resolved") return "";

  const form = canUpload
    ? `<form id="proof-form" class="file-attach mt-4" enctype="multipart/form-data">
        <span class="file-attach__title field-label"><i data-lucide="image-plus"></i>Upload proof photos</span>
        <p class="field-hint">Choose image files from this device. URL paste is not accepted.</p>
        <div class="file-attach__pick">
          <input id="proof-files" class="input" type="file" name="files[]" accept="image/jpeg,image/png,image/gif,image/webp" multiple>
        </div>
        <p id="proof-names" class="file-attach__names text-xs text-muted-foreground" hidden></p>
        <p class="rform-error" id="proof-error" hidden><i data-lucide="alert-circle"></i><span></span></p>
        <button type="submit" class="rbtn rbtn--solid rbtn--sm mt-2" id="proof-submit">
          <i data-lucide="upload"></i><span>Upload proof</span>
        </button>
      </form>`
    : "";

  return `
    <section class="rcard min-w-0">
      <div class="rcard-head">
        <h2 class="rcard-title"><i data-lucide="camera"></i>Rescue proof</h2>
      </div>
      <div class="rcard-pad">
        ${photoGrid(photos, canUpload ? "No proof uploaded yet." : "No rescue proof uploaded.")}
        ${form}
      </div>
    </section>`;
}

export function renderDetail(item) {
  const notes = String(item.resolution_notes || "").trim();
  return `
    <div class="rpage-head">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          ${statusChip(item.status)}
          <span class="text-xs text-muted-foreground">${esc(shortId(item.id))}</span>
        </div>
        <h1 class="rpage-title mt-2">Rescue case</h1>
        <p class="rpage-sub">Updated ${esc(timeAgo(item.updated_at || item.created_at) || "recently")}</p>
      </div>
      ${actionBar(item)}
    </div>

    <div class="rdetail-grid">
      <section class="rcard min-w-0">
        <div class="rcard-head">
          <h2 class="rcard-title"><i data-lucide="clipboard-list"></i>Case details</h2>
        </div>
        <div class="rcard-pad">
          <dl class="rspec-list">
            ${spec("Status", statusChip(item.status))}
            ${spec("Created", esc(timeAgo(item.created_at) || "—"))}
            ${spec("Updated", esc(timeAgo(item.updated_at) || "—"))}
            ${notes ? spec("Notes", esc(notes)) : ""}
          </dl>
        </div>
      </section>

      <section class="rcard min-w-0">
        <div class="rcard-head">
          <h2 class="rcard-title"><i data-lucide="file-text"></i>Source report</h2>
        </div>
        <div class="rcard-pad">
          <dl class="rspec-list">
            ${spec("Animal", esc(item.animal_description || "—"))}
            ${spec("Location", esc(item.address_text || "—"))}
          </dl>
          <div class="mt-4">${photoGrid(item.photo_urls || [], "No report photos.")}</div>
        </div>
      </section>

      ${proofPanel(item)}
    </div>`;
}

export function renderDetailError(message, { missing = false } = {}) {
  return `
    <div class="rempty">
      <i data-lucide="${missing ? "search-x" : "triangle-alert"}"></i>
      <p class="rempty-title">${missing ? "Case not found" : "Could not load this case"}</p>
      <p class="rempty-text">${esc(message)}</p>
      <a href="/cases/" class="rbtn rbtn--ghost"><i data-lucide="arrow-left"></i><span>Back to My Cases</span></a>
    </div>`;
}

export function renderDetailLoading() {
  return `<div class="rempty"><i data-lucide="loader-circle"></i><p class="rempty-text">Loading case…</p></div>`;
}
