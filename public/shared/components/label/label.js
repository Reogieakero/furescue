import { cn } from "/assets/js/lib/utils.js";

export function Label({ htmlFor = "", children = "", className = "", required = false } = {}) {
  const mark = required ? ` <span class="label-req" aria-hidden="true">*</span>` : "";
  const inner = required ? `<span class="label-text">${children}${mark}</span>` : children;
  return `<label for="${htmlFor}" class="${cn("label", className)}">${inner}</label>`;
}
