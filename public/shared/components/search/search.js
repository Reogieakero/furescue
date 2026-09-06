import { esc } from "/assets/js/lib/format.js";

export function Search({
  id = "",
  placeholder = "",
  value = "",
  className = "",
  attrs = "",
} = {}) {
  const cls = ["search-field", "report-search", className].filter(Boolean).join(" ");
  const extra = attrs ? ` ${attrs}` : "";
  return `<div class="${esc(cls)}"><i data-lucide="search"></i><input id="${esc(id)}" type="text" placeholder="${esc(placeholder)}" value="${esc(value)}"${extra}></div>`;
}
