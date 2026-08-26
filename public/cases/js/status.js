import { esc } from "../../js/lib/format.js";

/** DB status → display label only. Never send these strings back to the API. */
const STATUS_LABELS = {
  pending: "PENDING",
  pending_verification: "PENDING",
  open: "PENDING",
  verified: "VERIFIED",
  assigned: "ASSIGNED",
  in_progress: "IN PROGRESS",
  resolved: "RESOLVED",
};

const STATUS_CHIP = {
  pending: "rchip--brand",
  pending_verification: "rchip--brand",
  open: "rchip--brand",
  verified: "rchip--success",
  assigned: "rchip--sky",
  in_progress: "rchip--brand",
  resolved: "rchip--success",
};

const STATUS_STAMP = {
  pending: "stamp--muted",
  pending_verification: "stamp--muted",
  open: "stamp--muted",
  verified: "stamp--jungle",
  assigned: "stamp--coral",
  in_progress: "stamp--accent",
  resolved: "stamp--jungle",
};

export function statusLabel(status) {
  const raw = String(status || "").trim();
  if (STATUS_LABELS[raw]) return STATUS_LABELS[raw];
  return raw.replace(/_/g, " ").toUpperCase() || "—";
}

export function statusChip(status) {
  const raw = String(status || "").trim();
  const chip = STATUS_CHIP[raw] || "";
  const stamp = STATUS_STAMP[raw] || "stamp--muted";
  return `<span class="stamp stamp--sm ${stamp} rchip ${chip}">${esc(statusLabel(raw))}</span>`;
}

export function shortId(id) {
  if (!id) return "—";
  return "#" + String(id).replace(/-/g, "").slice(0, 4).toUpperCase();
}

export function parsePhotos(value) {
  if (!value) return [];
  if (Array.isArray(value)) {
    return value.filter((u) => typeof u === "string" && u.trim() !== "");
  }
  if (typeof value !== "string") return [];
  const trimmed = value.trim();
  if (!trimmed) return [];
  try {
    const decoded = JSON.parse(trimmed);
    return Array.isArray(decoded)
      ? decoded.filter((u) => typeof u === "string" && u.trim() !== "")
      : [];
  } catch {
    return trimmed.startsWith("/") || trimmed.startsWith("http") ? [trimmed] : [];
  }
}

export function excerpt(text, max = 140) {
  const value = String(text || "").replace(/\s+/g, " ").trim();
  if (!value) return "No description provided.";
  return value.length > max ? `${value.slice(0, max - 1)}…` : value;
}

export function caseHref(id) {
  return `/cases/detail.php?id=${encodeURIComponent(id)}`;
}
