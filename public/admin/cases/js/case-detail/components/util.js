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

// JSON columns (photo_urls / resolution_photos) arrive from the API as JSON
// strings; normalise them into an array of URL strings.
export function photos(value) {
  if (!value) return [];
  if (typeof value === "string") {
    try {
      value = JSON.parse(value);
    } catch {
      return [];
    }
  }
  if (!Array.isArray(value)) return [];
  return value
    .map((f) => (typeof f === "string" ? f : (f && f.url) || ""))
    .filter(Boolean);
}
