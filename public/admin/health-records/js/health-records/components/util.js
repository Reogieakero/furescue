export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

export function fmtDate(value, style = "short") {
  if (!value) return "—";
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return "—";
  if (style === "short") {
    return `${MONTHS[d.getMonth()]} ${d.getDate()}, ${String(d.getFullYear()).slice(2)}`;
  }
  if (style === "mono") {
    return `${String(d.getMonth() + 1).padStart(2, "0")}/${String(d.getDate()).padStart(2, "0")}`;
  }
  return d.toLocaleDateString("en-US", { year: "numeric", month: "short", day: "numeric" });
}

// Whole days from today until the given date (negative = past).
export function daysUntil(value) {
  const d = new Date(value);
  if (Number.isNaN(d.getTime())) return 0;
  const today = new Date();
  const a = Date.UTC(d.getFullYear(), d.getMonth(), d.getDate());
  const b = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());
  return Math.round((a - b) / 86400000);
}

export const VACC_TONE = {
  complete: { cls: "stamp--accent", label: "Complete" },
  partial: { cls: "stamp--muted", label: "Partial" },
  none: { cls: "stamp--coral", label: "Not vaccinated" },
};

export async function safe(promise, fallback) {
  try {
    return await promise;
  } catch {
    return fallback;
  }
}

export function shortId(id) {
  if (!id) return "—";
  const s = String(id).replace(/-/g, "");
  return "#" + s.slice(0, 4).toUpperCase();
}

export const HEALTH_TONE = {
  healthy: { cls: "status-text--accent", label: "Healthy" },
  not_healthy: { cls: "status-text--muted", label: "Needs care" },
};
