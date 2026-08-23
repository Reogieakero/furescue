import { createIcons, icons } from "lucide";
import { apiFetchFull, redirectToLogin } from "../../js/lib/api.js";
import { bootstrapPageAuth } from "../../js/lib/page-auth.js";
import { initResidentShell } from "../../js/components/resident-shell.js";
import { toast } from "../../js/components/ui/toast.js";
import { esc } from "../../js/lib/format.js";
import { openModelViewer, open360Viewer } from "./3d-viewer.js";

const PER_PAGE = 12;

const state = {
  page: 1,
  total: 0,
  shown: 0,
  loading: false,
  failed: false,
};

const registry = new Map();
const el = (id) => document.getElementById(id);

function parseUrls(value) {
  if (!value) return [];
  let parsed = value;
  if (typeof parsed === "string") {
    try {
      parsed = JSON.parse(parsed);
    } catch {
      return [];
    }
  }
  return Array.isArray(parsed) ? parsed.filter((u) => typeof u === "string" && u.trim() !== "") : [];
}

function label(value) {
  const raw = String(value || "").trim();
  if (!raw) return "";
  return raw.charAt(0).toUpperCase() + raw.slice(1);
}

function cardHtml(animal) {
  const photo = parseUrls(animal.photo_urls)[0] || null;
  const name = animal.name && String(animal.name).trim() !== "" ? String(animal.name).trim() : "Unnamed";
  const sex = animal.sex === "female" ? "♀ Female" : animal.sex === "male" ? "♂ Male" : "";
  const metaParts = [label(animal.breed_type), animal.age_estimate ? String(animal.age_estimate) : ""]
    .filter(Boolean)
    .join(" · ");
  const spin = parseUrls(animal.photo_360_set);

  return `
  <article class="racard" data-id="${esc(animal.id)}" tabindex="0" aria-label="View ${esc(name)}">
    <div class="racard-thumb">
      ${
        photo
          ? `<img src="${esc(photo)}" alt="Photo of ${esc(name)}" loading="lazy">`
          : `<div class="racard-placeholder"><i data-lucide="${
              animal.species === "cat" ? "cat" : "dog"
            }"></i></div>`
      }
      <span class="racard-flag"><span class="rchip rchip--success">Available</span></span>
    </div>
    <div class="racard-body">
      <div class="racard-top">
        <span class="racard-name">${esc(name)}</span>
        ${sex ? `<span class="racard-sex">${esc(sex)}</span>` : ""}
      </div>
      ${metaParts ? `<p class="racard-meta">${esc(metaParts)}</p>` : ""}
      <div class="racard-tags">
        <span class="rchip">${esc(label(animal.species) || "Pet")}</span>
        ${
          animal.model_3d_url
            ? `<button type="button" class="rchip rchip--sky" data-view-3d title="View 3D model"><i data-lucide="rotate-3d"></i>3D</button>`
            : ""
        }
        ${
          spin.length
            ? `<button type="button" class="rchip rchip--sky" data-view-360 title="View 360° photos"><i data-lucide="refresh-cw"></i>360°</button>`
            : ""
        }
      </div>
    </div>
  </article>`;
}

function renderCards(items, append) {
  remember(items);
  const grid = el("gallery-grid");
  if (append) {
    grid.insertAdjacentHTML("beforeend", items.map(cardHtml).join(""));
  } else {
    grid.innerHTML = items.map(cardHtml).join("");
  }
  createIcons({ icons });
}

function remember(items) {
  items.forEach((a) => {
    if (a && a.id) registry.set(a.id, a);
  });
}

function updateMeta() {
  el("gallery-count").hidden = state.failed;
  el("gallery-count").textContent = `Showing ${state.shown} of ${state.total} available ${
    state.total === 1 ? "animal" : "animals"
  }`;
  el("gallery-empty").hidden = state.shown > 0 || state.failed;
  el("load-more").hidden = !state.failed && (state.shown >= state.total || state.shown === 0);
}

async function load({ append = false } = {}) {
  if (state.loading) return;
  state.loading = true;

  const params = new URLSearchParams({
    adoption_status: "available",
    per_page: String(PER_PAGE),
    page: String(append ? state.page + 1 : 1),
  });
  const q = (el("filter-q").value || "").trim();
  if (q) params.set("q", q);
  for (const [id, key] of [
    ["filter-species", "species"],
    ["filter-sex", "sex"],
    ["filter-breed", "breed_type"],
  ]) {
    const v = el(id).value;
    if (v) params.set(key, v);
  }

  try {
    const payload = await apiFetchFull(`/animals?${params}`);
    const items = Array.isArray(payload.data) ? payload.data : [];
    const total = Number((payload.meta || {}).total) || items.length;
    state.page = Number((payload.meta || {}).page) || (append ? state.page + 1 : 1);
    state.total = total;
    state.failed = false;
    renderCards(items, append);
    state.shown = append ? state.shown + items.length : items.length;
  } catch (err) {
    if (err && err.status === 401) {
      redirectToLogin();
      return;
    }
    state.failed = true;
    toast(err.message || "Could not load the gallery.", { type: "error" });
  } finally {
    state.loading = false;
    updateMeta();
  }
}

const refresh = () => load({ append: false });

function initEvents() {
  const grid = el("gallery-grid");

  grid.addEventListener("click", (event) => {
    const card = event.target.closest(".racard");
    if (!card) return;
    const animal = registry.get(card.dataset.id);
    if (event.target.closest("[data-view-3d]") && animal && animal.model_3d_url) {
      openModelViewer(animal);
      return;
    }
    if (event.target.closest("[data-view-360]") && animal) {
      open360Viewer(animal);
      return;
    }
    window.location.href = `/animals/detail.php?id=${encodeURIComponent(card.dataset.id)}`;
  });

  grid.addEventListener("keydown", (event) => {
    if (event.key !== "Enter") return;
    const card = event.target.closest(".racard");
    if (!card || event.target !== card) return;
    window.location.href = `/animals/detail.php?id=${encodeURIComponent(card.dataset.id)}`;
  });

  el("load-more").addEventListener("click", () => load({ append: true }));

  let debounce = null;
  el("filter-q").addEventListener("input", () => {
    clearTimeout(debounce);
    debounce = setTimeout(refresh, 300);
  });
  ["filter-species", "filter-sex", "filter-breed"].forEach((id) => {
    el(id).addEventListener("change", refresh);
  });
}

document.addEventListener("DOMContentLoaded", () => {
  const user = bootstrapPageAuth();
  if (!user) redirectToLogin();
  initResidentShell();
  initEvents();
  refresh();
});
