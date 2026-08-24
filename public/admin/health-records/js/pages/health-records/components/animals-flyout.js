import { createIcons, icons } from "lucide";
import { safe } from "/admin/js/pages/dashboard/helpers.js";
import { fetchAllAnimals, fetchHealthRecords } from "/admin/js/lib/admin-data.js";
import { esc } from "./util.js";

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

let mounted = false;
let cache = null;
let cachePromise = null;

function statusTone(label) {
  return STATUS_TONES[label] || "stamp--muted";
}

function speciesIcon(species) {
  const s = (species || "").toLowerCase();
  return s === "cat" ? "cat" : "paw-print";
}

function firstPhoto(photoUrls) {
  if (Array.isArray(photoUrls) && photoUrls.length) return photoUrls[0];
  if (typeof photoUrls === "string" && photoUrls) {
    try {
      const arr = JSON.parse(photoUrls);
      if (Array.isArray(arr) && arr.length) return arr[0];
    } catch {
      return photoUrls;
    }
  }
  return null;
}

function normalize(raw) {
  const species = (raw.species || "dog").toLowerCase() === "cat" ? "Cat" : "Dog";
  const status = STATUS_LABELS[raw.adoption_status] || "Not listed";
  return {
    id: raw.id,
    name: raw.name || "Unnamed",
    species,
    status,
    photo: firstPhoto(raw.photo_urls),
  };
}

function loadAll() {
  if (cache) return Promise.resolve(cache);
  if (cachePromise) return cachePromise;
  cachePromise = fetchAllAnimals(1000)
    .then((items) => {
      const list = Array.isArray(items) ? items : [];
      cache = list.map(normalize);
      return cache;
    })
    .catch(() => [])
    .finally(() => {
      cachePromise = null;
    });
  return cachePromise;
}

let medicalSetCache = null;

function loadMedicalSet() {
  if (medicalSetCache) return Promise.resolve(medicalSetCache);
  return fetchHealthRecords()
    .then((records) => {
      const list = Array.isArray(records) ? records : [];
      medicalSetCache = new Set(list.filter((r) => r.hasMedicalRecord).map((r) => r.animalId));
      return medicalSetCache;
    })
    .catch(() => {
      medicalSetCache = new Set();
      return medicalSetCache;
    });
}

function loadAnimalsWithMedical() {
  return Promise.all([loadAll(), loadMedicalSet()]).then(([list, medSet]) =>
    list.map((a) => ({ ...a, hasMedical: medSet.has(a.id) }))
  );
}

function animalCard(a) {
  const initial = (a.name || "?").charAt(0).toUpperCase();
  const tone = statusTone(a.status);
  const ribbon = `<span class="animal-card-ribbon ${a.hasMedical ? "animal-card-ribbon--green" : "animal-card-ribbon--red"}">${a.hasMedical ? "Medical" : "No records"}</span>`;
  const thumb = a.photo
    ? `<img src="${esc(a.photo)}" alt="${esc(a.name)}" class="animal-thumb-img">`
    : `<span class="animal-thumb-initial">${esc(initial)}</span><i data-lucide="${speciesIcon(a.species)}" class="animal-thumb-icon"></i>`;
  return `
  <button type="button" class="animal-card" data-animal="${a.id}">
    <div class="animal-thumb animal-thumb--${a.species.toLowerCase()}">
      ${thumb}
      ${ribbon}
    </div>
    <div class="animal-card-body">
      <div class="animal-card-top">
        <span class="animal-card-name">${esc(a.name)}</span>
        <span class="stamp stamp--sm ${tone}">${esc(a.status)}</span>
      </div>
    </div>
  </button>`;
}

function bodyHtml(list, query) {
  const q = (query || "").trim().toLowerCase();
  const filtered = q
    ? list.filter(
        (a) =>
          a.name.toLowerCase().includes(q) ||
          a.species.toLowerCase().includes(q) ||
          a.status.toLowerCase().includes(q)
      )
    : list;
  if (!list.length) {
    return `<div class="af-empty"><i data-lucide="paw-print"></i><span>No animals in the system yet.</span></div>`;
  }
  if (!filtered.length) {
    return `<div class="af-empty"><i data-lucide="search"></i><span>No animals match “${esc(query)}”.</span></div>`;
  }
  return `<div class="af-grid">${filtered.map(animalCard).join("")}</div>`;
}

