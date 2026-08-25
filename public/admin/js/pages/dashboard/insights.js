export function esc(v) {
  return String(v ?? "")
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;");
}

export const REPORT_TYPE_LABELS = {
  stray: "Stray Animal",
  injured: "Injured Animal",
  abuse: "Abuse/Neglect",
  other: "Others",
};

export const STATUS_META = {
  pending: { label: "Pending", cls: "dash-pill dash-pill--pending" },
  verified: { label: "Verified", cls: "dash-pill dash-pill--verified" },
  in_progress: { label: "In Progress", cls: "dash-pill dash-pill--progress" },
  resolved: { label: "Resolved", cls: "dash-pill dash-pill--resolved" },
  dismissed: { label: "Dismissed", cls: "dash-pill dash-pill--muted" },
};

export function classifyReportType(description) {
  const t = String(description || "").toLowerCase();
  if (/\b(abuse|neglect|beaten|starved|cruel)\b/.test(t)) return "abuse";
  if (/\b(injur|wound|hurt|bleed|sick|hit.?and.?run|broken)\b/.test(t)) return "injured";
  if (/\b(stray|roaming|loose|abandoned|wandering)\b/.test(t)) return "stray";
  return "other";
}

export function reportTypeLabel(description) {
  const kind = classifyReportType(description);
  const species = /\bcat\b/i.test(description || "") ? "Cat" : /\bdog\b/i.test(description || "") ? "Dog" : "Animal";
  if (kind === "stray") return `Stray ${species}`;
  if (kind === "injured") return `Injured ${species}`;
  if (kind === "abuse") return "Abuse/Neglect";
  return species === "Animal" ? "Others" : species;
}

export function displayStatus(report) {
  const cs = String(report.case_status || "");
  if (cs === "resolved") return "resolved";
  if (cs === "in_progress") return "in_progress";
  const st = String(report.status || "");
  if (st === "verified") return "verified";
  if (st === "dismissed") return "dismissed";
  return "pending";
}

export function formatReportId(id, createdAt) {
  const year = createdAt ? new Date(createdAt).getFullYear() : new Date().getFullYear();
  const s = String(id || "").replace(/-/g, "").slice(-6).toUpperCase();
  return `#RPT-${year}-${s.padStart(6, "0")}`;
}

export function formatDateTime(value) {
  if (!value) return "—";
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return "—";
  return date.toLocaleString("en-US", {
    month: "short",
    day: "numeric",
    year: "numeric",
    hour: "numeric",
    minute: "2-digit",
  });
}

export function daysUntil(value) {
  if (!value) return null;
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return null;
  const ts = new Date(date.getFullYear(), date.getMonth(), date.getDate()).getTime();
  const today = new Date();
  const start = new Date(today.getFullYear(), today.getMonth(), today.getDate()).getTime();
  return Math.round((ts - start) / 86400000);
}

export function firstPhoto(urls) {
  if (Array.isArray(urls) && urls.length) return urls[0];
  if (typeof urls === "string" && urls) {
    try {
      const arr = JSON.parse(urls);
      if (Array.isArray(arr) && arr.length) return arr[0];
    } catch {
      if (urls.startsWith("/") || urls.startsWith("http")) return urls;
    }
  }
  return "";
}

export function densitySummary(points) {
  const cells = new Map();
  for (const p of points || []) {
    const lat = Number(p.latitude);
    const lng = Number(p.longitude);
    if (!Number.isFinite(lat) || !Number.isFinite(lng)) continue;
    const key = `${lat.toFixed(2)},${lng.toFixed(2)}`;
    cells.set(key, (cells.get(key) || 0) + 1);
  }
  let high = 0;
  let moderate = 0;
  let low = 0;
  for (const n of cells.values()) {
    if (n >= 5) high += 1;
    else if (n >= 2) moderate += 1;
    else low += 1;
  }
  return { high, moderate, low };
}

export function categoryBreakdown(reports) {
  const counts = { stray: 0, injured: 0, abuse: 0, other: 0 };
  for (const r of reports || []) {
    counts[classifyReportType(r.animal_description)] += 1;
  }
  const total = Math.max(1, Object.values(counts).reduce((s, n) => s + n, 0));
  return Object.entries(REPORT_TYPE_LABELS).map(([key, label]) => ({
    key,
    label,
    count: counts[key],
    pct: Math.round((counts[key] / total) * 100),
  }));
}

export function statusPill(key) {
  const meta = STATUS_META[key] || STATUS_META.pending;
  return `<span class="${meta.cls}">${esc(meta.label)}</span>`;
}

