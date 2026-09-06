
export function shortId(id) {
  if (!id) return "—";
  const s = String(id).replace(/-/g, "");
  return "#" + s.slice(0, 4).toUpperCase();
}

export function truncate(text, n = 22) {
  if (!text) return "—";
  const t = String(text);
  return t.length > n ? t.slice(0, n - 1) + "…" : t;
}

export function initials(name) {
  if (!name) return "?";
  return String(name)
    .split(/\s+/)
    .filter(Boolean)
    .slice(0, 2)
    .map((w) => w[0])
    .join("")
    .toUpperCase();
}

export function titleCase(value) {
  return String(value ?? "")
    .replace(/_/g, " ")
    .split(/\s+/)
    .filter(Boolean)
    .map((w) => w.charAt(0).toUpperCase() + w.slice(1))
    .join(" ");
}

export function timeAgo(value) {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const day = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const diff = Math.round((today - day) / 86400000);
  if (diff === 0) return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  if (diff === 1) return "Yesterday";
  if (diff < 7) return `${diff} days ago`;
  return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

export function buildWeekChart(trends) {
  const map = {};
  (trends || []).forEach((t) => {
    map[t.day] = parseInt(t.completed, 10) || 0;
  });
  const key = (d) => d.toISOString().slice(0, 10);

  const bars = [];
  for (let i = 6; i >= 0; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    bars.push({ day: d.toLocaleDateString("en-US", { weekday: "short" }), count: map[key(d)] || 0 });
  }
  const max = Math.max(1, ...bars.map((b) => b.count));
  const peak = bars.findIndex((b) => b.count === max);
  const out = bars.map((b, i) => ({
    day: b.day,
    count: b.count,
    h: Math.round((b.count / max) * 100),
    coral: i === peak,
  }));

  const cur = bars.reduce((s, b) => s + b.count, 0);
  let prev = 0;
  for (let i = 13; i >= 7; i--) {
    const d = new Date();
    d.setDate(d.getDate() - i);
    prev += map[key(d)] || 0;
  }
  let growth = null;
  if (prev > 0) growth = Math.round(((cur - prev) / prev) * 100);
  return { bars: out, growth };
}

export async function safe(promise, fallback) {
  try {
    return await promise;
  } catch {
    return fallback;
  }
}

export function EmptyRow(colspan, text) {
  return `<tr><td class="table-cell table-cell--muted" colspan="${colspan}">${text}</td></tr>`;
}
