import { KpiCard } from "/js/components/kpi-card.js";
import { state } from "../state.js";

export function moduleCounts() {
  const published = state.modules.filter((m) => m.published_status === "published").length;
  const drafts = state.modules.filter((m) => m.published_status === "draft").length;
  const categories = new Set(state.modules.map((m) => m.category).filter(Boolean)).size;
  return {
    total: state.modules.length,
    published,
    drafts,
    categories,
  };
}

export function buildKpis() {
  const c = moduleCounts();
  return [
    { icon: "book-open", value: c.total, label: "Total modules", tone: "jungle" },
    { icon: "badge-check", value: c.published, label: "Published", tone: "ink" },
    {
      icon: "file-text",
      value: c.drafts,
      label: "Drafts",
      tone: "coral",
      trend: c.drafts ? "Needs You" : "",
      trendTone: "down",
    },
    { icon: "library", value: c.categories, label: "Categories in use", tone: "amber" },
  ];
}

export function KpiTile(k) {
  return KpiCard(k);
}
