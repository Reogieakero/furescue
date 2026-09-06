export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function timeAgo(input) {
  if (!input) return "";
  const then = new Date(String(input).replace(" ", "T") + (String(input).includes("Z") || String(input).includes("+") ? "" : "Z"));
  const ms = Date.now() - then.getTime();
  if (Number.isNaN(ms)) return String(input);
  const mins = Math.floor(ms / 60000);
  if (mins < 1) return "just now";
  if (mins < 60) return `${mins}m ago`;
  const hours = Math.floor(mins / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  if (days < 7) return `${days}d ago`;
  return then.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
}

export function clockTime(input) {
  if (!input) return "";
  const d = new Date(String(input).replace(" ", "T") + (String(input).includes("Z") || String(input).includes("+") ? "" : "Z"));
  if (Number.isNaN(d.getTime())) return String(input);
  return d.toLocaleTimeString("en-US", { hour: "numeric", minute: "2-digit" });
}
