import { state } from "../state.js";
import { createIcons, icons } from "lucide";
import { esc, enrich } from "./util.js";
import { Select, initSelect } from "../../../../../js/components/ui/select.js";

const MATI_CENTER = [6.95, 126.2];
const MATI_BOUNDS = [
  [6.85, 126.1],
  [7.08, 126.4],
];

const INTENSITY_PRESETS = {
  low: { radius: 18, blur: 12, minOpacity: 0.35 },
  medium: { radius: 25, blur: 15, minOpacity: 0.4 },
  high: { radius: 35, blur: 22, minOpacity: 0.5 },
};

const STATUS_COLORS = {
  open: "hsl(211, 71%, 38%)",
  assigned: "hsl(211, 71%, 38%)",
  in_progress: "hsl(199, 74%, 53%)",
  resolved: "hsl(215, 16%, 47%)",
};

let caseMapMode = "pins";
let currentIntensity = "medium";
let caseMapInstance = null;
let caseHeat = null;

export function renderCaseMap() {
  const el = document.getElementById("case-map");
  if (!el) return;
  if (caseMapInstance) {
    caseMapInstance.remove();
    caseMapInstance = null;
    caseHeat = null;
  }
  if (!window.L) return;

  const map = window.L.map(el, {
    center: MATI_CENTER,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: MATI_BOUNDS,
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  window.L
    .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    })
    .addTo(map);

  const all = state.cases.map(enrich).filter((c) => c.lat != null && c.lng != null);

  if (caseMapMode === "heatmap" && window.L.heatLayer) {
    const heatPoints = (state.heatmap || [])
      .filter((p) => p.latitude != null && p.longitude != null)
      .map((p) => [Number(p.latitude), Number(p.longitude), 1]);
    caseHeat = window.L.heatLayer(heatPoints, { radius: 25, blur: 15, maxZoom: 17 }).addTo(map);
    caseHeat.setOptions(INTENSITY_PRESETS[currentIntensity] || INTENSITY_PRESETS.medium);

    const count = document.getElementById("case-map-count");
    if (count) count.textContent = String(heatPoints.length);
  } else {
    all.forEach((c) => {
      const color = STATUS_COLORS[c.statusRaw] || STATUS_COLORS.open;
      const marker = window.L.circleMarker([c.lat, c.lng], {
        radius: 9,
        color: "#fff",
        weight: 2,
        fillColor: color,
        fillOpacity: 1,
      }).addTo(map);
      marker.bindPopup(`<strong>${esc(c.shortId)}</strong> &middot; ${esc(c.status)}<br>${esc(c.brgy)}`);
      marker.on("click", () => {
        window.location.href = "case-detail.html?id=" + encodeURIComponent(c.id);
      });
    });

    const count = document.getElementById("case-map-count");
    if (count) count.textContent = String(all.length);
  }

  window.setTimeout(() => map.invalidateSize(), 0);
  caseMapInstance = map;
}

export function initCaseMapMode() {
  const wrap = document.getElementById("case-map-toggle");
  if (!wrap || wrap.dataset.modeInit) return;
  wrap.dataset.modeInit = "1";
  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-map-mode]");
    if (!btn) return;
    const mode = btn.dataset.mapMode;
    if (mode === caseMapMode) return;
    caseMapMode = mode;
    wrap.querySelectorAll("[data-map-mode]").forEach((b) => b.classList.toggle("is-active", b === btn));
    renderCaseMap();
    const label = document.getElementById("case-map-foot-label");
    if (label) {
      label.textContent =
        caseMapMode === "heatmap" ? "heat points · Density of reported cases" : "pinned cases · Click a pin for details";
    }
  });

  initSelect(document.getElementById("case-map-intensity"), {
    "case-map-intensity": (value) => {
      currentIntensity = value;
      if (caseHeat) caseHeat.setOptions(INTENSITY_PRESETS[value] || INTENSITY_PRESETS.medium);
    },
  });

  const panel = document.getElementById("case-map-panel");
  const expandBtn = document.getElementById("case-map-expand");
  if (panel && expandBtn && !expandBtn.dataset.mapExpandInit) {
    expandBtn.dataset.mapExpandInit = "1";
    expandBtn.addEventListener("click", () => {
      const expanded = !panel.classList.contains("is-expanded");
      panel.classList.toggle("is-expanded", expanded);
      expandBtn.setAttribute("aria-label", expanded ? "Collapse map" : "Expand map");
      expandBtn.title = expanded ? "Collapse map" : "Expand map";
      expandBtn.querySelector(".lucide")?.remove();
      expandBtn.insertAdjacentHTML("afterbegin", `<i data-lucide="${expanded ? "minimize" : "maximize"}"></i>`);
      createIcons({ icons });
      window.setTimeout(() => {
        const m = caseMapInstance;
        if (!m) return;
        if (expanded) m.fitBounds(MATI_BOUNDS, { animate: true });
        else m.setView(MATI_CENTER, 13);
        m.invalidateSize();
      }, 350);
    });
  }
}

export function MapPanel() {
  return `
  <div class="panel case-map-panel" id="case-map-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="map"></i>
        <h2 class="panel-title">Case map &middot; City of Mati</h2>
      </div>
      <div class="map-tools">
        <span class="map-label">Heat intensity</span>
        ${Select({
          id: "case-map-intensity",
          value: currentIntensity,
          placeholder: "Heat intensity",
          options: [
            { value: "low", label: "Low" },
            { value: "medium", label: "Medium" },
            { value: "high", label: "High" },
          ],
        })}
        <button type="button" id="case-map-expand" class="map-expand" aria-label="Expand map" title="Expand map"><i data-lucide="maximize"></i></button>
        <div class="map-toggle" id="case-map-toggle" role="group" aria-label="Map display mode">
          <button type="button" class="map-toggle-btn${caseMapMode === "pins" ? " is-active" : ""}" data-map-mode="pins"><i data-lucide="map-pin"></i> Pins</button>
          <button type="button" class="map-toggle-btn${caseMapMode === "heatmap" ? " is-active" : ""}" data-map-mode="heatmap"><i data-lucide="flame"></i> Heatmap</button>
        </div>
      </div>
    </div>
    <div id="case-map" class="map-canvas map-canvas--leaflet case-map"></div>
    <div class="map-foot"><span id="case-map-count">0</span> <span id="case-map-foot-label">${caseMapMode === "heatmap" ? "heat points · Density of reported cases" : "pinned cases · Click a pin for details"}</span></div>
  </div>`;
}
