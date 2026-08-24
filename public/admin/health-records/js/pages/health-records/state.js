import { daysUntil } from "./components/util.js";
import * as api from "/admin/js/lib/admin-data.js";
import { safe } from "/admin/js/pages/dashboard/helpers.js";

export const state = {
  filter: "all", // all | complete | partial | none | overdue | under_treatment
  query: "",
  sort: "newest", // newest | animal | next_due | health
  range: "30d", // 30d | 90d | 12mo
  species: "all", // all | dog | cat
  page: 1,
  queueExpanded: false, // attention queue shows all items when true
  records: [],
  activity: [],
};

export const PAGE_SIZE = 8;

const FILTERS = [
  { key: "all", label: "All" },
  { key: "complete", label: "Complete" },
  { key: "partial", label: "Partial" },
  { key: "none", label: "Not vaccinated" },
  { key: "overdue", label: "Overdue" },
  { key: "under_treatment", label: "Under treatment" },
];

export { FILTERS };

function matchFilter(r) {
  switch (state.filter) {
    case "complete":
      return r.vaccinationStatus === "complete";
    case "partial":
      return r.vaccinationStatus === "partial";
    case "none":
      return r.vaccinationStatus === "none";
    case "overdue":
      return daysUntil(r.nextCheckupDue) < 0;
    case "under_treatment":
      return r.healthStatus === "not_healthy";
    default:
      return true;
  }
}

function matchQuery(r) {
  const q = state.query.trim().toLowerCase();
  if (!q) return true;
  return [r.id, r.animalName, r.barangay, r.condition, r.vetName]
    .filter(Boolean)
    .join(" ")
    .toLowerCase()
    .includes(q);
}

// Records after filter tabs + search (the "visible" set that drives most panels).
export function visibleRecords() {
  return state.records.filter((r) => matchFilter(r) && matchQuery(r));
}

// Per-status / per-health counts over the FULL dataset (for tab badges).
export function recordCounts() {
  const all = state.records;
  return {
    all: all.length,
    complete: all.filter((r) => r.vaccinationStatus === "complete").length,
    partial: all.filter((r) => r.vaccinationStatus === "partial").length,
    none: all.filter((r) => r.vaccinationStatus === "none").length,
    overdue: all.filter((r) => daysUntil(r.nextCheckupDue) < 0).length,
    under_treatment: all.filter((r) => r.healthStatus === "not_healthy").length,
  };
}

export function vaccinationBreakdown() {
  const list = visibleRecords();
  return {
    total: list.length,
    complete: list.filter((r) => r.vaccinationStatus === "complete").length,
    partial: list.filter((r) => r.vaccinationStatus === "partial").length,
    none: list.filter((r) => r.vaccinationStatus === "none").length,
  };
}

// Species-scoped coverage: respects filter tabs + search but always shows the
// requested species regardless of the global species toggle.
export function vaccinationBreakdownForSpecies(species) {
  const list = state.records.filter(
    (r) => matchFilter(r) && matchQuery(r) && r.species === species
  );
  return {
    total: list.length,
    complete: list.filter((r) => r.vaccinationStatus === "complete").length,
    partial: list.filter((r) => r.vaccinationStatus === "partial").length,
    none: list.filter((r) => r.vaccinationStatus === "none").length,
  };
}

function speciesFiltered(list) {
  if (state.species === "dog") return list.filter((r) => r.species === "dog");
  if (state.species === "cat") return list.filter((r) => r.species === "cat");
  return list;
}

// Top 8 barangays by record count, stacked by health tier.
export function healthByBarangay() {
  const list = speciesFiltered(visibleRecords());
  const counts = {};
  list.forEach((r) => {
    const b = r.barangay || "Unknown";
    if (!counts[b]) counts[b] = { healthy: 0, treatment: 0, critical: 0 };
    if (r.healthStatus === "not_healthy" && r.treatmentStage === "completed") counts[b].critical += 1;
    else if (r.healthStatus === "not_healthy") counts[b].treatment += 1;
    else counts[b].healthy += 1;
  });
  const labels = Object.keys(counts)
    .sort((a, b) => {
      const sa = counts[a].healthy + counts[a].treatment + counts[a].critical;
      const sb = counts[b].healthy + counts[b].treatment + counts[b].critical;
      return sb - sa;
    })
    .slice(0, 8);
  return {
    labels,
    healthy: labels.map((l) => counts[l].healthy),
    treatment: labels.map((l) => counts[l].treatment),
    critical: labels.map((l) => counts[l].critical),
  };
}

function bucketDaily(days, reducer) {
  const now = Date.now();
  const cutoff = now - days * 86400000;
  const sliced = state.activity.filter((d) => new Date(d.date).getTime() >= cutoff);
  return reducer(sliced);
}

