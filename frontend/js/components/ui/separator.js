import { cn } from "../../lib/utils.js";

export function Separator({ label = "", className = "" } = {}) {
  if (label) {
    return `<div class="${cn("separator separator--label", className)}"><span>${label}</span></div>`;
  }
  return `<div class="${cn("separator", className)}" role="separator"></div>`;
}