export function healthPill(key, label) {
  const cls =
    key === "attention"
      ? "dash-pill dash-pill--pending"
      : key === "treatment"
        ? "dash-pill dash-pill--care"
        : key === "recovered"
          ? "dash-pill dash-pill--progress"
          : "dash-pill dash-pill--resolved";
  return `<span class="${cls}">${esc(label)}</span>`;
}

export function healthOverview(records) {
  let healthy = 0;
  let attention = 0;
  let treatment = 0;
  let recovered = 0;
  let upToDate = 0;
  let dueSoon = 0;
  let overdue = 0;
  let none = 0;
  const reminderRaw = [];
  const checkups = [];

  for (const r of records || []) {
    const stage = String(r.treatmentStage || r.treatment_stage || "none");
    const health = String(r.healthStatus || r.health_status || "healthy");
    let healthLabel = "Healthy";
    let healthKey = "healthy";
    if (stage === "ongoing") {
      treatment += 1;
      healthLabel = "Under Treatment";
      healthKey = "treatment";
    } else if (stage === "completed") {
      recovered += 1;
      healthLabel = "Recovered";
      healthKey = "recovered";
    } else if (health === "not_healthy") {
      attention += 1;
      healthLabel = "Needs Attention";
      healthKey = "attention";
    } else {
      healthy += 1;
    }

    const vax = String(r.vaccinationStatus || r.vaccination_status || "none");
    const vaxDays = daysUntil(r.vaccinationExpiry || r.vaccination_expiry);
    if (vax === "none" || vax === "") none += 1;
    else if (vaxDays !== null && vaxDays < 0) overdue += 1;
    else if (vaxDays !== null && vaxDays <= 14) dueSoon += 1;
    else if (vax === "complete") upToDate += 1;
    else dueSoon += 1;

    const dueDays = daysUntil(r.nextCheckupDue || r.next_checkup_due);
    if (dueDays !== null && dueDays >= 0 && dueDays <= 21) {
      reminderRaw.push({
        label: "Check-up",
        detail: `Due in ${dueDays} day${dueDays === 1 ? "" : "s"}`,
        days: dueDays,
      });
    }
    if (vaxDays !== null && vaxDays >= 0 && vaxDays <= 21) {
      reminderRaw.push({
        label: "Vaccine booster",
        detail: `Due in ${vaxDays} day${vaxDays === 1 ? "" : "s"}`,
        days: vaxDays,
      });
    }

    const last = r.lastCheckupDate || r.last_checkup_date;
    if (last) {
      checkups.push({
        name: r.animalName || r.name || "Unnamed",
        meta: `${r.species || "Animal"} · ${formatDateTime(last)}`,
        photo: firstPhoto(r.photo_urls || r.photoUrls),
        status: healthLabel,
        statusKey: healthKey,
        animalId: r.animalId || r.id || "",
        sort: new Date(last).getTime() || 0,
      });
    }
  }

  reminderRaw.sort((a, b) => a.days - b.days);
  const grouped = new Map();
  for (const item of reminderRaw) {
    const key = `${item.label}|${item.days}`;
    const prev = grouped.get(key);
    if (prev) prev.count += 1;
    else grouped.set(key, { ...item, count: 1 });
  }

  const total = Math.max(1, (records || []).length);
  return {
    summary: [
      { key: "healthy", label: "Healthy", count: healthy, icon: "heart" },
      { key: "attention", label: "Needs Attention", count: attention, icon: "alert-triangle" },
      { key: "treatment", label: "Under Treatment", count: treatment, icon: "syringe" },
      { key: "recovered", label: "Recovered", count: recovered, icon: "badge-check" },
    ],
    totalAnimals: (records || []).length,
    vax: [
      { key: "up", label: "Up to date", count: upToDate, pct: Math.round((upToDate / total) * 100) },
      { key: "soon", label: "Due Soon", count: dueSoon, pct: Math.round((dueSoon / total) * 100) },
      { key: "over", label: "Overdue", count: overdue, pct: Math.round((overdue / total) * 100) },
      { key: "none", label: "Not Yet Vaccinated", count: none, pct: Math.round((none / total) * 100) },
    ],
    reminders: [...grouped.values()].slice(0, 3),
    checkups: checkups.sort((a, b) => b.sort - a.sort).slice(0, 4),
  };
}

export function trendLabel(n) {
  if (!n) return { text: "No change today", tone: "neutral" };
  if (n > 0) return { text: `+${n} Today`, tone: "up" };
  return { text: `${n} Today`, tone: "down" };
}

export function hslToken(name) {
  const raw = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
  return raw ? `hsl(${raw})` : "";
}

export function cssVar(name) {
  return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}
