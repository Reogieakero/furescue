import { cn } from "../../lib/utils.js";

// shadcn-style Input
export function Input({
  id = "",
  name = "",
  type = "text",
  placeholder = "",
  value = "",
  className = "",
  attrs = "",
} = {}) {
  return `<input
    id="${id}"
    name="${name}"
    type="${type}"
    placeholder="${placeholder}"
    value="${value}"
    class="${cn("input", className)}"
    ${attrs}
  />`;
}
