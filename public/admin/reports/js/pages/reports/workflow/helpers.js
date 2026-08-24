import { esc } from "../components/util.js";
import { shortId, titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { state } from "../state.js";

export function report(id) {
  return state.reports.find((r) => r.id === id) || null;
}

export function caseOf(reportId) {
  return state.cases.find((c) => c.report_id === reportId) || null;
}

function strStartsWith(haystack, needle) {
  return typeof haystack === "string" && haystack.indexOf(needle) === 0;
}

export function locationSub(loc, name) {
  if (loc && loc.full && typeof name === "string" && strStartsWith(loc.full, name)) {
    return loc.full.slice(name.length).replace(/^\s*,\s*/, "");
  }
  if (loc && loc.road) return loc.road;
  return "";
}

export function infoRows(id) {
  const r = report(id);
  if (!r) return "";
  const rows = [
    { label: "Case", value: shortId(r.id) },
    { label: "Reported area", value: titleCase(r.address_text) || "—" },
    { label: "Reporter", value: shortId(r.resident_id) },
    { label: "Animal description", value: r.animal_description || "—" },
    { label: "Latitude", value: r.latitude != null ? String(r.latitude) : "—" },
    { label: "Longitude", value: r.longitude != null ? String(r.longitude) : "—" },
    { label: "Validation", value: titleCase(r.validation_status) || "—" },
    { label: "Status", value: titleCase(r.status) || "—" },
    { label: "Submitted", value: new Date(r.created_at).toLocaleString() },
  ];
  return rows
    .map(
      (row) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(row.label)}</span>
      <span class="dialog-info-value">${esc(row.value)}</span>
    </div>`
    )
    .join("");
}

export function typewriter(el, text, speed = 26) {
  let i = 0;
  const cursor = '<span class="tw-cursor">|</span>';
  const step = () => {
    if (i >= text.length) {
      el.innerHTML = text;
      return;
    }
    el.innerHTML = text.slice(0, i) + cursor;
    i += 1;
    setTimeout(step, speed);
  };
  step();
}
