import { esc } from "/assets/js/lib/format.js";

export function Toolbar({ children = "", className = "" } = {}) {
  const cls = ["report-toolbar", className].filter(Boolean).join(" ");
  return `<div class="${esc(cls)}">${children}</div>`;
}
