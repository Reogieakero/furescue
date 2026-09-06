import { createIcons, icons } from "lucide";

const ICONS = {
  success: "check-circle-2",
  error: "alert-circle",
  info: "info",
  default: "bell-ring",
};

let viewport = null;
let currentEl = null;
let hideTimer = null;

function ensureViewport() {
  if (!viewport || !document.body.contains(viewport)) {
    viewport = document.body.querySelector(":scope > .toast-viewport:not(.toast-viewport--adoption)");
    if (!viewport) {
      viewport = document.createElement("div");
      viewport.className = "toast-viewport";
      viewport.setAttribute("aria-live", "polite");
      document.body.appendChild(viewport);
    }
  }
  return viewport;
}

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function removeCurrent() {
  if (hideTimer) {
    clearTimeout(hideTimer);
    hideTimer = null;
  }
  if (currentEl) {
    currentEl.remove();
    currentEl = null;
  }
  document
    .querySelectorAll(".toast-viewport:not(.toast-viewport--adoption) .toast")
    .forEach((node) => node.remove());
}

export function toast(message, { type = "default", duration = 3500 } = {}) {
  removeCurrent();

  const el = document.createElement("div");
  el.className = `toast toast--${type}`;
  el.setAttribute("role", "status");
  el.innerHTML = `
    <i data-lucide="${ICONS[type] || ICONS.default}" class="toast-icon"></i>
    <p class="toast-message">${esc(message)}</p>
    <button class="toast-close" aria-label="Dismiss"><i data-lucide="x"></i></button>
  `;

  currentEl = el;
  ensureViewport().appendChild(el);
  createIcons({ icons });
  requestAnimationFrame(() => el.classList.add("is-visible"));

  const dismiss = () => {
    if (currentEl !== el) return;
    if (hideTimer) {
      clearTimeout(hideTimer);
      hideTimer = null;
    }
    el.classList.remove("is-visible");
    setTimeout(() => {
      if (el.parentNode) el.remove();
      if (currentEl === el) currentEl = null;
    }, 200);
  };

  el.querySelector(".toast-close").addEventListener("click", dismiss);
  if (duration > 0) {
    hideTimer = setTimeout(dismiss, duration);
  }

  return dismiss;
}
