import { createIcons, icons } from "lucide";
import { requireAuth, apiFetchFull, apiUpload, redirectToLogin } from "../../js/lib/api.js";
import { toast } from "../../js/components/ui/toast.js";

const MAX_FILES = 8;
const MAX_FILE_SIZE = 10 * 1024 * 1024;

const state = window.__PAGE_STATE__ || {};
const bounds = state.bounds || { latMin: 6.89, latMax: 7.01, lngMin: 126.13, lngMax: 126.27 };
const MATI_CENTER = [
  (bounds.latMin + bounds.latMax) / 2,
  (bounds.lngMin + bounds.lngMax) / 2,
];
const MATI_BOUNDS = [
  [bounds.latMin, bounds.lngMin],
  [bounds.latMax, bounds.lngMax],
];

let map = null;
let marker = null;
let selectedFiles = [];

const el = (id) => document.getElementById(id);

function showError(key, message) {
  const slot = document.querySelector(`[data-error-for="${key}"]`);
  if (!slot) return;
  if (message !== undefined) {
    const span = slot.querySelector("span");
    if (span) span.textContent = message;
  }
  slot.classList.remove("hidden");
  slot.classList.add("flex");
}

function clearError(key) {
  const slot = document.querySelector(`[data-error-for="${key}"]`);
  if (!slot) return;
  slot.classList.add("hidden");
  slot.classList.remove("flex");
}

function setMarker(latlng) {
  if (!map) return;
  if (!marker) {
    marker = window.L.marker(latlng, { draggable: true }).addTo(map);
    marker.on("dragend", () => onMarkerMoved(marker.getLatLng()));
  } else {
    marker.setLatLng(latlng);
  }
  el("latitude").value = Number(latlng.lat).toFixed(6);
  el("longitude").value = Number(latlng.lng).toFixed(6);
  clearError("location");
}

function onMarkerMoved(latlng) {
  setMarker(latlng);
  reverseGeocode(latlng.lat, latlng.lng).catch(() => {});
}

let geocodeTimer = null;
async function reverseGeocode(lat, lng) {
  const address = el("address_text");
  try {
    const data = await apiFetchFull(
      `/geo/reverse?lat=${encodeURIComponent(lat)}&lng=${encodeURIComponent(lng)}`
    );
    const result = data && data.data ? data.data : data;
    const label = result && (result.full || result.name);
    if (address && !address.dataset.touched && label) {
      address.value = String(label);
    }
  } catch {
    /* reverse geocode is best-effort */
  }
}

function queueReverseGeocode(lat, lng) {
  clearTimeout(geocodeTimer);
  geocodeTimer = setTimeout(() => reverseGeocode(lat, lng), 400);
}

function initMap() {
  const container = el("report-map");
  if (!container || !window.L) return;

  map = window.L.map(container, {
    center: MATI_CENTER,
    zoom: 13,
    minZoom: 11,
    maxZoom: 18,
    maxBounds: window.L.latLngBounds(MATI_BOUNDS).pad(0.02),
    maxBoundsViscosity: 1,
    scrollWheelZoom: false,
  });

  window.L
    .tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
      maxZoom: 19,
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors',
    })
    .addTo(map);

  window.L.rectangle(MATI_BOUNDS, {
    color: "hsl(199, 74%, 53%)",
    weight: 1,
    fill: false,
    dashArray: "4 6",
  }).addTo(map);

  map.on("click", (e) => {
    setMarker(e.latlng);
    queueReverseGeocode(e.latlng.lat, e.latlng.lng);
  });

  setTimeout(() => map.invalidateSize(), 0);
}

