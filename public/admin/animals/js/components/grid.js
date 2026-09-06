import { createIcons, icons } from "lucide";
import { Search } from "/shared/components/search/search.js";
import { Toolbar } from "/shared/components/toolbar/toolbar.js";
import { FilterTabs as TabStrip, FilterTab } from "/shared/components/filter-tabs/filter-tabs.js";
import { EmptyState } from "/shared/components/empty-state/empty-state.js";
import { state, visibleAnimals, speciesIcon, statusTone, getAnimal } from "../state.js";
import { esc } from "./util.js";

function AnimalCard(a) {
  const tone = statusTone(a.status);
  const active = state.selectedId === a.id ? " is-selected" : "";
  const initial = (a.name || "?").charAt(0).toUpperCase();
  return `
  <button type="button" class="animal-card${active}" data-animal="${a.id}">
    <div class="animal-thumb animal-thumb--${a.species.toLowerCase()}">
      ${a.photo ? `<img src="${esc(a.photo)}" alt="${esc(a.name)}" class="animal-thumb-img">` : `<span class="animal-thumb-initial">${esc(initial)}</span><i data-lucide="${speciesIcon(a.species)}" class="animal-thumb-icon"></i>`}
      ${a.isNew ? `<span class="stamp stamp--sm stamp--jungle animal-card-new">New</span>` : ""}
      <span class="animal-card-ribbon ${a.hasMedical ? "animal-card-ribbon--green" : "animal-card-ribbon--red"}">${a.hasMedical ? "Medical" : "No records"}</span>
    </div>
    <div class="animal-card-body">
      <div class="animal-card-top">
        <span class="animal-card-name">${esc(a.name)}</span>
        <span class="stamp stamp--sm ${tone}">${esc(a.status)}</span>
      </div>
    </div>
  </button>`;
}

export function AnimalGrid() {
  const list = visibleAnimals();
  const grid = list.length
    ? list.map(AnimalCard).join("")
    : EmptyState({ icon: "paw-print", text: "No animals match your filters.", className: "animal-empty" });
  return `
  <div class="panel animal-panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="paw-print"></i>
        <h2 class="panel-title">Animals <span class="animal-count" id="animal-total-badge">${state.animals.length}</span></h2>
      </div>
    </div>
    ${Toolbar({
      className: "animal-toolbar",
      children: `
      ${TabStrip({ id: "animal-filter-tabs", children: FilterTabs() })}
      ${Search({ id: "animal-search", placeholder: "Search name, species, breed, ID…", value: state.query, className: "animal-search" })}`,
    })}
    <div class="panel-body">
      <div id="animal-grid" class="animal-grid">${grid}</div>
      <div id="animal-selected-store" hidden>${state.selectedId || ""}</div>
    </div>
  </div>`;
}

export const ANIMAL_FILTERS = [
  { key: "all", label: "All" },
  { key: "Available", label: "Available" },
  { key: "Pending", label: "Pending" },
  { key: "Adopted", label: "Adopted" },
  { key: "Not listed", label: "Not listed" },
];

export function FilterTabs() {
  const c = (() => {
    const count = (s) => state.animals.filter((a) => a.status === s).length;
    return { all: state.animals.length, Available: count("Available"), Pending: count("Pending"), Adopted: count("Adopted"), "Not listed": count("Not listed") };
  })();
  return ANIMAL_FILTERS.map((f) =>
    FilterTab({ key: f.key, label: `${f.label} &middot; ${c[f.key]}`, active: state.filter === f.key })
  ).join("");
}

export function renderAnimalGrid() {
  const grid = document.getElementById("animal-grid");
  if (!grid) return;
  const list = visibleAnimals();
  grid.innerHTML = list.length
    ? list.map(AnimalCard).join("")
    : EmptyState({ icon: "paw-print", text: "No animals match your filters.", className: "animal-empty" });
  const badge = document.getElementById("animal-total-badge");
  if (badge) badge.textContent = String(state.animals.length);
  const tabs = document.getElementById("animal-filter-tabs");
  if (tabs) tabs.innerHTML = FilterTabs();
  createIcons({ icons });
}

export function renderSelection() {
  const store = document.getElementById("animal-selected-store");
  if (store) store.textContent = state.selectedId || "";
  document.querySelectorAll(".animal-card").forEach((el) => {
    el.classList.toggle("is-selected", el.dataset.animal === state.selectedId);
  });
}

export { getAnimal };
