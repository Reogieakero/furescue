import { cn } from "../../lib/utils.js";

export function Label({ htmlFor = "", children = "", className = "" } = {}) {
  return `<label for="${htmlFor}" class="${cn("label", className)}">${children}</label>`;
}
