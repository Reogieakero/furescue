import { createIcons, icons } from "lucide";

// Full-page overlay loader (shadcn-style spinner).
// Usage: showLoader("Signing in…") / hideLoader()

let overlay = null;

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function ensureOverlay() {
  if (!overlay || !document.body.contains(overlay)) {
    overlay = document.createElement("div");
    overlay.className = "loader-overlay";
    document.body.appendChild(overlay);
  }
  return overlay;
}

export function showLoader(message = "Please wait…") {
  const el = ensureOverlay();
  el.innerHTML = `
    <div class="loader-box">
      <i data-lucide="loader-circle" class="animate-spin loader-icon"></i>
      <p class="loader-text">${esc(message)}</p>
    </div>
  `;
  createIcons({ icons });
  requestAnimationFrame(() => el.classList.add("is-visible"));
}

export function hideLoader() {
  if (overlay && document.body.contains(overlay)) {
    overlay.classList.remove("is-visible");
  }
}