function initGeolocate() {
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
        const inside =
          lat >= bounds.latMin && lat <= bounds.latMax &&
          lng >= bounds.lngMin && lng <= bounds.lngMax;
        if (!inside) {
          toast("You appear to be outside Mati City — drop the pin on the map instead.", { type: "error" });
          return;
        }
        map.setView([lat, lng], 16);
        setMarker({ lat, lng });
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

function renderSelectedFiles() {
  const list = el("photo-list");
  if (!list) return;
  list.innerHTML = "";
  selectedFiles.forEach((file, i) => {
    const li = document.createElement("li");
    li.className =
      "flex items-center gap-2 rounded-md border border-input bg-background px-2.5 py-1.5 text-xs";
    const name = document.createElement("span");
    name.className = "min-w-0 flex-1 truncate font-semibold";
    name.textContent = file.name;
    const size = document.createElement("span");
    size.className = "shrink-0 tabular-nums text-muted-foreground";
    size.textContent = `${(file.size / 1024 / 1024).toFixed(1)} MB`;
    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "inline-flex h-5 w-5 shrink-0 items-center justify-center rounded text-muted-foreground transition-colors hover:bg-destructive/10 hover:text-destructive";
    remove.setAttribute("aria-label", `Remove ${file.name}`);
    remove.innerHTML = '<i data-lucide="x" class="h-3.5 w-3.5"></i>';
    remove.addEventListener("click", () => {
      selectedFiles.splice(i, 1);
      renderSelectedFiles();
    });
    li.append(name, size, remove);
    list.appendChild(li);
  });
  createIcons({ icons });
}

function initPhotoInput() {
  const input = el("report-photos");
  if (!input) return;
  input.addEventListener("change", () => {
    for (const file of Array.from(input.files || [])) {
      if (selectedFiles.length >= MAX_FILES) {
        toast(`Up to ${MAX_FILES} files allowed.`, { type: "error" });
        break;
      }
      if (file.size > MAX_FILE_SIZE) {
        toast(`"${file.name}" is larger than 10 MB.`, { type: "error" });
        continue;
      }
      if (selectedFiles.some((f) => f.name === file.name && f.size === file.size)) continue;
      selectedFiles.push(file);
    }
    input.value = "";
    renderSelectedFiles();
  });
}

function initDescCounter() {
  const field = el("animal_description");
  const counter = el("desc-count");
  if (!field || !counter) return;
  const update = () => {
    counter.textContent = `${field.value.length} / 2000`;
  };
  field.addEventListener("input", update);
  update();
}

function initAddressTouched() {
  const address = el("address_text");
  if (!address) return;
  address.addEventListener("input", () => {
    address.dataset.touched = "1";
  });
}

async function uploadPhotos(reportId) {
  if (selectedFiles.length === 0) return;
  const formData = new FormData();
  for (const file of selectedFiles) {
    formData.append("photos[]", file);
  }
  await apiUpload(`/reports/${encodeURIComponent(reportId)}/media`, formData);
}

async function handleSubmit(e) {
  e.preventDefault();

  const description = el("animal_description").value.trim();
  const latitude = el("latitude").value.trim();
  const longitude = el("longitude").value.trim();
  const addressText = el("address_text").value.trim();
  let valid = true;

  clearError("animal_description");
  clearError("location");

  if (!description) {
    showError("animal_description");
    valid = false;
  }
  if (!latitude || !longitude) {
    showError("location");
    valid = false;
  }
  if (!valid) return;

  const submit = el("report-submit");
  submit.disabled = true;
  submit.querySelector("span").textContent = "Submitting…";

  try {
    const payload = await apiFetchFull("/reports", {
      method: "POST",
      body: {
        animal_description: description,
        latitude: Number(latitude),
        longitude: Number(longitude),
        ...(addressText ? { address_text: addressText } : {}),
      },
    });

    const report = payload && payload.data && payload.data.report;
    if (!report || !report.id) throw new Error("Unexpected server response.");
    const reportId = report.id;
    let flaggedDuplicate =
      report.validation_status === "flagged_duplicate" || report.duplicate_of_report_id != null;

    try {
      await uploadPhotos(reportId);
    } catch (uploadErr) {
      toast(`Report created, but some photos failed to upload: ${uploadErr.message}`, { type: "error", duration: 6000 });
    }

    sessionStorage.setItem(
      "furescue_flash",
      JSON.stringify({
        type: flaggedDuplicate ? "info" : "success",
        message: flaggedDuplicate
          ? "A similar report exists nearby — yours was flagged for review."
          : "Report submitted! Our team will verify it shortly.",
      })
    );
    window.location.href = "/reports/";
  } catch (err) {
    submit.disabled = false;
    submit.querySelector("span").textContent = "Submit report";
    if (err.status === 401) {
      toast("Your session expired. Please sign in again.", { type: "error" });
      setTimeout(redirectToLogin, 1200);
      return;
    }
    if (err.code === "OUT_OF_BOUNDS") {
      showError("location", "Location is outside Mati City.");
      return;
    }
    toast(err.message || "Could not submit the report.", { type: "error" });
  }
}

requireAuth();

document.addEventListener("DOMContentLoaded", () => {
  createIcons({ icons });
  initMap();
  initGeolocate();
  initPhotoInput();
  initDescCounter();
  initAddressTouched();
  const form = el("report-form");
  if (form) form.addEventListener("submit", handleSubmit);
});
