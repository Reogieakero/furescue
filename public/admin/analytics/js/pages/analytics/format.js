export const OVERVIEW_LABELS = {
  reports: "Total reports",
  reports_verified: "Reports verified",
  cases: "Total cases",
  cases_resolved: "Cases resolved",
  animals: "Total animals",
  animals_adopted: "Animals adopted",
  adoptions_pending: "Adoptions pending",
  adoptions_completed: "Adoptions completed",
  rescuers_on_duty: "Rescuers on duty",
  residents: "Residents",
};

export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

export function timeAgo(value) {
  if (!value) return "—";
  const ts = new Date(value).getTime();
  if (Number.isNaN(ts)) return "—";
  const day = new Date(ts);
  day.setHours(0, 0, 0, 0);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const diff = Math.round((today - day) / 86400000);
  if (diff === 0) {
    return new Date(ts).toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  }
  if (diff === 1) return "Yesterday";
  if (diff < 7) return `${diff} days ago`;
  return new Date(ts).toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

export function shortId(id) {
  if (!id) return "—";
  return `#${String(id).replace(/-/g, "").slice(0, 4).toUpperCase()}`;
}

export function emptyState(icon, text) {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${esc(text)}</span></div>`;
}

export function mapHealthUpdate(h) {
  const healthy = (h.health_status ?? "") === "healthy";
  const parts = [h.animal_name ?? "", h.breed_type ?? ""].filter((p) => p !== "");
  return {
    id: shortId(h.id),
    animal: parts.length ? parts.join(", ") : "Unnamed animal",
    by: h.logged_by_name || "—",
    when: timeAgo(h.logged_at),
    status: healthy ? "Stable" : "Needs Attention",
    statusCls: healthy ? "stamp--accent" : "stamp--coral",
  };
}
