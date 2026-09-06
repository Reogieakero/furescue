import { openDrawer } from "/shared/components/drawer/drawer.js";
import { mountPinMap } from "/assets/js/lib/leaflet.js";
import * as api from "/assets/js/admin/admin-data.js";
import { titleCase } from "/admin/js/helpers.js";

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
      let pendingPopup = null;
      if (mapEl) {
        void mountPinMap(mapEl, latitude, longitude, { popup: esc(address || "Report location") }).then((mapApi) => {
          marker = mapApi?.marker || null;
          if (pendingPopup && marker) marker.setPopupContent(pendingPopup);
        });
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
          if (specific) {
            pendingPopup = esc(specific);
            if (marker) marker.setPopupContent(pendingPopup);
          }
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
