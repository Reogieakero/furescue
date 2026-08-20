import * as api from "../../../lib/admin-data.js";
import { createIcons, icons } from "lucide";
import { openDrawer } from "../../../../../js/components/ui/drawer.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { state } from "../state.js";
import { getCase, enrich, esc } from "./util.js";
import { titleCase } from "../../dashboard/helpers.js";

function locationSub(loc, name) {
  if (loc && loc.full && typeof name === "string" && loc.full.indexOf(name) === 0) {
    return loc.full.slice(name.length).replace(/^\s*,\s*/, "");
  }
  if (loc && loc.road) return loc.road;
  return "";
}

function infoRows(c) {
  const rows = [
    { label: "Case", value: c.shortId },
    { label: "Status", value: c.status },
    { label: "Barangay", value: c.brgy !== "—" ? titleCase(c.brgy) : "—" },
    { label: "Animal", value: c.animal },
    { label: "Rescuer", value: c.rescuer ? c.rescuer.full_name : "Unassigned" },
    { label: "Created", value: c.createdAt ? new Date(c.createdAt).toLocaleString() : "—" },
    { label: "Updated", value: c.updatedAt ? new Date(c.updatedAt).toLocaleString() : "—" },
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

export function openCaseDrawer(caseId) {
  const raw = getCase(caseId);
  if (!raw) return;
  const c = enrich(raw);
  const resolved = c.statusRaw === "resolved";
  const actions = [
    !resolved
      ? Button({
          text: "Reassign",
          variant: "outline",
          size: "sm",
          icon: "refresh-cw",
          attrs: `data-drawer-action="reassign" data-case="${c.id}" data-report="${c.report ? c.report.id : ""}"`,
        })
      : "",
    !resolved
      ? Button({
          text: "Resolve",
          variant: "default",
          size: "sm",
          icon: "check-circle-2",
          attrs: `data-drawer-action="resolve" data-case="${c.id}"`,
        })
      : "",
  ].join("");

  openDrawer({
    title: `Case ${c.shortId}`,
    body: `
      <div class="dialog-info">${infoRows(c)}</div>
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name" id="case-detail-location">Resolving location…</div>
          <div class="drawer-location-sub" id="case-detail-location-sub"></div>
        </div>
      </div>
      <div id="case-detail-map" class="drawer-map"></div>
      ${actions ? `<div class="drawer-foot-actions">${actions}</div>` : ""}`,
    onMount: (bodyEl) => {
      const lat = c.lat;
      const lng = c.lng;
      const mapEl = bodyEl.querySelector("#case-detail-map");
      let marker = null;
      if (window.L && mapEl && lat != null && lng != null) {
        const map = window.L.map(mapEl).setView([lat, lng], 15);
        window.L
          .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
          })
          .addTo(map);
        marker = window.L.marker([lat, lng]).addTo(map).bindPopup(esc(c.brgy || "Case location"));
        setTimeout(() => map.invalidateSize(), 300);
      }
      const locEl = bodyEl.querySelector("#case-detail-location");
      const subEl = bodyEl.querySelector("#case-detail-location-sub");
      const fallback = c.brgy !== "—" ? titleCase(c.brgy) : "Unknown location";
      if (lat != null && lng != null) {
        api.reverseGeocode(lat, lng).then((loc) => {
          const specific = loc && (loc.name || loc.road || loc.full);
          const sub = specific ? locationSub(loc, specific) : "";
          if (locEl) {
            locEl.textContent = specific || fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = sub || (specific ? "" : fallback);
          if (specific && marker) marker.setPopupContent(esc(specific));
        }).catch(() => {
          if (locEl) {
            locEl.textContent = fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = "";
        });
      } else if (locEl) {
        locEl.textContent = fallback;
        locEl.classList.remove("loc-loading");
      }
    },
  });
}
