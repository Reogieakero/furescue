import { esc } from "/assets/js/lib/format.js";

export function EmptyState({ icon = "inbox", text = "No records.", className = "" } = {}) {
  const cls = ["empty-state", className].filter(Boolean).join(" ");
  return `<div class="${esc(cls)}"><i data-lucide="${esc(icon)}"></i><span>${esc(text)}</span></div>`;
}
