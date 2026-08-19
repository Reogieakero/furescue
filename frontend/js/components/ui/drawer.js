import { createIcons, icons } from "lucide";
import { Button } from "./button.js";

// Tracks the currently open drawer so callers (e.g. hover-preview) can close it.
let activeDrawer = null;

// shadcn-style Drawer (bottom sheet, no framework — DOM based).
// Usage:
//   openDrawer({
//     title: "Report details",
//     description: "#ABCD · Mati Poblacion",
//     body: `<div class="drawer-info">…</div><div id="report-detail-map" class="drawer-map"></div>`,
//     footer: Button({ text: "Close", variant: "outline", attrs: 'data-act="close"' }),
//     onMount: (bodyEl) => { /* init Leaflet map here */ },
//   });
// Resolves when the drawer is closed (overlay click, X, or Escape).

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function openDrawer({
  title = "",
  body = "",
  footer = "",
  onMount = null,
  elevated = false,
} = {}) {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "drawer-overlay" + (elevated ? " drawer-overlay--elevated" : "");
    overlay.innerHTML = `
      <div class="drawer" role="dialog" aria-modal="true" aria-labelledby="drawer-title">
        <div class="drawer-header">
          ${title ? `<h3 class="drawer-title" id="drawer-title">${esc(title)}</h3>` : ""}
          ${Button({ text: "Close", variant: "ghost", size: "sm", attrs: 'data-act="close"' })}
        </div>
        <div class="drawer-body">${body}</div>
        ${footer ? `<div class="drawer-foot">${footer}</div>` : ""}
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });
    requestAnimationFrame(() => overlay.classList.add("is-open"));

    const close = () => {
      overlay.classList.remove("is-open");
      setTimeout(() => overlay.remove(), 300);
      if (activeDrawer === overlay) activeDrawer = null;
      document.removeEventListener("keydown", escHandler);
      resolve();
    };

    activeDrawer = overlay;
    overlay.querySelectorAll('[data-act="close"]').forEach((el) => el.addEventListener("click", close));
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
    const escHandler = (e) => {
      if (e.key === "Escape") {
        close();
        document.removeEventListener("keydown", escHandler);
      }
    };
    document.addEventListener("keydown", escHandler);

    if (onMount) onMount(overlay.querySelector(".drawer-body"));
  });
}

// Closes the currently open drawer (used by the hover map preview).
export function closeDrawer() {
  if (!activeDrawer) return;
  const overlay = activeDrawer;
  activeDrawer = null;
  overlay.classList.remove("is-open");
  setTimeout(() => overlay.remove(), 300);
}

// Returns the open drawer's overlay element, or null.
export function getOpenDrawer() {
  return activeDrawer;
}