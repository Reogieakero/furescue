import { createIcons, icons } from "lucide";
import { initSelect } from "../../../../js/components/ui/select.js";

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

export function initCaseDensityMap(points) {
  const el = document.getElementById("case-density-map");
  if (!el || !window.L || !window.L.heatLayer) return null;

  const map = L.map(el, {
    center: MATI_CENTER,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: MATI_BOUNDS,
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
    maxZoom: 19,
    attribution:
      '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
  }).addTo(map);

  const heatPoints = (points || [])
    .filter((p) => p.latitude != null && p.longitude != null)
    .map((p) => [Number(p.latitude), Number(p.longitude), 1]);

  const heat = L.heatLayer(heatPoints, { radius: 25, blur: 15, maxZoom: 17 }).addTo(map);

  initSelect(document.getElementById("heat-intensity"), {
    "heat-intensity": (value) => {
      heat.setOptions(INTENSITY_PRESETS[value] || INTENSITY_PRESETS.medium);
    },
  });

  const count = document.getElementById("heat-count");
  if (count) count.textContent = String(heatPoints.length);

  const panel = document.getElementById("case-density-panel");
  const expandBtn = document.getElementById("map-expand");
  if (panel && expandBtn) {
    let expanded = false;
    expandBtn.addEventListener("click", () => {
      expanded = !expanded;
      panel.classList.toggle("is-expanded", expanded);
      expandBtn.setAttribute("aria-label", expanded ? "Collapse map" : "Expand map");
      expandBtn.title = expanded ? "Collapse map" : "Expand map";
      expandBtn.querySelector(".lucide")?.remove();
      expandBtn.insertAdjacentHTML("afterbegin", `<i data-lucide="${expanded ? "minimize" : "maximize"}"></i>`);
      createIcons({ icons });
      window.setTimeout(() => {
        if (expanded) map.fitBounds(MATI_BOUNDS, { animate: true });
        else map.setView(MATI_CENTER, 13);
        map.invalidateSize();
      }, 350);
    });
  }

  window.setTimeout(() => map.invalidateSize(), 0);

  return { map, heat };
}