function markup() {
  return `
  <div class="af-overlay" data-animals-flyout-overlay hidden></div>
  <aside class="af-flyout" data-animals-flyout hidden aria-hidden="true" role="dialog" aria-label="All animals">
    <header class="af-head">
      <div class="af-head-title">
        <i data-lucide="paw-print"></i>
        <div>
          <div class="af-title">All animals</div>
          <div class="af-subtitle" data-animals-count>Loading…</div>
        </div>
      </div>
      <button type="button" class="af-close" data-animals-close aria-label="Close panel"><i data-lucide="x"></i></button>
    </header>
    <div class="af-search">
      <i data-lucide="search"></i>
      <input type="text" data-animals-search placeholder="Search name, species, status…" autocomplete="off">
    </div>
    <div class="af-body" data-animals-body>
      <div class="af-loading"><span class="af-spinner"></span> Loading animals…</div>
    </div>
  </aside>`;
}

function mount() {
  if (mounted) return;
  document.body.insertAdjacentHTML("beforeend", markup());
  mounted = true;
  const flyout = document.querySelector("[data-animals-flyout]");
  const overlay = document.querySelector("[data-animals-flyout-overlay]");

  document.addEventListener("click", (e) => {
    const card = e.target.closest("[data-animal]");
    if (card) {
      const id = card.dataset.animal;
      close();
      window.location.href = `/admin/health-records/health-record.php?id=${encodeURIComponent(id)}`;
      return;
    }
    if (e.target.closest("[data-animals-open]")) {
      open();
      return;
    }
    if (e.target.closest("[data-animals-close]") || e.target.closest("[data-animals-flyout-overlay]")) {
      close();
      return;
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !flyout.hidden && document.querySelector("[data-animals-open]")) close();
  });

  document.addEventListener("input", (e) => {
    const search = e.target.closest("[data-animals-search]");
    if (!search) return;
    const body = flyout.querySelector("[data-animals-body]");
    const count = flyout.querySelector("[data-animals-count]");
    loadAnimalsWithMedical().then((list) => {
      body.innerHTML = bodyHtml(list, search.value);
      count.textContent = `${list.length} animal${list.length === 1 ? "" : "s"}`;
      createIcons({ icons });
    });
  });
}

function open() {
  mount();
  const flyout = document.querySelector("[data-animals-flyout]");
  const overlay = document.querySelector("[data-animals-flyout-overlay]");
  const body = flyout.querySelector("[data-animals-body]");
  const count = flyout.querySelector("[data-animals-count]");
  const search = flyout.querySelector("[data-animals-search]");

  overlay.hidden = false;
  flyout.hidden = false;
  flyout.setAttribute("aria-hidden", "false");
  requestAnimationFrame(() => {
    overlay.classList.add("is-visible");
    flyout.classList.add("is-open");
  });

  search.value = "";
  body.innerHTML = `<div class="af-loading"><span class="af-spinner"></span> Loading animals…</div>`;
  count.textContent = "Loading…";
  createIcons({ icons });

  loadAnimalsWithMedical().then((list) => {
    body.innerHTML = bodyHtml(list, "");
    count.textContent = `${list.length} animal${list.length === 1 ? "" : "s"}`;
    createIcons({ icons });
  });
}

function close() {
  const flyout = document.querySelector("[data-animals-flyout]");
  const overlay = document.querySelector("[data-animals-flyout-overlay]");
  if (!flyout || flyout.hidden) return;
  overlay.classList.remove("is-visible");
  flyout.classList.remove("is-open");
  flyout.setAttribute("aria-hidden", "true");
  setTimeout(() => {
    flyout.hidden = true;
    overlay.hidden = true;
  }, 260);
}

export function initAnimalsFlyout() {
  mount();
}
