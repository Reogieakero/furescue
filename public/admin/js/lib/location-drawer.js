import { openDrawer } from "../../../js/components/ui/drawer.js";
import * as api from "./admin-data.js";
import { titleCase } from "../pages/dashboard/helpers.js";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

// Opens a drawer with a Leaflet map for the given coordinates. This is the same
// map rendering used by the report page drawer, so case detail "See location"
// shows an identical map.
export function openLocationDrawer({
  lat,
  lng,
  address,
  title = "Case location",
  mapElId = "loc-drawer-map",
} = {}) {
  const latitude = Number(lat);
  const longitude = Number(lng);
  const hasCoords = Number.isFinite(latitude) && Number.isFinite(longitude);

  if (!hasCoords) {
    openDrawer({
      title,
      body: `<div class="empty-state"><i data-lucide="map-pin-off"></i><span>No coordinates on the report.</span></div>`,
    });
    return;
  }

  openDrawer({
    title,
    body: `
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name loc-loading" id="loc-drawer-name">Resolving location…</div>
          <div class="drawer-location-sub" id="loc-drawer-sub"></div>
        </div>
      </div>
      <div id="${mapElId}" class="drawer-map"></div>`,
    onMount: (bodyEl) => {
      const mapEl = bodyEl.querySelector(`#${mapElId}`);
      const fallback = address ? titleCase(address) : "Unknown location";
      let marker = null;
      if (window.L && mapEl) {
        const map = window.L.map(mapEl).setView([latitude, longitude], 15);
        window.L
          .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: "&copy; OpenStreetMap contributors",
          })
          .addTo(map);
        marker = window.L
          .marker([latitude, longitude])
          .addTo(map)
          .bindPopup(esc(address || "Report location"));
        setTimeout(() => map.invalidateSize(), 300);
      }
      const nameEl = bodyEl.querySelector("#loc-drawer-name");
      const subEl = bodyEl.querySelector("#loc-drawer-sub");
      api
        .reverseGeocode(latitude, longitude)
        .then((loc) => {
          const specific = (loc && (loc.name || loc.road || loc.full)) || null;
          const sub = specific ? (loc.address || "") : "";
          if (nameEl) {
            nameEl.textContent = specific || fallback;
            nameEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = sub || (specific ? "" : fallback);
          if (specific && marker) marker.setPopupContent(esc(specific));
        })
        .catch(() => {
          if (nameEl) {
            nameEl.textContent = fallback;
            nameEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = "";
        });
    },
  });
}
