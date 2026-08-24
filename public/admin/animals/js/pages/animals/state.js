import * as api from "/admin/js/lib/admin-data.js";
import { safe } from "/admin/js/pages/dashboard/helpers.js";

const STATUS_LABELS = {
  not_listed: "Not listed",
  available: "Available",
  pending: "Pending",
  adopted: "Adopted",
};

const STATUS_TONES = {
  Available: "stamp--accent",
  Pending: "stamp--muted",
  Adopted: "stamp--jungle",
  "Not listed": "stamp--muted",
};

function statusTone(status) {
  return STATUS_TONES[status] || "stamp--muted";
}

function speciesIcon(species) {
  const s = (species || "").toLowerCase();
  if (s === "cat") return "cat";
  if (s === "dog") return "paw-print";
  return "paw-print";
}

function firstPhoto(photoUrls) {
  if (Array.isArray(photoUrls) && photoUrls.length) return photoUrls[0];
  if (typeof photoUrls === "string" && photoUrls) {
    try {
      const arr = JSON.parse(photoUrls);
      if (Array.isArray(arr) && arr.length) return arr[0];
    } catch {
      return null;
    }
  }
  return null;
}

export function normalize(raw) {
  const speciesLabel = (raw.species || "dog").toLowerCase() === "cat" ? "Cat" : "Dog";
  const breedRaw = (raw.breed_type || "").toLowerCase();
  const breedLabel = breedRaw ? breedRaw.charAt(0).toUpperCase() + breedRaw.slice(1) : "—";
  const sexLabel = raw.sex === "female" ? "F" : raw.sex === "male" ? "M" : "—";
  const statusLabel = STATUS_LABELS[raw.adoption_status] || "Not listed";
  const location = raw.color_markings
    ? raw.color_markings
    : raw.source === "resident_listing"
    ? "Resident listing"
    : raw.source
    ? "Rescued case"
    : "—";
  return {
    id: raw.id,
    name: raw.name || "Unnamed",
    species: speciesLabel,
    breed: breedLabel,
    age: raw.age_estimate || "—",
    sex: sexLabel,
    status: statusLabel,
    barangay: location,
    intake: (raw.created_at || "").slice(0, 10),
    photo: firstPhoto(raw.photo_urls),
    model3d: raw.model_3d_url || "",
    photo360: typeof raw.photo_360_set === "string" ? raw.photo_360_set : raw.photo_360_set ? JSON.stringify(raw.photo_360_set) : "",
  };
}

export const state = {
  animals: [],
  query: "",
  filter: "all",
  selectedId: null,
};

const SELECTED_ID_KEY = "furescue.animals.selectedId";

export function setSelectedId(id) {
  state.selectedId = id || null;
  try {
    if (state.selectedId) localStorage.setItem(SELECTED_ID_KEY, state.selectedId);
    else localStorage.removeItem(SELECTED_ID_KEY);
  } catch {
    /* storage unavailable */
  }
}

export function restoreSelectedId() {
  try {
    const id = localStorage.getItem(SELECTED_ID_KEY);
    if (id && state.animals.some((a) => a.id === id)) {
      state.selectedId = id;
    } else if (id) {
      localStorage.removeItem(SELECTED_ID_KEY);
      state.selectedId = null;
    }
  } catch {
    /* storage unavailable */
  }
}

export async function loadAnimals() {
  const res = await safe(api.fetchAnimals(), { items: [], total: 0 });
  state.animals = (res.items || []).map(normalize);
  const medSet = await safe(api.fetchMedicalAnimalIds(), new Set());
  state.animals.forEach((a) => {
    a.hasMedical = medSet.has(a.id);
  });
  restoreSelectedId();
  return state.animals;
}

export function getAnimal(id) {
  return state.animals.find((a) => a.id === id) || null;
}

export function parsePhoto360(text) {
  const raw = (text || "").trim();
  if (!raw) return null;
  let parsed;
  try {
    parsed = JSON.parse(raw);
  } catch {
    throw new Error("360° photo set must be valid JSON, e.g. [\"https://…/1.jpg\"]");
  }
  if (!Array.isArray(parsed) || parsed.length === 0 || !parsed.every((u) => typeof u === "string" && u.trim() !== "")) {
    throw new Error("360° photo set must be a JSON array of image URLs.");
  }
  return parsed;
}

export async function addAnimal(data) {
  const created = await api.createAnimal({
    name: data.name || null,
    species: data.species,
    breed_type: data.breed,
    sex: data.sex,
    age_estimate: data.age || null,
    birth_date: data.birthDate || null,
    color_markings: data.color || null,
    adoption_status: data.status,
    description: data.description || null,
    photo_urls: data.photo ? [data.photo] : null,
    model_3d_url: data.model3d ? data.model3d : null,
    photo_360_set: data.photo360Urls || null,
  });
  const animal = normalize(created);
  animal.isNew = true;
  state.animals = [animal, ...state.animals];
  return animal;
}

export function animalCounts() {
  const count = (s) => state.animals.filter((a) => a.status === s).length;
  return {
    all: state.animals.length,
    Available: count("Available"),
    Pending: count("Pending"),
    Adopted: count("Adopted"),
    "Not listed": count("Not listed"),
  };
}

export function speciesBreakdown() {
  const map = {};
  for (const a of state.animals) map[a.species] = (map[a.species] || 0) + 1;
  return Object.entries(map)
    .map(([species, count]) => ({ species, count, icon: speciesIcon(species) }))
    .sort((a, b) => b.count - a.count);
}

export function visibleAnimals() {
  const q = state.query.trim().toLowerCase();
  return state.animals.filter((a) => {
    if (state.filter !== "all" && a.status !== state.filter) return false;
    if (!q) return true;
    return (
      a.name.toLowerCase().includes(q) ||
      a.species.toLowerCase().includes(q) ||
      a.breed.toLowerCase().includes(q) ||
      a.id.toLowerCase().includes(q) ||
      a.barangay.toLowerCase().includes(q)
    );
  });
}

export { speciesIcon, statusTone };
