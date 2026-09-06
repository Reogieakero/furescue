export function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) =>
    ({
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;",
    }[c])
  );
}

export const ROLE_LABELS = {
  admin: "Admin",
  rescuer: "Rescuer",
  resident: "Resident",
};

export const STATUS_LABELS = {
  active: "Active",
  pending: "Pending",
  suspended: "Suspended",
  rejected: "Rejected",
};

export function roleLabel(role) {
  return ROLE_LABELS[role] || String(role || "—").replace(/_/g, " ");
}

export function statusLabel(status) {
  return STATUS_LABELS[status] || String(status || "—").replace(/_/g, " ");
}

export function roleStamp(role) {
  if (role === "admin") return "stamp--coral";
  if (role === "rescuer") return "stamp--accent";
  return "stamp--muted";
}

export function statusStamp(status) {
  if (status === "pending") return "stamp--coral";
  if (status === "suspended" || status === "rejected") return "stamp--muted";
  return "stamp--accent";
}

export function panelTitle(filter) {
  if (filter === "admin") return "Admins";
  if (filter === "rescuer") return "Rescuers";
  if (filter === "resident") return "Residents";
  if (filter === "pending") return "Pending accounts";
  if (filter === "suspended") return "Suspended accounts";
  if (filter === "active") return "Active accounts";
  return "All accounts";
}

export function emptyMessage(filter, query) {
  if (query && query.trim()) return "No accounts match.";
  if (filter === "admin") return "No admin accounts.";
  if (filter === "rescuer") return "No rescuer accounts.";
  if (filter === "resident") return "No resident accounts.";
  if (filter === "pending") return "No pending accounts.";
  if (filter === "suspended") return "No suspended accounts.";
  return "No user accounts yet.";
}

export function tabsHtml(attr, options, active) {
  return options
    .map((o) => {
      const disabled = o.disabled ? " disabled aria-disabled=\"true\"" : "";
      const cls = `q-btn${o.value === active ? " is-active" : ""}${o.disabled ? " is-disabled" : ""}`;
      return `<button type="button" class="${cls}" data-${attr}="${esc(o.value)}"${disabled}>${esc(o.label)}</button>`;
    })
    .join("");
}
