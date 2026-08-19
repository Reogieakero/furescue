import { cn } from "../../lib/utils.js";

// shadcn-style Separator (with optional centered label)
export function Separator({ label = "", className = "" } = {}) {
  if (label) {
    return `<div class="${cn("separator separator--label", className)}"><span>${label}</span></div>`;
  }
  return `<div class="${cn("separator", className)}" role="separator"></div>`;
}