function weeklyBuckets(sliced) {
  const labels = [];
  const checkups = [];
  const treatments = [];
  const vaccinations = [];
  for (let i = 0; i < sliced.length; i += 7) {
    const week = sliced.slice(i, i + 7);
    labels.push("W" + (Math.floor(i / 7) + 1));
    checkups.push(week.reduce((s, d) => s + d.checkups, 0));
    treatments.push(week.reduce((s, d) => s + d.treatments, 0));
    vaccinations.push(week.reduce((s, d) => s + d.vaccinations, 0));
  }
  return { labels, checkups, treatments, vaccinations };
}

function monthlyBuckets() {
  const byMonth = {};
  state.activity.forEach((d) => {
    const ym = d.date.slice(0, 7);
    if (!byMonth[ym]) byMonth[ym] = { ym, label: d.date.slice(0, 7), checkups: 0, treatments: 0, vaccinations: 0 };
    byMonth[ym].checkups += d.checkups;
    byMonth[ym].treatments += d.treatments;
    byMonth[ym].vaccinations += d.vaccinations;
  });
  const months = Object.values(byMonth).slice(-12);
  return {
    labels: months.map((m) => m.ym.slice(2).replace("-", "/")),
    checkups: months.map((m) => m.checkups),
    treatments: months.map((m) => m.treatments),
    vaccinations: months.map((m) => m.vaccinations),
  };
}

export function activitySeries(range = state.range) {
  if (range === "90d") {
    const sliced = bucketDaily(90, (s) => s);
    return weeklyBuckets(sliced);
  }
  if (range === "12mo") return monthlyBuckets();
  const sliced = bucketDaily(30, (s) => s);
  return {
    labels: sliced.map((d) => d.date.slice(5)),
    checkups: sliced.map((d) => d.checkups),
    treatments: sliced.map((d) => d.treatments),
    vaccinations: sliced.map((d) => d.vaccinations),
  };
}

export function topConditions() {
  const list = visibleRecords();
  const counts = {};
  list.forEach((r) => {
    counts[r.condition] = (counts[r.condition] || 0) + 1;
  });
  const entries = Object.entries(counts).sort((a, b) => b[1] - a[1]).slice(0, 6);
  const max = entries.length ? entries[0][1] : 1;
  return { entries, max, total: list.length };
}

export function allAttentionCount() {
  let n = 0;
  state.records.forEach((r) => {
    if (daysUntil(r.nextCheckupDue) < 0) n += 1;
    if (r.vaccinationExpiry) {
      const exp = daysUntil(r.vaccinationExpiry);
      if (exp >= 0 && exp <= 30) n += 1;
    }
  });
  return n;
}

export function attentionItems() {
  const list = visibleRecords();
  const items = [];
  list.forEach((r) => {
    const base = {
      id: r.id,
      animalName: r.animalName,
      barangay: r.barangay,
      species: r.species,
      condition: r.condition,
    };
    const due = daysUntil(r.nextCheckupDue);
    if (due < 0) {
      items.push({
        ...base,
        kind: "checkup",
        icon: "stethoscope",
        text: "Overdue checkup",
        date: r.nextCheckupDue,
        days: due,
        tier: due <= -8 ? "critical" : "warn",
      });
    }
    if (r.vaccinationExpiry) {
      const exp = daysUntil(r.vaccinationExpiry);
      if (exp >= 0 && exp <= 30) {
        items.push({
          ...base,
          kind: "vaccine",
          icon: "syringe",
          text: "Vaccination expiring",
          date: r.vaccinationExpiry,
          days: exp,
          tier: exp <= 7 ? "warn" : "soon",
        });
      }
    }
  });
  return items.sort((a, b) => a.days - b.days);
}

export function attentionBreakdown() {
  const items = attentionItems();
  return {
    total: items.length,
    overdue: items.filter((i) => i.kind === "checkup").length,
    expiring: items.filter((i) => i.kind === "vaccine").length,
  };
}

export function avgHeartRate() {
  const list = visibleRecords().filter((r) => typeof r.heartRateBpm === "number");
  if (!list.length) return 0;
  return Math.round(list.reduce((s, r) => s + r.heartRateBpm, 0) / list.length);
}

export function sortedRecords() {
  const list = visibleRecords().slice();
  const cmp = (a, b) => {
    switch (state.sort) {
      case "animal":
        return a.animalName.localeCompare(b.animalName);
      case "next_due":
        return new Date(a.nextCheckupDue) - new Date(b.nextCheckupDue);
      case "health":
        return a.healthStatus === "not_healthy" ? -1 : 1;
      default:
        return new Date(b.updatedAt) - new Date(a.updatedAt);
    }
  };
  return list.sort(cmp);
}

export function pagedRecords() {
  const list = sortedRecords();
  const start = (state.page - 1) * PAGE_SIZE;
  return {
    rows: list.slice(start, start + PAGE_SIZE),
    total: list.length,
    page: state.page,
  };
}

export async function loadHealthRecords() {
  const records = await safe(api.fetchHealthRecords(), []);
  state.records = records;
  return state.records;
}

export async function loadHealthActivity() {
  const daily = await safe(api.fetchHealthActivity(), []);
  state.activity = daily;
  return state.activity;
}
