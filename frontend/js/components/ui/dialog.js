import { createIcons, icons } from "lucide";
import { toast } from "./toast.js";
import { Button } from "./button.js";
import { Spinner } from "./spinner.js";

// shadcn-style confirm Dialog (no framework — DOM based).
// Usage:
//   const ok = await confirmDialog({
//     title: "Dismiss report",
//     message: "Are you sure?",
//     info: [
//       { label: "Case", value: "#ABCD" },
//       { label: "Barangay", value: "Mati Poblacion" },
//     ],
//     confirmText: "Dismiss",
//     danger: true,
//     withReason: true,
//     reasonLabel: "Dismiss reason",
//     reasonRequired: true,
//     run: ({ reason }) => api.dismissReport(id, reason),
//   });
// Resolves with the run() result on success, false on cancel. On error it
// shows a toast, keeps the dialog open (spinner shown in the confirm button
// while running). If `onError` is provided it is called instead of the toast.
// The confirm/cancel buttons use the shadcn Button component.

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function detailsDialog({ title = "Details", info = [] } = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="info" class="dialog-icon"></i>
            <h3 class="dialog-title" id="dialog-title">${esc(title)}</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          <div class="dialog-info">
            ${info
              .map(
                (row) => `
              <div class="dialog-info-row">
                <span class="dialog-info-label">${esc(row.label)}</span>
                <span class="dialog-info-value">${esc(row.value)}</span>
              </div>`
              )
              .join("")}
          </div>
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Close", variant: "outline", attrs: 'data-act="close"' })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });

    const close = () => {
      overlay.remove();
      resolve();
    };
    overlay.querySelector('[data-act="close"]').addEventListener("click", close);
    overlay.querySelector(".dialog-x").addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
  });
}

export function confirmDialog({
  title = "Are you sure?",
  message = "",
  info = [],
  confirmText = "Confirm",
  cancelText = "Cancel",
  danger = false,
  withReason = false,
  reasonLabel = "Reason",
  reasonRequired = false,
  run = null,
  onError = null,
} = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="dialog-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="${danger ? "alert-triangle" : "shield-question"}" class="dialog-icon ${danger ? "dialog-icon--danger" : ""}"></i>
            <h3 class="dialog-title" id="dialog-title">${esc(title)}</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          ${message ? `<p class="dialog-message">${esc(message)}</p>` : ""}
          ${info.length ? `
            <div class="dialog-info">
              ${info
                .map(
                  (row) => `
                <div class="dialog-info-row">
                  <span class="dialog-info-label">${esc(row.label)}</span>
                  <span class="dialog-info-value">${esc(row.value)}</span>
                </div>`
                )
                .join("")}
            </div>` : ""}
          ${withReason ? `
            <label class="dialog-label" for="dialog-reason">${esc(reasonLabel)}${reasonRequired ? ' <span class="dialog-req">*</span>' : ""}</label>
            <textarea class="dialog-input" id="dialog-reason" rows="3" placeholder="Type a reason..."></textarea>
            <p class="dialog-error" hidden>Please provide a reason.</p>` : ""}
        </div>
        <div class="dialog-foot">
          ${Button({ text: cancelText, variant: "outline", attrs: 'data-act="cancel"' })}
          ${Button({ text: confirmText, variant: danger ? "destructive" : "default", attrs: 'data-act="ok"' })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });

    const okBtn = overlay.querySelector('[data-act="ok"]');
    const cancelBtn = overlay.querySelector('[data-act="cancel"]');
    const xBtn = overlay.querySelector(".dialog-x");
    const reasonEl = withReason ? overlay.querySelector("#dialog-reason") : null;
    const errorEl = withReason ? overlay.querySelector(".dialog-error") : null;
    let settled = false;

    const close = () => {
      if (settled) return;
      settled = true;
      overlay.remove();
      resolve(false);
    };

    const restore = () => {
      okBtn.disabled = false;
      okBtn.innerHTML = `<span>${esc(confirmText)}</span>`;
    };

    const submit = async () => {
      if (okBtn.disabled) return;
      if (reasonRequired && reasonEl && !reasonEl.value.trim()) {
        if (errorEl) errorEl.hidden = false;
        if (reasonEl) reasonEl.focus();
        return;
      }
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>${esc(confirmText)}</span>`;
      createIcons({ icons });
      try {
        const result = run ? await run({ reason: reasonEl ? reasonEl.value.trim() : "" }) : null;
        settled = true;
        overlay.remove();
        resolve(result ?? true);
      } catch (err) {
        restore();
        if (onError) onError(err);
        else toast(err && err.message ? err.message : "Action failed.", { type: "error" });
      }
    };

    okBtn.addEventListener("click", submit);
    cancelBtn.addEventListener("click", close);
    xBtn.addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
    if (reasonEl) {
      reasonEl.addEventListener("input", () => {
        if (errorEl) errorEl.hidden = true;
      });
    }
  });
}