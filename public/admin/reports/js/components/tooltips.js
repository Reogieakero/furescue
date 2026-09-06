import { createIcons, icons } from "lucide";
import { attachTooltip, hideTooltip } from "/shared/components/tooltip/tooltip.js";
import { mountPinMap } from "/assets/js/lib/leaflet.js";
import * as api from "/assets/js/admin/admin-data.js";
import { esc } from "./util.js";
import { titleCase } from "/admin/js/helpers.js";
import { state } from "../state.js";

function buildTooltipContent(r) {
  const hasLoc = Number.isFinite(Number(r.latitude)) && Number.isFinite(Number(r.longitude));
  if (!hasLoc) {
    return `<div class="tooltip-empty"><i data-lucide="map-pin-off"></i><span>No location</span></div>`;
  }
  return `
    <div class="tooltip-map" data-lat="${r.latitude}" data-lng="${r.longitude}"></div>
    <div class="drawer-map-cap"><i data-lucide="map-pin"></i><span class="loc-name loc-loading">Resolving…</span></div>`;
}

export function hideReportMapDrawer() {
  hideTooltip();
}

export function attachReportTooltips() {
  const table = document.getElementById("report-table");
  if (!table) return;
  table.querySelectorAll("tr[data-id]").forEach((row) => {
    if (row.dataset.tipAttached) return;
    row.dataset.tipAttached = "1";
    const r = state.reports.find((x) => x.id === row.dataset.id) || null;
    if (!r) return;
    attachTooltip(row, {
      placement: "top-right",
      offset: 16,
      className: "tooltip--map-lg",
      content: () => buildTooltipContent(r),
      shouldShow: (e) => !e.target.closest(".table-actions, .action-link, [data-action]"),
      onMount: (el) => {
        const mapEl = el.querySelector(".tooltip-map");
        if (mapEl) {
          const lat = Number(mapEl.dataset.lat);
          const lng = Number(mapEl.dataset.lng);
          void mountPinMap(mapEl, lat, lng, {
            zoom: 14,
            interactive: false,
            invalidateDelay: 60,
            popup: esc(r.address_text || "Report location"),
          }).then((api) => {
            if (api?.map) el._map = api.map;
          });
        }
        const capEl = el.querySelector(".loc-name");
        const fallback = r.address_text ? titleCase(r.address_text) : "Unknown location";
        if (!Number.isFinite(Number(r.latitude)) || !Number.isFinite(Number(r.longitude))) {
          if (capEl) {
            capEl.textContent = fallback;
            capEl.classList.remove("loc-loading");
          }
        } else {
          api.reverseGeocode(Number(r.latitude), Number(r.longitude)).then((loc) => {
            const specific = (loc && (loc.name || loc.road || loc.full)) || null;
            if (capEl) {
              capEl.textContent = specific || fallback;
              capEl.classList.remove("loc-loading");
            }
          }).catch(() => {
            if (capEl) {
              capEl.textContent = fallback;
              capEl.classList.remove("loc-loading");
            }
          });
        }
        createIcons({ icons });
      },
      onDestroy: (el) => {
        if (el._map) el._map.remove();
      },
    });
  });
}
