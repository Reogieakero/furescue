import { createIcons, icons } from "lucide";
import { KpiCard, KpiGrid } from "/assets/js/components/kpi-card.js";
import { esc } from "/assets/js/lib/format.js";
import { animalCounts } from "../state.js";

export function buildKpis() {
  const c = animalCounts();
  return [
    {
      icon: "paw-print",
      value: c.all,
      label: "Total",
      tone: "jungle",
      filter: "all",
      desc: "Every animal currently in the shelter system.",
    },
    {
      icon: "check-circle-2",
      value: c.Available,
      label: "Available",
      tone: "sky",
      filter: "Available",
      desc: "Animals listed and ready for adoption.",
    },
    {
      icon: "hourglass",
      value: c.Pending,
      label: "Pending",
      tone: "amber",
      filter: "Pending",
      desc: "In-care animals on hold pending adoption or review.",
    },
    {
      icon: "heart",
      value: c.Adopted,
      label: "Adopted",
      tone: "ink",
      filter: "Adopted",
      desc: "Animals that have already been adopted.",
    },
    {
      icon: "alert-triangle",
      value: c.noMedical,
      label: "No medical records",
      tone: "coral",
      trend: c.noMedical ? "Needs records" : "",
      trendTone: "down",
      desc: "Animals with no medical file on record.",
    },
  ];
}

export function toKpiCardProps(k) {
  const extra = [];
  if (k.desc) extra.push(`title="${esc(k.desc)}"`);
  if (k.filter) extra.push(`data-filter="${esc(k.filter)}"`);
  return {
    icon: k.icon,
    tone: k.tone,
    label: k.label,
    value: k.value,
    trend: k.trend || "",
    trendTone: k.trendTone || "neutral",
    interactive: Boolean(k.filter),
    attrs: extra.join(" "),
  };
}

export function AnimalKpis() {
  return KpiGrid({ id: "animal-kpis", items: buildKpis().map(toKpiCardProps) });
}

export function renderAnimalKpis() {
  const el = document.getElementById("animal-kpis");
  if (!el) return;
  el.innerHTML = buildKpis().map((k) => KpiCard(toKpiCardProps(k))).join("");
  createIcons({ icons });
}
