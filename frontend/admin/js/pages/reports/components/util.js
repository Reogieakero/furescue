export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function stampCls(status) {
  if (status === "dismissed" || status === "rejected") return "stamp--muted";
  if (status === "assigned" || status === "pending_verification" || status === "open") return "stamp--coral";
  return "stamp--accent";
}
