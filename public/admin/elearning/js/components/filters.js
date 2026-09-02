import { esc, CATEGORIES, STATUS_FILTERS } from "./util.js";
import { state } from "../state.js";
import { moduleCounts } from "./kpis.js";

export function FilterTabs() {
  const c = moduleCounts();
  const statusCount = {
    all: c.total,
    draft: c.drafts,
    published: c.published,
  };
  const catCount = { all: state.modules.length };
  CATEGORIES.forEach((cat) => {
    catCount[cat.key] = state.modules.filter((m) => m.category === cat.key).length;
  });
  return `
  <div class="report-toolbar">
    <div class="q-tabs" id="elearn-status-tabs">
      ${STATUS_FILTERS.map(
        (f) =>
          `<button type="button" data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${statusCount[f.key]}</button>`
      ).join("")}
    </div>
    <div class="report-search">
      <i data-lucide="search"></i>
      <input id="elearn-search" type="text" placeholder="Search title…" value="${esc(state.query)}">
    </div>
  </div>
  <div class="report-toolbar elearn-cat-toolbar">
    <div class="q-tabs" id="elearn-category-tabs">
      <button type="button" data-category="all" class="q-btn${state.category === "all" ? " is-active" : ""}">All categories &middot; ${catCount.all}</button>
      ${CATEGORIES.map(
        (cat) =>
          `<button type="button" data-category="${cat.key}" class="q-btn${state.category === cat.key ? " is-active" : ""}">${esc(cat.label)} &middot; ${catCount[cat.key]}</button>`
      ).join("")}
    </div>
  </div>`;
}
