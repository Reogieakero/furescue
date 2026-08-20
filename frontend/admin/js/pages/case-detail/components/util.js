export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function caseStampCls(status) {
  if (status === "in_progress" || status === "resolved") return "stamp--accent";
  return "stamp--coral";
}

export function formatMeta(value, key) {
  if (value && typeof value === "string" && value.trim() === key) {
    return value.split(/^\s*,\s*/).join("");
  }
  if (value && value[key]) return value[key];
  return "";
}
