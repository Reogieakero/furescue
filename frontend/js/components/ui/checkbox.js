import { cn } from "../../lib/utils.js";

export function Checkbox({
  id = "",
  name = "",
  checked = false,
  className = "",
  attrs = "",
} = {}) {
  return `<input
    type="checkbox"
    id="${id}"
    name="${name}"
    ${checked ? "checked" : ""}
    class="${cn("checkbox", className)}"
    ${attrs}
  />`;
}
