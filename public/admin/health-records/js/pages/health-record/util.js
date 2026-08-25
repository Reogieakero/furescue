import { Select } from "/js/components/ui/select.js";
import { esc } from "../health-records/components/util.js";
import { API_BASE_URL } from "/js/lib/api.js";

export function resolveDocUrl(raw) {
  if (!raw) return "";
  if (/^https?:\/\//i.test(raw)) return raw;
  const base = API_BASE_URL.replace(/\/api\/v1\/?$/, "");
  return raw.startsWith("/") ? `${base}${raw}` : `${base}/${raw}`;
}

export const TONE = {
  green: "tint-green text-green",
  blue: "tint-blue text-blue",
  purple: "tint-purple text-purple",
  orange: "tint-orange text-orange",
  red: "tint-red text-red",
  yellow: "tint-yellow text-yellow",
};

export const ICON = {
  green: "heart",
  blue: "shield",
  purple: "link",
  orange: "scissors",
  red: "activity",
  yellow: "clock",
};

export const SPECIES_VACCINES = {
  dog: ["DHPP / DAPP", "Rabies", "Leptospirosis", "Bordetella", "Canine Influenza", "Lyme"],
  cat: ["FVRCP", "Rabies", "FeLV (Feline Leukemia Virus)", "Chlamydia felis", "Bordetella"],
};

export function vaccineOptionList(species) {
  const key = String(species || "").toLowerCase();
  return (SPECIES_VACCINES[key] || []).map((v) => ({ value: v, label: v }));
}

export const STATUS_OPTIONS = [
  { value: "complete", label: "Complete" },
  { value: "partial", label: "Partial" },
  { value: "none", label: "None" },
];

export const VITAL_OPTIONS = [
  { value: "Weight", label: "Weight", unit: "kg" },
  { value: "Body Temperature", label: "Body Temperature", unit: "°C" },
  { value: "Heart Rate", label: "Heart Rate", unit: "bpm" },
];

export const ADOPTION_OPTIONS = [
  { value: "not_listed", label: "Not listed" },
  { value: "available", label: "Available" },
  { value: "pending", label: "Pending" },
  { value: "adopted", label: "Adopted" },
];

export const DEWORMING_OPTIONS = [
  { value: "unknown", label: "Unknown" },
  { value: "up_to_date", label: "Up to date" },
  { value: "overdue", label: "Overdue" },
];

export const NEUTERED_OPTIONS = [
  { value: "unknown", label: "Unknown" },
  { value: "yes", label: "Yes" },
  { value: "no", label: "No" },
];

export const VAX_STATUS_TONE = {
  none: "red",
  partial: "yellow",
  complete: "green",
};

export function titleCase(v) {
  return String(v || "")
    .replace(/_/g, " ")
    .replace(/\b\w/g, (c) => c.toUpperCase());
}

export function cap(v) {
  const s = String(v ?? "");
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

export function vaxStatusPill(status) {
  const tone = VAX_STATUS_TONE[status] || "gray";
  const cls = tone === "gray" ? "pill" : `pill pill--${tone}`;
  return `<span class="${cls}">${esc(status ? titleCase(status) : "Unknown")}</span>`;
}

// shadcn Select is JS-managed (not a native <select>), so its value isn't read by
// FormData. We mirror the chosen value into a hidden input with the same `name`
// via an initSelect handler wired in paint().
export function selectField({ id, name, options, value, placeholder }) {
  return `${Select({ id, options, value: value || "", placeholder })}<input type="hidden" name="${name}" id="${id}-value" value="${esc(value || "")}">`;
}

export function chip(tone, text) {
  return `<span class="pill pill--${tone}">${esc(text)}</span>`;
}

export function emptyState(msg, icon = "inbox") {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${esc(msg)}</span></div>`;
}

export function toneFor(field, value) {
  switch (field) {
    case "healthStatus":
      return value === "not_healthy" ? "red" : "green";
    case "vaccinationStatus":
      return value === "complete" ? "blue" : value === "partial" ? "yellow" : "red";
    case "deworming":
      return value === "up_to_date" ? "green" : value === "overdue" ? "red" : "yellow";
    case "neutered":
      return value === "yes" ? "green" : value === "no" ? "orange" : "yellow";
    default:
      return "blue";
  }
}

export function interpretOverview(o) {
  if (!o) return "";
  const parts = [];

  if (o.healthStatus) {
    parts.push(
      o.healthStatus === "not_healthy"
        ? "This animal is currently flagged as not healthy and needs prompt veterinary attention."
        : "This animal is in good general health."
    );
  }

  if (o.vaccinationStatus) {
    if (o.vaccinationStatus === "complete") parts.push("Vaccinations are complete and up to date.");
    else if (o.vaccinationStatus === "partial")
      parts.push("Vaccinations are only partially done; remaining doses should be scheduled.");
    else parts.push("Vaccinations are not up to date and should be prioritised.");
  }

  if (o.deworming) {
    if (o.deworming === "up_to_date") parts.push("Deworming is up to date.");
    else if (o.deworming === "overdue") parts.push("Deworming is overdue and should be repeated soon.");
    else parts.push("Deworming status is pending.");
  }

  if (o.neutered) {
    if (o.neutered === "yes") parts.push("The animal is neutered.");
    else if (o.neutered === "no") parts.push("The animal is not neutered; consider scheduling the procedure.");
    else parts.push("Neutering status is unknown.");
  }

  if (!parts.length) return "";
  return parts.join(" ");
}
