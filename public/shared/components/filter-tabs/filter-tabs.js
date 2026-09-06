import { esc } from "/assets/js/lib/format.js";

export function FilterTabs({ id = "", children = "", className = "" } = {}) {
  const cls = ["q-tabs", className].filter(Boolean).join(" ");
  const idAttr = id ? ` id="${esc(id)}"` : "";
  return `<div${idAttr} class="${esc(cls)}">${children}</div>`;
}

export function FilterTab({ key = "", label = "", active = false, attrs = "" } = {}) {
  const cls = "q-btn" + (active ? " is-active" : "");
  const data = key !== "" ? ` data-filter="${esc(key)}"` : "";
  const extra = attrs ? ` ${attrs}` : "";
  return `<button${data} class="${esc(cls)}"${extra}>${label}</button>`;
}
