import { getAccessToken, API_BASE_URL } from "../../js/lib/api.js";
import { toast } from "../../js/components/ui/toast.js";

const el = (id) => document.getElementById(id);

const LEAFLET_SCRIPTS = [
  "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.js",
  "https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.js",
];
const LEAFLET_STYLES = [
  "https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css",
  "https://cdn.jsdelivr.net/npm/leaflet@1.9.4/dist/leaflet.css",
];

function tokenHsl(name, fallback) {
  const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return raw ? `hsl(${raw})` : fallback;
}

function loadScript(src) {
  return new Promise((resolve, reject) => {
    const s = document.createElement("script");
    s.src = src;
    s.async = true;
    s.onload = () => resolve();
    s.onerror = () => reject(new Error(`Failed to load ${src}`));
    document.head.appendChild(s);
  });
}

function ensureLeafletCss() {
  if ([...document.querySelectorAll("link[rel='stylesheet']")].some((l) => /leaflet/i.test(l.href))) {
    return;
  }
  const link = document.createElement("link");
  link.rel = "stylesheet";
  link.href = LEAFLET_STYLES[0];
  document.head.appendChild(link);
}

export async function ensureLeaflet() {
  if (window.L) return true;
  ensureLeafletCss();
  for (const src of LEAFLET_SCRIPTS) {
    try {
      await loadScript(src);
      if (window.L) return true;
    } catch {
      /* try next CDN */
    }
  }
  return false;
}

export function clampLatLng(lat, lng, bounds) {
  return {
    lat: Math.min(bounds.latMax, Math.max(bounds.latMin, Number(lat))),
    lng: Math.min(bounds.lngMax, Math.max(bounds.lngMin, Number(lng))),
  };
}

export function isInsideBounds(lat, lng, bounds) {
  return (
    lat >= bounds.latMin &&
    lat <= bounds.latMax &&
    lng >= bounds.lngMin &&
    lng <= bounds.lngMax
  );
}

function showMapStatus(message) {
  const status = el("report-map-status");
  if (!status) return;
  status.textContent = message || "";
  status.classList.toggle("hidden", !message);
}

function enableManualCoords() {
  for (const id of ["latitude", "longitude"]) {
    const input = el(id);
    if (!input) continue;
    input.removeAttribute("readonly");
    input.classList.remove("cursor-default");
  }
}

export function getLatLng() {
  const latRaw = String(el("latitude")?.value ?? "").trim();
  const lngRaw = String(el("longitude")?.value ?? "").trim();
  if (latRaw === "" || lngRaw === "") return null;
  const lat = Number(latRaw);
  const lng = Number(lngRaw);
  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return null;
  return { lat, lng };
}

export function writeLatLng(lat, lng) {
  if (el("latitude")) el("latitude").value = Number(lat).toFixed(6);
  if (el("longitude")) el("longitude").value = Number(lng).toFixed(6);
}

let geocodeTimer = null;
let reverseAbort = null;
let reversePaused = false;

export function pauseReverseGeocode() {
  reversePaused = true;
  clearTimeout(geocodeTimer);
  reverseAbort?.abort();
}

export async function reverseGeocode(lat, lng) {
  if (reversePaused) return;
  const address = el("address_text");
  if (!address || address.dataset.touched) return;
  reverseAbort?.abort();
  reverseAbort = new AbortController();
  try {
    const headers = { Accept: "application/json" };
    const token = getAccessToken();
    if (token) headers.Authorization = `Bearer ${token}`;
    const res = await fetch(
      `${API_BASE_URL}/geo/reverse?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`,
      { headers, signal: reverseAbort.signal }
    );
    if (!res.ok) return;
    const data = await res.json();
    const result = data && data.data ? data.data : data;
    const label = result && (result.full || result.name);
    if (address && !address.dataset.touched && label) {
      address.value = String(label);
    }
  } catch (err) {
    if (err && err.name === "AbortError") return;
    /* reverse geocode is best-effort and must not block report submit */
  }
}

export function queueReverseGeocode(lat, lng) {
  if (reversePaused) return;
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(() => reverseGeocode(lat, lng), 400);
}

export function initAddressTouched() {
  const address = el("address_text");
  if (!address) return;
  address.addEventListener("input", () => {
    address.dataset.touched = "1";
  });
}

export async function initMap(bounds, { onPin } = {}) {
  const container = el("report-map");
  const ready = await ensureLeaflet();
  if (!container || !ready || !window.L) {
    showMapStatus("Map failed to load. Enter latitude and longitude inside Mati City instead.");
    enableManualCoords();
    return null;
  }
  showMapStatus("");

  const center = [
    (bounds.latMin + bounds.latMax) / 2,
    (bounds.lngMin + bounds.lngMax) / 2,
  ];
  const matiBounds = [
    [bounds.latMin, bounds.lngMin],
    [bounds.latMax, bounds.lngMax],
  ];

  const map = window.L.map(container, {
    center,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: window.L.latLngBounds(matiBounds).pad(0.02),
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  window.L
    .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution:
        '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    })
    .addTo(map);

  window.L.rectangle(matiBounds, {
    color: tokenHsl("--jungle2", "hsl(199 74% 53%)"),
    weight: 1,
    fill: false,
    dashArray: "4 6",
  }).addTo(map);

  let marker = null;
  const place = (latlng) => {
    const next = clampLatLng(latlng.lat, latlng.lng, bounds);
    if (!marker) {
      marker = window.L.marker(next, { draggable: true }).addTo(map);
      marker.on("dragend", () => {
        const pos = marker.getLatLng();
        const c = clampLatLng(pos.lat, pos.lng, bounds);
        marker.setLatLng(c);
        writeLatLng(c.lat, c.lng);
        queueReverseGeocode(c.lat, c.lng);
        if (onPin) onPin(c);
      });
    } else {
      marker.setLatLng(next);
    }
    writeLatLng(next.lat, next.lng);
    if (onPin) onPin(next);
    return next;
  };

  map.on("click", (e) => {
    const pinned = place(e.latlng);
    queueReverseGeocode(pinned.lat, pinned.lng);
  });

  setTimeout(() => map.invalidateSize(), 0);
  window.addEventListener("resize", () => map.invalidateSize());
  container._furescueMap = map;

  return { map, place };
}

export function initGeolocate(bounds, mapApi) {
  const btn = el("use-my-location");
  if (!btn) return;
  btn.addEventListener("click", () => {
    if (!navigator.geolocation) {
      toast("Geolocation is not supported by this browser.", { type: "error" });
      return;
    }
    btn.disabled = true;
    navigator.geolocation.getCurrentPosition(
      (pos) => {
        btn.disabled = false;
        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;
        if (!isInsideBounds(lat, lng, bounds)) {
          toast("You appear to be outside Mati City — drop the pin on the map instead.", {
            type: "error",
          });
          return;
        }
        if (mapApi?.map) mapApi.map.setView([lat, lng], 16);
        if (mapApi?.place) {
          mapApi.place({ lat, lng });
        } else {
          writeLatLng(lat, lng);
        }
        queueReverseGeocode(lat, lng);
      },
      () => {
        btn.disabled = false;
        toast("Could not get your location. Check browser permissions.", { type: "error" });
      },
      { enableHighAccuracy: true, timeout: 10000 }
    );
  });
}
