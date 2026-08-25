import { createIcons, icons } from "lucide";
import { initSelect } from "../../../../js/components/ui/select.js";
import { downloadCsv, datedCsvName } from "../../../../js/lib/csv.js";
import { classifyReportType, displayStatus, cssVar, hslToken, categoryBreakdown, densitySummary } from "./insights.js";
import { categoryLegendHtml, densityRowsHtml } from "./components/gis.js";

const MATI_CENTER = [6.95, 126.2];
const MATI_BOUNDS = [
  [6.85, 126.1],
  [7.08, 126.4],
];

let mapApi = null;
let allPoints = [];
let mode = "heatmap";
const filters = { start: "", end: "", type: "", status: "" };

function hex(name, fallback) {
  return cssVar(name) || fallback;
}

function heatGradient() {
  return {
    0.2: hex("--heat-low", "#3d7432"),
    0.5: hex("--heat-mid", "#fbc02d"),
    1: hex("--heat-high", "#d32f2f"),
  };
}

function markerColor(point) {
  const st = displayStatus(point);
  if (st === "resolved" || st === "verified") return hslToken("--primary") || "#3d7432";
  if (st === "in_progress") return hslToken("--status-progress") || "#3b82f6";
  if (st === "dismissed") return hslToken("--stamp") || "#94a3b8";
  return hslToken("--status-pending") || "#f59e0b";
}

export function filterPoints(points, next = filters) {
  return (points || []).filter((p) => {
    if (p.latitude == null || p.longitude == null) return false;
    if (next.type && classifyReportType(p.animal_description) !== next.type) return false;
    if (next.status && displayStatus(p) !== next.status) return false;
    if (next.start || next.end) {
      const t = p.created_at ? new Date(p.created_at).getTime() : NaN;
      if (Number.isNaN(t)) return false;
      if (next.start && t < new Date(`${next.start}T00:00:00`).getTime()) return false;
      if (next.end && t > new Date(`${next.end}T23:59:59`).getTime()) return false;
    }
    return true;
  });
}

function syncSidebar(points) {
  const densityEl = document.getElementById("gis-density");
  if (densityEl) densityEl.innerHTML = densityRowsHtml(densitySummary(points));
  const legendEl = document.getElementById("gis-cat-legend");
  if (legendEl) legendEl.innerHTML = categoryLegendHtml(categoryBreakdown(points));
  const updated = document.getElementById("gis-updated");
  if (updated) {
    const stamps = points.map((p) => p.created_at).filter(Boolean).map((v) => new Date(v).getTime()).filter((n) => !Number.isNaN(n));
    updated.textContent = stamps.length
      ? new Date(Math.max(...stamps)).toLocaleString("en-US", {
          month: "short",
          day: "numeric",
          year: "numeric",
          hour: "numeric",
          minute: "2-digit",
        })
      : "—";
  }
}

function renderLayers() {
  if (!mapApi) return;
  const points = filterPoints(allPoints);
  const heatPoints = points.map((p) => [Number(p.latitude), Number(p.longitude), 1]);
  mapApi.heat.setLatLngs(heatPoints);
  mapApi.markers.clearLayers();
  points.forEach((p) => {
    const marker = window.L.circleMarker([Number(p.latitude), Number(p.longitude)], {
      radius: 7,
      color: "#fff",
      weight: 1,
      fillColor: markerColor(p),
      fillOpacity: 0.9,
    });
    marker.bindPopup(`<strong>${(p.address_text || "Mati City").replace(/[<>]/g, "")}</strong><br>${(p.animal_description || "").slice(0, 80)}`);
    mapApi.markers.addLayer(marker);
  });
  if (mode === "heatmap") {
    if (!mapApi.map.hasLayer(mapApi.heat)) mapApi.heat.addTo(mapApi.map);
    if (mapApi.map.hasLayer(mapApi.markers)) mapApi.map.removeLayer(mapApi.markers);
  } else {
    if (mapApi.map.hasLayer(mapApi.heat)) mapApi.map.removeLayer(mapApi.heat);
    if (!mapApi.map.hasLayer(mapApi.markers)) mapApi.markers.addTo(mapApi.map);
  }
  syncSidebar(points);
}

export function initCaseDensityMap(points) {
  const el = document.getElementById("case-density-map");
  allPoints = points || [];
  if (!el || !window.L || !window.L.heatLayer) {
    if (el && !window.L) console.warn("Leaflet not loaded; heatmap skipped.");
    if (el && window.L && !window.L.heatLayer) console.warn("leaflet.heat not loaded; heatmap skipped.");
    return null;
  }

  const map = L.map(el, {
    center: MATI_CENTER,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: MATI_BOUNDS,
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  L.tileLayer("https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png", {
    maxZoom: 19,
    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> &copy; <a href="https://carto.com/attributions">CARTO</a>',
  }).addTo(map);

  const heat = L.heatLayer([], { radius: 28, blur: 18, maxZoom: 17, minOpacity: 0.35, gradient: heatGradient() });
  const markers = L.layerGroup();
  mapApi = { map, heat, markers };
  renderLayers();

  window.setTimeout(() => map.invalidateSize(), 0);
  return mapApi;
}

export function bindGisActions() {
  const toggle = document.getElementById("gis-filters-toggle");
  const filtersEl = document.getElementById("gis-filters");
  if (toggle && filtersEl) {
    toggle.addEventListener("click", () => filtersEl.classList.toggle("is-open"));
  }

  document.querySelectorAll("[data-map-mode]").forEach((btn) => {
    btn.addEventListener("click", () => {
      mode = btn.dataset.mapMode || "heatmap";
      document.querySelectorAll("[data-map-mode]").forEach((b) => b.classList.toggle("is-active", b === btn));
      renderLayers();
    });
  });

  initSelect(document.getElementById("gis-type"), {
    "gis-type": (value) => {
      filters.type = value || "";
    },
  });
  initSelect(document.getElementById("gis-status"), {
    "gis-status": (value) => {
      filters.status = value || "";
    },
  });

  document.getElementById("gis-apply")?.addEventListener("click", () => {
    filters.start = document.getElementById("gis-date-start")?.value || "";
    filters.end = document.getElementById("gis-date-end")?.value || "";
    renderLayers();
  });

  document.getElementById("gis-reset")?.addEventListener("click", () => {
    filters.start = filters.end = filters.type = filters.status = "";
    const start = document.getElementById("gis-date-start");
    const end = document.getElementById("gis-date-end");
    if (start) start.value = "";
    if (end) end.value = "";
    renderLayers();
  });

  document.getElementById("gis-export")?.addEventListener("click", () => {
    const rows = filterPoints(allPoints).map((p) => [
      p.id,
      p.animal_description || "",
      p.address_text || "",
      p.status || "",
      p.case_status || "",
      p.latitude,
      p.longitude,
      p.created_at || "",
    ]);
    downloadCsv(datedCsvName("heatmap"), ["ID", "Description", "Location", "Status", "Case status", "Lat", "Lng", "Created"], rows);
  });

  createIcons({ icons });
}
