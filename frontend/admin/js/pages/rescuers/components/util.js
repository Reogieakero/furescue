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

export function dutyStampCls(status) {
  return (status || "off_duty") === "on_duty" ? "stamp--accent" : "stamp--muted";
}

export function dutyLabel(status) {
  return (status || "off_duty") === "on_duty" ? "On duty" : "Off duty";
}
