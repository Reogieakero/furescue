import { AppShell } from "../../../layout/app-shell.js";
import * as api from "../../../lib/admin-data.js";

let proofMap = null;

export function renderLocation(caseData) {
  const lat = Number(caseData.latitude);
  const lng = Number(caseData.longitude);
  const hasCoords = caseData.latitude != null && caseData.longitude != null;
  const locName =
    hasCoords && caseData.report
      ? caseData.report.address_text || "Unknown location"
      : "Unknown location";

  if (!hasCoords) {
    return AppShell({
      title: "Case location",
      children: `<div class="empty-state"><i data-lucide="map-pin-off"></i><span>No coordinates on the report.</span></div>`,
    });
  }

  return AppShell({
    title: "Case location",
    children: `
      <div id="cd-drawer-map" class="drawer-map"></div>
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name loc-loading" id="cd-loc-name">Resolving location…</div>
          <div class="drawer-location-sub" id="cd-loc-sub"></div>
        </div>
      </div>`,
    onMount: () => {
      const el = document.getElementById("cd-drawer-map");
      proofMap = L.map(el).setView([lat, lng], 15);
      L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
        attribution: "&copy; OpenStreetMap contributors",
      }).addTo(proofMap);
      L.marker([lat, lng]).addTo(proofMap).bindPopup(locName);
      api.reverseGeocode(lat, lng).then((res) => {
        const name = res && (res.display_name || res.name);
        const sub = res && res.address ? res.address : "";
        const nameEl = document.getElementById("cd-loc-name");
        const subEl = document.getElementById("cd-loc-sub");
        if (nameEl) {
          nameEl.textContent = name || locName;
          nameEl.classList.remove("loc-loading");
        }
        if (subEl) subEl.textContent = sub || "";
      }).catch(() => {
        const nameEl = document.getElementById("cd-loc-name");
        if (nameEl) {
          nameEl.textContent = locName;
          nameEl.classList.remove("loc-loading");
        }
      });
    },
  });
}
