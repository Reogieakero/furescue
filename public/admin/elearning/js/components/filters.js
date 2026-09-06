import { Search } from "/shared/components/search/search.js";
import { Toolbar } from "/shared/components/toolbar/toolbar.js";
import { FilterTabs as TabStrip, FilterTab } from "/shared/components/filter-tabs/filter-tabs.js";
import { esc, CATEGORIES, STATUS_FILTERS } from "./util.js";
import { state } from "../state.js";
import { moduleCounts } from "./kpis.js";

function counts() {
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
  return { statusCount, catCount };
}

export function StatusTabs() {
  const { statusCount } = counts();
  return TabStrip({
    id: "elearn-status-tabs",
    children: STATUS_FILTERS.map((f) =>
      FilterTab({
        key: f.key,
        label: `${f.label} &middot; ${statusCount[f.key]}`,
        active: state.filter === f.key,
        attrs: 'type="button"',
      })
    ).join(""),
  });
}

export function FilterTabs() {
  const { catCount } = counts();
  return (
    Toolbar({
      children: Search({ id: "elearn-search", placeholder: "Search title…", value: state.query }),
    }) +
    Toolbar({
      className: "elearn-cat-toolbar",
      children: TabStrip({
        id: "elearn-category-tabs",
        children:
          FilterTab({
            label: `All categories &middot; ${catCount.all}`,
            active: state.category === "all",
            attrs: 'type="button" data-category="all"',
          }) +
          CATEGORIES.map((cat) =>
            FilterTab({
              label: `${esc(cat.label)} &middot; ${catCount[cat.key]}`,
              active: state.category === cat.key,
              attrs: `type="button" data-category="${cat.key}"`,
            })
          ).join(""),
      }),
    })
  );
}
