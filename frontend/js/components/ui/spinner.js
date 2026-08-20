import { cn } from "../../lib/utils.js";

export function Spinner({ className = "", size = 24 } = {}) {
  return `<i
    data-lucide="loader-circle"
    class="${cn("animate-spin", className)}"
    style="width:${size}px;height:${size}px"
  ></i>`;
}
