import { createIcons, icons } from "lucide";
import { openDrawer } from "../../../../../js/components/ui/drawer.js";
import * as api from "../../../lib/admin-data.js";
import { esc } from "../components/util.js";
import { titleCase } from "../../dashboard/helpers.js";
import { report, infoRows, typewriter, locationSub } from "./helpers.js";

export function openReportDrawer(id) {
  const r = report(id);
  if (!r) return;
  openDrawer({
    title: "Report details",
    body: `
      <div class="dialog-info">${infoRows(id)}</div>
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name" id="report-detail-location">Resolving location…</div>
          <div class="drawer-location-sub" id="report-detail-location-sub"></div>
        </div>
      </div>
      <div id="report-detail-map" class="drawer-map"></div>
      <div class="drawer-reported">
        <img class="drawer-photo" src="../public/reported.png" alt="Report">
        <span class="drawer-reported-text" id="drawer-reported-text"></span>
      </div>`,
    onMount: (bodyEl) => {
      const lat = Number(r.latitude);
      const lng = Number(r.longitude);
      const mapEl = bodyEl.querySelector("#report-detail-map");
      let marker = null;
      if (window.L && mapEl && Number.isFinite(lat) && Number.isFinite(lng)) {
        const map = window.L.map(mapEl).setView([lat, lng], 15);
        window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);
        marker = window.L.marker([lat, lng]).addTo(map).bindPopup(esc(r.address_text || "Report location"));
        setTimeout(() => map.invalidateSize(), 300);
      }
      const capEl = bodyEl.querySelector("#drawer-reported-text");
      if (capEl) typewriter(capEl, `Reported at ${r.address_text || "Mati City"}`);

      const locEl = bodyEl.querySelector("#report-detail-location");
      const subEl = bodyEl.querySelector("#report-detail-location-sub");
      const fallback = r.address_text ? titleCase(r.address_text) : "Unknown location";
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        api.reverseGeocode(lat, lng).then((loc) => {
          const specific = (loc && (loc.name || loc.road || loc.full)) || null;
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
