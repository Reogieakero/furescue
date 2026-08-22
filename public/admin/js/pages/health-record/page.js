import { createIcons, icons } from "lucide";
import { AppShell, initShell } from "../../layout/app-shell.js";
import { Button } from "../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../js/components/ui/select.js";
import { Input } from "../../../../js/components/ui/input.js";
import { DatePicker, initDatePicker } from "../../../../js/components/ui/date-picker.js";
import { Spinner } from "../../../../js/components/ui/spinner.js";
import { openDrawer, closeDrawer } from "../../../../js/components/ui/drawer.js";
import { toast } from "../../../../js/components/ui/toast.js";
import { esc } from "../health-records/components/util.js";
import { fetchAnimalHealthRecord, upsertAnimalVaccinations, addAnimalVital, upsertAnimalMedical, uploadAnimalDocument, updateAnimalDocument, deleteAnimalDocument, createAdoptionListing } from "../../lib/admin-data.js";
import { API_BASE_URL } from "../../../../js/lib/api.js";

function resolveDocUrl(raw) {
  if (!raw) return "";
  if (/^https?:\/\//i.test(raw)) return raw;
  const base = API_BASE_URL.replace(/\/api\/v1\/?$/, "");
  return raw.startsWith("/") ? `${base}${raw}` : `${base}/${raw}`;
}

const TONE = {
  green: "tint-green text-green",
  blue: "tint-blue text-blue",
  purple: "tint-purple text-purple",
  orange: "tint-orange text-orange",
  red: "tint-red text-red",
  yellow: "tint-yellow text-yellow",
};

const ICON = {
  green: "heart",
  blue: "shield",
  purple: "link",
  orange: "scissors",
  red: "activity",
  yellow: "clock",
};

const SPECIES_VACCINES = {
  dog: ["DHPP / DAPP", "Rabies", "Leptospirosis", "Bordetella", "Canine Influenza", "Lyme"],
  cat: ["FVRCP", "Rabies", "FeLV (Feline Leukemia Virus)", "Chlamydia felis", "Bordetella"],
};

function vaccineOptionList(species) {
  return (SPECIES_VACCINES[species] || []).map((v) => ({ value: v, label: v }));
}

const STATUS_OPTIONS = [
  { value: "complete", label: "Complete" },
  { value: "partial", label: "Partial" },
  { value: "none", label: "None" },
];

const VITAL_OPTIONS = [
  { value: "Weight", label: "Weight", unit: "kg" },
  { value: "Body Temperature", label: "Body Temperature", unit: "°C" },
  { value: "Heart Rate", label: "Heart Rate", unit: "bpm" },
];

// Map research-based vaccination status to an existing pill tone.
const VAX_STATUS_TONE = {
  none: "red",
  partial: "yellow",
  complete: "green",
};

function vaxStatusPill(status) {
  const tone = VAX_STATUS_TONE[status] || "gray";
  const cls = tone === "gray" ? "pill" : `pill pill--${tone}`;
  return `<span class="${cls}">${esc(status ? titleCase(status) : "Unknown")}</span>`;
}

// shadcn Select is JS-managed (not a native <select>), so its value isn't read by
// FormData. We mirror the chosen value into a hidden input with the same `name`
// via an initSelect handler wired in paint().
function selectField({ id, name, options, value, placeholder }) {
  return `${Select({ id, options, value: value || "", placeholder })}<input type="hidden" name="${name}" id="${id}-value" value="${esc(value || "")}">`;
}


function chip(tone, text) {
  return `<span class="pill pill--${tone}">${esc(text)}</span>`;
}

function emptyState(msg, icon = "inbox") {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${esc(msg)}</span></div>`;
}

function PageHead(hasRecord, editing, mode) {
  const actions = hasRecord
    ? `${Button({ text: editing ? "Done" : "Edit", variant: "outline", attrs: `data-action="edit-record"` })}
       ${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}`
    : `${Button({ text: editing ? "Add" : "Add Health Record", variant: "default", attrs: `data-action="add-record"` })}`;
  return `
  <div class="page-head">
    <div>
      <a href="health-records.html" class="cd-back"><i data-lucide="chevron-left"></i> Back to health records</a>
    </div>
    <div class="page-head-actions">
      ${actions}
    </div>
  </div>`;
}

function ProfilePanel(r) {
  const photo = r.photoUrl
    ? `<img src="${esc(r.photoUrl)}" alt="${esc(r.name)}" class="hr-photo">`
    : `<span class="hr-photo hr-photo--ph">${esc((r.name || "?").charAt(0).toUpperCase())}</span>`;

  const rows = [
    ["paw-print", "Species", r.species],
    ["dog", "Breed", r.breedType],
    ["venus-mars", "Sex", r.sex],
    ["calendar", "Age", r.ageEstimate],
    ["calendar-check", "Date of birth", r.birthDate],
    ["map-pin", "Location", r.barangay],
    ["heart-handshake", "Status", r.adoptionStatus],
  ].filter((row) => row[2] != null && row[2] !== "");

  return `
  <section class="panel hr-profile-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="dog"></i><h3 class="panel-title">Animal Profile</h3></div>
    </div>
    <div class="panel-body hr-profile-body">
      <div class="hr-profile">
        ${photo}
        <div class="hr-profile-info">
          <h2 class="hr-profile-name">${esc(cap(r.name))}</h2>
          <div class="hr-info-card">
            <ul class="hr-detail-list">
            ${rows
              .map(
                ([ic, label, val]) => `
              <li class="hr-detail-row">
                <i data-lucide="${ic}" class="hr-detail-ic"></i>
                <div class="hr-detail-text">
                  <span class="hr-detail-label">${esc(label)}</span>
                  <span class="hr-detail-value">${esc(cap(String(val)))}</span>
                </div>
              </li>`
              )
              .join("")}
            </ul>
          </div>
        </div>
      </div>
    </div>
  </section>`;
}

function toneFor(field, value) {
  switch (field) {
    case "healthStatus":
      return value === "not_healthy" ? "red" : "green";
    case "vaccinationStatus":
      return value === "complete" ? "blue" : value === "partial" ? "yellow" : "red";
    case "deworming":
      return value === "up_to_date" ? "green" : value === "overdue" ? "red" : "yellow";
    case "neutered":
      return value === "yes" ? "green" : value === "no" ? "orange" : "yellow";
    default:
      return "blue";
  }
}

function titleCase(v) {
  return String(v || "").replace(/_/g, " ").replace(/\b\w/g, (c) => c.toUpperCase());
}

function cap(v) {
  const s = String(v ?? "");
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

function interpretOverview(o) {
  if (!o) return "";
  const parts = [];

  if (o.healthStatus) {
    parts.push(
      o.healthStatus === "not_healthy"
        ? "This animal is currently flagged as not healthy and needs prompt veterinary attention."
        : "This animal is in good general health."
    );
  }

  if (o.vaccinationStatus) {
    if (o.vaccinationStatus === "complete") parts.push("Vaccinations are complete and up to date.");
    else if (o.vaccinationStatus === "partial") parts.push("Vaccinations are only partially done; remaining doses should be scheduled.");
    else parts.push("Vaccinations are not up to date and should be prioritised.");
  }

  if (o.deworming) {
    if (o.deworming === "up_to_date") parts.push("Deworming is up to date.");
    else if (o.deworming === "overdue") parts.push("Deworming is overdue and should be repeated soon.");
    else parts.push("Deworming status is pending.");
  }

  if (o.neutered) {
    if (o.neutered === "yes") parts.push("The animal is neutered.");
    else if (o.neutered === "no") parts.push("The animal is not neutered; consider scheduling the procedure.");
    else parts.push("Neutering status is unknown.");
  }

  if (!parts.length) return "";
  return parts.join(" ");
}

function OverviewPanel(o, vaccinations = []) {
  if (!o) return "";
  const sub = (field, label, value, extra = "") => {
    const tone = toneFor(field, value);
    return `
    <div class="hr-subcard">
      <span class="tint-circle ${TONE[tone].split(" ")[0]}"><i data-lucide="${ICON[tone] || "circle"}"></i></span>
      <div>
        <div class="hr-subcard-label">${esc(label)}</div>
        <div class="hr-subcard-value">${chip(tone, titleCase(value))}</div>
        ${extra}
      </div>
    </div>`;
  };

  const list = (vaccinations || [])
    .map((v) => ({ name: (v.vaccine || "").trim(), date: v.dateGiven || v.date || null, status: v.status || null }))
    .filter((v) => v.name)
    .sort((a, b) => String(b.date || "").localeCompare(String(a.date || "")));
  const latest = list[0] || null;

  const interpretation = interpretOverview(o);
  const notes = interpretation
    ? `<div class="hr-notes"><p class="hr-notes-text">${esc(interpretation)}</p><p class="hr-notes-meta">${esc(o.notesMeta || "Interpretation of the health overview data above")}</p></div>`
    : emptyState("No health data recorded");

  return `
  <section class="panel hr-overview-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="activity"></i><h3 class="panel-title">Health Overview</h3></div>
    </div>
    <div class="panel-body hr-overview-body">
      <div class="hr-subcards">
        ${sub("healthStatus", "Health Status", o.healthStatus)}
        ${sub("vaccinationStatus", "Vaccination", latest ? latest.name : o.vaccinationStatus)}
        ${sub("deworming", "Deworming", o.deworming)}
        ${sub("neutered", "Neutered", o.neutered)}
      </div>
      ${notes}
    </div>
  </section>`;
}

function HistoryPanel(history) {
  const item = (it) => {
    const tone = TONE[it.tone] || TONE.green;
    return `
    <li class="hr-tl-item">
      <span class="hr-tl-dot ${tone.split(" ")[0]}"><i data-lucide="${ICON[it.tone] || "circle"}"></i></span>
      <div class="hr-tl-content">
        <div class="hr-tl-row"><span class="hr-tl-date">${esc(it.date)}</span><span class="hr-tl-doctor">${esc(it.doctor)}</span></div>
        <div class="hr-tl-title">${esc(it.title)}</div>
        <div class="hr-tl-desc">${esc(it.description)}</div>
      </div>
    </li>`;
  };
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="clipboard-list"></i><h3 class="panel-title">Medical History</h3></div>
    </div>
    <div class="panel-body">
      ${
        history && history.length
          ? `<ul class="hr-timeline">${history.map(item).join("")}</ul>`
          : emptyState("No medical history recorded yet")
      }
    </div>
  </section>`;
}

function VaccinationPanel(vax, { editing = false, mode = null, species = null, selecting = false } = {}) {
  const list = vax || [];
  const visible = selecting ? list : list.slice(0, 3);
  const row = (r, i) => `
    <tr${selecting ? ` data-action="vax-edit-row" data-idx="${i}" style="cursor:pointer"` : ""}>
      ${selecting ? `<td class="table-cell table-cell--center"><input type="checkbox" class="hr-vax-check" data-idx="${i}" onclick="event.stopPropagation()"></td>` : ""}
      <td class="table-cell table-cell--strong">${esc(r.vaccine)}</td>
      <td class="table-cell table-cell--mono">${esc(r.dateGiven || "—")}</td>
      <td class="table-cell table-cell--mono">Dose ${esc(r.doseNumber || "—")}</td>
      <td class="table-cell table-cell--mono">${esc(r.nextDue || "—")}</td>
      <td class="table-cell table-cell--right">${vaxStatusPill(r.status)}</td>
    </tr>`;
  const headActions = editing
    ? mode === "add"
      ? `<div class="panel-head-actions">
          ${Button({ text: "Add", variant: "default", size: "sm", attrs: 'data-action="open-vaccination-modal"' })}
        </div>`
      : `<div class="panel-head-actions">
          ${Button({ text: selecting ? "Done" : "Edit", variant: "outline", size: "sm", attrs: 'data-action="vax-toggle-select"' })}
          ${Button({ text: "Delete", variant: "destructive", size: "sm", attrs: `data-action="vax-delete-selected"${selecting ? "" : " disabled"}`, disabled: !selecting })}
        </div>`
    : "";

  const flags = list
    .flatMap((v) => (v.flags && Array.isArray(v.flags) ? v.flags : []))
    .filter((f, i, a) => a.indexOf(f) === i);
  const ageFlag = flags.find((f) => /not age-appropriate/i.test(f));
  const vetReview = flags.find((f) => /veterinary review required/i.test(f));
  let headerFlag = "";
  if (ageFlag) {
    headerFlag = `<span class="hr-vax-head-flag hr-vax-head-flag--warn" title="${esc(ageFlag)}"><i data-lucide="alert-triangle"></i>Not age-appropriate</span>`;
  } else if (vetReview) {
    headerFlag = `<span class="hr-vax-head-flag" title="${esc(vetReview)}"><i data-lucide="alert-triangle"></i>Veterinary Review Required</span>`;
  }
  const body = list.length
    ? `<div class="table-wrap">      <table class="table"><thead class="table-head"><tr>${selecting ? `<th></th>` : ""}<th>Vaccine</th><th>Date Given</th><th>Dose</th><th>Next Due</th><th class="table-cell--right">Status</th></tr></thead><tbody>${visible
        .map((r, i) => row(r, i))
        .join("")}</tbody></table></div>${
        !selecting && list.length > 3
          ? `<button type="button" class="hr-link" data-action="view-all-vaccinations">View all ${list.length} vaccinations</button>`
          : ""
      }`
    : emptyState("No vaccinations recorded");

  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="syringe"></i><h3 class="panel-title">Vaccination Record</h3></div>
      ${headerFlag}
      ${headActions}
    </div>
    <div class="panel-body">
      ${body}
    </div>
  </section>`;
}

function openAllVaccinationsDrawer(vax) {
  const list = vax || [];
  const row = (r) => `
    <div class="hr-vax-row">
      <div class="hr-vax-row-head">
        <span class="hr-vax-row-name">${esc(r.vaccine)}</span>
        ${vaxStatusPill(r.status)}
      </div>
      <div class="hr-vax-row-meta">
        <span>Date given: <strong>${esc(r.dateGiven || "—")}</strong></span>
        <span>Dose: <strong>${esc(r.doseNumber || "—")}</strong></span>
        <span>Next due: <strong>${esc((r.dueWindow && r.dueWindow.recommended) || r.nextDue || "—")}</strong></span>
      </div>
      ${r.manufacturer || r.productName || r.batchNumber || r.route || r.notes
        ? `<div class="hr-vax-row-extra">${[
            r.manufacturer && `Mfr: ${esc(r.manufacturer)}`,
            r.productName && `Product: ${esc(r.productName)}`,
            r.batchNumber && `Batch: ${esc(r.batchNumber)}`,
            r.route && `Route: ${esc(r.route)}`,
            r.notes && esc(r.notes),
          ].filter(Boolean).join(" · ")}</div>`
        : ""}
      ${(r.flags && r.flags.filter((f) => !/veterinary review required/i.test(f)).length)
        ? `<div class="hr-vax-row-flags">${r.flags
            .filter((f) => !/veterinary review required/i.test(f))
            .map((f) => `<span class="hr-flag">${esc(f)}</span>`)
            .join("")}</div>`
        : ""}
    </div>`;
  openDrawer({
    title: `All vaccinations (${list.length})`,
    body: list.length
      ? `<div class="hr-vax-list">${list.map(row).join("")}</div>`
      : `<div class="empty-state"><i data-lucide="syringe"></i><span>No vaccinations recorded.</span></div>`,
  });
}

const VAX_STATUS_EXPLAIN = {
  NOT_STARTED: "No dose has been recorded yet. Add a vaccination with a date to start tracking.",
  UPCOMING: "The next dose is scheduled but its due window hasn't opened yet.",
  DUE: "The recommended date for the next dose (or booster) is now due.",
  OVERDUE: "The dose/booster is past its due date — schedule it soon.",
  SERIES_IN_PROGRESS: "The primary vaccine series has started but isn't complete for the animal's age.",
  SERIES_COMPLETE: "The primary series is complete for the animal's age.",
  BOOSTER_DUE: "An annual/booster dose is due based on the last vaccination.",
  TOO_EARLY: "The animal is younger than this vaccine's minimum recommended age, so it isn't appropriate to give yet. Wait until the animal reaches the minimum age.",
};

function explainVaccineEntry(r) {
  const status = r.status || "UNKNOWN";
  const why = VAX_STATUS_EXPLAIN[status] || "Status could not be determined from the recorded data.";
  const otherFlags = (r.flags || []).filter((f) => !/veterinary review required/i.test(f));
  const flags = otherFlags.length
    ? `<p class="hr-explain-flags">${otherFlags.map((f) => `<span class="hr-flag">${esc(f)}</span>`).join("")}</p>`
    : "";
  const due = (r.dueWindow && r.dueWindow.recommended) || r.nextDue || null;
  const dueNote = due
    ? `<p class="hr-explain-note">Next due window (recommended): <strong>${esc(due)}</strong>. This is computed from the last administered date using the vaccine protocol interval.</p>`
    : (status === "TOO_EARLY" && r.minimumAgeWeeks
      ? `<p class="hr-explain-note">Minimum recommended age for this vaccine: <strong>${esc(r.minimumAgeWeeks)} weeks</strong>. It becomes appropriate once the animal reaches that age.</p>`
      : `<p class="hr-explain-note">No due window yet — usually because no administration date was recorded.</p>`);
  return `
    <div class="hr-explain-entry">
      <div class="hr-explain-entry-head"><span class="hr-explain-name">${esc(r.vaccine || "Vaccine")}</span>${vaxStatusPill(status)}</div>
      <p class="hr-explain-why">${esc(why)}</p>
      ${dueNote}
      ${flags}
    </div>`;
}

function openVaccinationExplainer(vax) {
  const list = vax || [];
  const intro = `
    <p class="hr-explain-intro">Each vaccine is evaluated by the vaccination engine using the recorded dose dates and the species protocol (WSAVA / AAHA guidelines). Common reasons you may see a status:</p>
    <ul class="hr-explain-list">
      <li><strong>Not Started</strong> — no dose date recorded for that vaccine.</li>
      <li><strong>Due / Overdue / Booster Due</strong> — the next dose falls on/after the recommended date from the last dose.</li>
      <li><strong>Series Complete</strong> — the primary puppy/kitten series is finished for the animal's age.</li>
      <li><strong>Too Early</strong> — the animal is below this vaccine's minimum recommended age, so it isn't appropriate to give yet.</li>
    </ul>`;
  const entries = list.length
    ? list.map(explainVaccineEntry).join("")
    : `<p class="hr-explain-note">No vaccinations recorded yet. Add one and the engine will evaluate its status and due window here.</p>`;
  openDrawer({
    title: "Understanding vaccination status",
    body: `<div class="hr-explain">${intro}${entries}</div>`,
  });
}

function openVaccineScheduleDrawer(record) {
  const protocols = (record && record.protocols) || [];
  const ageWeeks = record && typeof record.ageWeeks === "number" ? record.ageWeeks : null;
  const weeksToMonths = (w) => (w == null ? "—" : (w / 4.345).toFixed(1).replace(/\.0$/, ""));
  const row = (p) => {
    const min = p.minimum_age_weeks != null ? p.minimum_age_weeks : null;
    const series = p.series_completion_age_weeks != null ? p.series_completion_age_weeks : null;
    const allowedNow = ageWeeks !== null && min !== null && ageWeeks >= min;
    const tag = ageWeeks === null
      ? `<span class="pill">Age unknown</span>`
      : allowedNow
        ? `<span class="pill pill--green">Allowed now</span>`
        : `<span class="pill pill--gray">Too early</span>`;
    return `
      <div class="hr-sched-row">
        <div class="hr-sched-head">
          <span class="hr-sched-name">${esc(p.vaccine)}</span>
          ${tag}
        </div>
        <div class="hr-sched-meta">
          <span>Category: <strong>${esc(p.category || "—")}</strong></span>
          <span>Allowed from: <strong>${min != null ? `${min} weeks (~${weeksToMonths(min)} mo)` : "—"}</strong></span>
          <span>Series complete by: <strong>${series != null ? `${series} weeks (~${weeksToMonths(series)} mo)` : "—"}</strong></span>
          <span>Booster every: <strong>${p.booster_interval_days != null ? `${p.booster_interval_days} days` : "—"}</strong></span>
        </div>
      </div>`;
  };
  const intro = ageWeeks !== null
    ? `<p class="hr-explain-intro">Animal age: <strong>~${ageWeeks} weeks (~${weeksToMonths(ageWeeks)} months)</strong>. Vaccines marked "Too early" are not yet appropriate at this age.</p>`
    : `<p class="hr-explain-intro">No age recorded for this animal, so eligibility can't be determined. Enter an age estimate to see what's allowed.</p>`;
  openDrawer({
    title: `Vaccine schedule${record && record.species ? ` — ${esc(titleCase(record.species))}` : ""}`,
    body: protocols.length
      ? `<div class="hr-sched-list">${intro}${protocols.map(row).join("")}</div>`
      : `<div class="empty-state"><i data-lucide="clipboard-list"></i><span>No vaccine protocols for this species.</span></div>`,
  });
}

function RemindersPanel(rem) {
  const item = (r) => `
    <li class="hr-reminder">
      <div class="hr-reminder-left">
        <span class="tint-circle ${TONE[r.tone] ? TONE[r.tone].split(" ")[0] : "tint-blue"}"><i data-lucide="${esc(r.icon)}"></i></span>
        <div>
          <div class="hr-reminder-title">${esc(r.title)}</div>
          <div class="hr-reminder-due">Due ${esc(r.dueDate)}</div>
        </div>
      </div>
      <span class="pill pill--${r.tone}">${esc(r.days + " days")}</span>
    </li>`;
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bell"></i><h3 class="panel-title">Upcoming Reminders</h3></div>
    </div>
    <div class="panel-body">
      ${rem && rem.length ? `<ul class="hr-reminder-list">${rem.map(item).join("")}</ul>` : emptyState("No upcoming reminders")}
    </div>
  </section>`;
}

function VitalsPanel(vitals, meta, { editing = false, mode = null, openForm = false } = {}) {
  const item = (v) => `
    <li class="hr-vital">
      <div class="hr-vital-left">
        <span class="hr-vital-label">${esc(v.label)}</span>
        <span class="hr-vital-value">${esc(v.value)}<small>${esc(v.unit)}</small></span>
      </div>
    </li>`;

  const headActions = editing && mode === "add"
    ? `<div class="panel-head-actions">${Button({
        text: "Add",
        variant: "default",
        size: "sm",
        attrs: `data-action="open-vital-modal"`,
      })}</div>`
    : "";

  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="heart-pulse"></i><h3 class="panel-title">Vital Signs</h3></div>
      ${headActions}
    </div>
    <div class="panel-body">
      ${
        vitals && vitals.length
          ? `<ul class="hr-vital-list">${vitals.map(item).join("")}</ul><p class="hr-vital-meta">${esc(meta || "")}</p>`
          : emptyState("No vitals recorded")
      }
    </div>
  </section>`;
}

function DocumentsPanel(docs, { editing = false, mode = null } = {}) {
  const row = (d, i) => `
    <tr class="hr-doc-row" data-action="view-document" data-idx="${i}" style="cursor:pointer">
      <td class="table-cell table-cell--strong"><i data-lucide="file-text" class="hr-doc-ic"></i>${esc(d.name)}</td>
      <td class="table-cell table-cell--muted">${esc(d.type || "Document")}</td>
      <td class="table-cell table-cell--mono">${esc(d.meta || "—")}</td>
      <td class="table-cell table-cell--right">${d.fileUrl ? `<span class="hr-doc-open">View</span>` : '<span class="table-cell--muted">—</span>'}</td>
    </tr>`;
  const headActions = `
    ${editing && mode === "add" ? Button({ text: "Add", variant: "default", size: "sm", attrs: `data-action="open-document-modal"` }) : ""}
    ${(docs && docs.length > 3) ? `<button type="button" class="hr-link" data-action="view-all-documents">View all ${docs.length} documents</button>` : ""}
  `;
  const list = (docs || []).slice(0, 3);
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="file-text"></i><h3 class="panel-title">Health Documents</h3></div>
      ${headActions ? `<div class="panel-head-actions">${headActions}</div>` : ""}
    </div>
    <div class="panel-body">
      ${
        list.length
          ? `<div class="table-wrap hr-doc-table-wrap"><table class="table"><thead class="table-head"><tr><th>Document</th><th>Type</th><th>Meta</th><th class="table-cell--right">File</th></tr></thead><tbody>${list.map(row).join("")}</tbody></table></div>`
          : emptyState("No documents uploaded")
      }
    </div>
  </section>`;
}

function openAllDocumentsDrawer(docs) {
  const list = docs || [];
  const row = (d, i) => `
    <div class="hr-doc-row-card" data-action="view-document" data-idx="${i}" style="cursor:pointer">
      <div class="hr-doc-row-head">
        <span class="hr-doc-row-name"><i data-lucide="file-text"></i>${esc(d.name)}</span>
        ${d.fileUrl ? `<span class="hr-doc-open">View</span>` : ""}
      </div>
      <div class="hr-doc-row-meta">
        <span>Type: <strong>${esc(d.type || "Document")}</strong></span>
        <span>Meta: <strong>${esc(d.meta || "—")}</strong></span>
      </div>
    </div>`;
  openDrawer({
    title: `All documents (${list.length})`,
    body: list.length
      ? `<div class="hr-doc-list">${list.map(row).join("")}</div>`
      : `<div class="empty-state"><i data-lucide="file-x"></i><span>No documents uploaded.</span></div>`,
    onMount: (bodyEl) => {
      bodyEl.querySelectorAll(".hr-doc-row-card").forEach((card) => {
        card.addEventListener("click", () => {
          const idx = parseInt(card.getAttribute("data-idx") || "-1", 10);
          if (!Number.isNaN(idx) && idx >= 0) {
            closeDrawer();
            openDocumentPreview(idx);
          }
        });
      });
    },
  });
}

function statBlock(num, label, icon, tone) {
  return `
  <div class="hr-stat">
    <span class="tint-circle ${TONE[tone].split(" ")[0]}"><i data-lucide="${icon}"></i></span>
    <div class="hr-stat-text">
      <div class="hr-stat-num">${esc(String(num))}</div>
      <div class="hr-stat-label">${esc(label)}</div>
    </div>
  </div>`;
}

function StatsPanel(r) {
  const checkups = (r.history || []).filter((h) => /check/i.test(h.title)).length;
  return `
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bar-chart-3"></i><h3 class="panel-title">Health Statistics</h3></div>
    </div>
    <div class="panel-body">
      <div class="hr-stat-strip">
        ${statBlock(checkups, "Check-ups", "stethoscope", "green")}
        ${statBlock((r.vaccinations || []).length, "Vaccinations", "syringe", "blue")}
        ${statBlock((r.reminders || []).length, "Reminders", "bell", "yellow")}
        ${statBlock((r.vitals || []).length, "Vitals logged", "heart-pulse", "purple")}
      </div>
    </div>
  </section>`;
}

export function HealthRecordPage(user, record, ui = {}) {
  const r = record || {};
  const hasRecord = !!r.hasMedicalRecord;
  const editing = !!ui.editing;
  const openForm = ui.openForm || null;
  return AppShell({
    user,
    activeNav: "health records",
    children: [
      PageHead(hasRecord, editing, ui.mode),
      `<div class="hr-grid">${ProfilePanel(r)}${OverviewPanel(r.overview, r.vaccinations)}</div>`,
      `<div class="hr-trio">
        ${HistoryPanel(r.history)}
        <div class="hr-trio-col">${VaccinationPanel(r.vaccinations, {
          editing,
          mode: ui.mode,
          selecting: ui.vaxSelecting,
        })}${RemindersPanel(r.reminders)}</div>
        <div class="hr-trio-col">${VitalsPanel(r.vitals, r.vitalMeta, {
          editing,
          mode: ui.mode,
          openForm: openForm === "vital",
        })}${DocumentsPanel(r.documents, { editing, mode: ui.mode })}</div>
      </div>
      ${StatsPanel(r)}`,
    ].join(""),
  });
}

export function HealthRecordLoading(user) {
  return AppShell({
    user,
    activeNav: "health records",
    children: emptyState("Loading health record…", "loader"),
  });
}

export function HealthRecordError(user, message) {
  return AppShell({
    user,
    activeNav: "health records",
    children: emptyState(message || "Could not load this health record.", "alert-triangle"),
  });
}

export function HealthRecordEmpty(user) {
  return AppShell({
    user,
    activeNav: "health records",
    children: emptyState("No health record found for this animal.", "search"),
  });
}

let _user = null;
let _record = null;
let _ui = { editing: false, mode: null, openForm: null, saving: false, vaxSelecting: false };

function syncHidden(id, val) {
  const el = document.getElementById(`${id}-value`);
  if (el) el.value = val;
}

function paint() {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = HealthRecordPage(_user, _record, _ui);
  createIcons({ icons });
  initShell();
  initDatePicker(app);
}

async function reloadRecord() {
  const id = _record && _record.id;
  if (!id) return;
  const fresh = await fetchAnimalHealthRecord(id);
  if (fresh) _record = fresh;
  paint();
  maybeNotifyFromRecord();
}

const ADOPTION_NOTIFY_MS = 15 * 60 * 1000;
const ADOPTION_STORE_KEY = "furescue_adoption_ready";
let _adoptionToastEl = null;

function ensureToastViewport() {
  let vp = document.querySelector(".toast-viewport--adoption");
  if (!vp) {
    vp = document.createElement("div");
    vp.className = "toast-viewport toast-viewport--adoption";
    vp.setAttribute("aria-live", "polite");
    document.body.appendChild(vp);
  }
  return vp;
}

function readAdoptionStore() {
  try {
    const raw = localStorage.getItem(ADOPTION_STORE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function writeAdoptionStore(entry) {
  try {
    if (entry) localStorage.setItem(ADOPTION_STORE_KEY, JSON.stringify(entry));
    else localStorage.removeItem(ADOPTION_STORE_KEY);
  } catch {
    /* storage unavailable */
  }
}

function maybeNotifyAdoptionReady(kind) {
  if (kind === "vaccination") _ui.addedVaccination = true;
  if (kind === "vital") _ui.addedVital = true;
  if (_adoptionToastEl || !_ui.addedVaccination || !_ui.addedVital) return;
  if (!_record || !_record.id) return;
  const entry = {
    animalId: _record.id,
    animalName: _record.name || "this animal",
    createdAt: Date.now(),
    expiresAt: Date.now() + ADOPTION_NOTIFY_MS,
    listed: false,
  };
  writeAdoptionStore(entry);
  showAdoptionToast(entry);
}

function maybeNotifyFromRecord() {
  if (_adoptionToastEl || !_record || !_record.id) return;
  const hasVaccination = Array.isArray(_record.vaccinations) && _record.vaccinations.length > 0;
  const hasVital = Array.isArray(_record.vitals) && _record.vitals.length > 0;
  if (!hasVaccination || !hasVital) return;
  const stored = readAdoptionStore();
  if (stored && (stored.listed || stored.animalId === _record.id)) return;
  const entry = {
    animalId: _record.id,
    animalName: _record.name || "this animal",
    createdAt: Date.now(),
    expiresAt: Date.now() + ADOPTION_NOTIFY_MS,
    listed: false,
  };
  writeAdoptionStore(entry);
  showAdoptionToast(entry);
}

async function autoListOnExpiry(entry) {
  if (entry.listed) return;
  entry.listed = true;
  writeAdoptionStore(entry);
  try {
    await createAdoptionListing(entry.animalId);
    toast(`${esc(entry.animalName)} moved to adoption ready.`, { type: "success" });
  } catch {
    entry.listed = false;
    writeAdoptionStore(entry);
  }
  dismissAdoptionToast();
}

function showAdoptionToast(entry) {
  const stored = entry || readAdoptionStore();
  if (!stored) return;
  const now = Date.now();
  const expiresAt = stored.expiresAt || now + ADOPTION_NOTIFY_MS;
  if (expiresAt <= now && !stored.listed) {
    autoListOnExpiry(stored);
    return;
  }

  const viewport = ensureToastViewport();
  const el = document.createElement("div");
  el.className = "toast toast--adoption is-visible";
  el.setAttribute("role", "status");
  el.innerHTML = `
    <i data-lucide="list-plus" class="toast-icon"></i>
    <div class="toast-adoption-body">
      <p class="toast-message">Health record updated. You can list <strong>${esc(stored.animalName || "this animal")}</strong> for adoption.</p>
      <div class="toast-countdown">
        <div class="toast-countdown-bar"><span class="toast-countdown-fill"></span></div>
        <span class="toast-countdown-text">Ready in 15:00</span>
      </div>
      <button type="button" class="toast-adoption-btn">Add to adoption list</button>
    </div>
    <button class="toast-close" aria-label="Dismiss"><i data-lucide="x"></i></button>`;
  viewport.appendChild(el);
  createIcons({ icons });
  _adoptionToastEl = el;

  const fill = el.querySelector(".toast-countdown-fill");
  const text = el.querySelector(".toast-countdown-text");
  const btn = el.querySelector(".toast-adoption-btn");
  const tick = () => {
    const remaining = Math.max(0, expiresAt - Date.now());
    const mins = Math.floor(remaining / 60000);
    const secs = Math.floor((remaining % 60000) / 1000);
    text.textContent = `Ready in ${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
    fill.style.width = `${(remaining / ADOPTION_NOTIFY_MS) * 100}%`;
    if (remaining <= 0) {
      clearInterval(timer);
      btn.disabled = true;
      text.textContent = "Ready to list";
      autoListOnExpiry(stored);
    }
  };
  const timer = setInterval(tick, 1000);
  tick();

  btn.addEventListener("click", async () => {
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerHTML = `<span>Saving…</span>`;
    try {
      await createAdoptionListing(stored.animalId);
      stored.listed = true;
      writeAdoptionStore(stored);
      btn.innerHTML = `<span>Added to adoption list</span>`;
      toast("Added to adoption list.", { type: "success" });
      setTimeout(dismissAdoptionToast, 1500);
    } catch (err) {
      btn.disabled = false;
      btn.innerHTML = `<span>Add to adoption list</span>`;
      toast(err && err.message ? err.message : "Could not add to adoption list.", { type: "error" });
    }
  });

  el.querySelector(".toast-close").addEventListener("click", dismissAdoptionToast);
}

function restoreAdoptionToast() {
  const stored = readAdoptionStore();
  if (stored && !stored.listed && !_adoptionToastEl) showAdoptionToast(stored);
}

function dismissAdoptionToast() {
  if (!_adoptionToastEl) return;
  const el = _adoptionToastEl;
  _adoptionToastEl = null;
  el.classList.remove("is-visible");
  setTimeout(() => el.remove(), 200);
}


function mapRecordToLegacyDetails(v) {
  return {
    vaccine: v.vaccine ?? null,
    dateGiven: v.administered_date ?? null,
    nextDue: v.next_due ?? null,
    status: v.status ?? null,
    doseNumber: v.dose_number ?? null,
    manufacturer: v.manufacturer ?? null,
    productName: v.product_name ?? null,
    batchNumber: v.batch_number ?? null,
    route: v.route ?? null,
    notes: v.notes ?? null,
  };
}

async function deleteSelectedVaccinations() {
  if (!_record || !_record.id) return;
  const checks = Array.from(document.querySelectorAll(".hr-vax-check:checked"));
  if (!checks.length) {
    toast("Select at least one vaccination to delete.", { type: "info" });
    return;
  }
  const count = checks.length;
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="vax-del-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="trash-2" class="dialog-icon"></i>
          <h3 class="dialog-title" id="vax-del-title">Delete vaccination${count > 1 ? "s" : ""}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        <p class="dialog-message">Delete ${count} selected vaccination record${count > 1 ? "s" : ""}? This cannot be undone.</p>
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
        ${Button({ text: "Delete", variant: "destructive", attrs: 'data-act="ok"' })}
      </div>
    </div>`;
  document.body.appendChild(overlay);
  createIcons({ icons });

  const close = () => overlay.remove();
  const okBtn = overlay.querySelector('[data-act="ok"]');
  const performDelete = async () => {
    const removeIdx = new Set(checks.map((c) => parseInt(c.getAttribute("data-idx") || "0", 10)));
    const remaining = (_record.vaccinations || []).filter((_, i) => !removeIdx.has(i));
    const records = remaining.map((v) => ({
      vaccine: v.vaccine,
      administered_date: v.dateGiven || null,
      next_due: v.nextDue || null,
      status: v.status || null,
      dose_number: v.doseNumber || null,
      manufacturer: v.manufacturer || null,
      product_name: v.productName || null,
      batch_number: v.batchNumber || null,
      route: v.route || null,
      notes: v.notes || null,
    }));
    const details = records.map(mapRecordToLegacyDetails);
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Deleting…</span>`;
    try {
      await upsertAnimalVaccinations(_record.id, records, details);
      toast("Vaccination record(s) deleted.", { type: "success" });
      _ui.vaxSelecting = false;
      close();
      await reloadRecord();
    } catch (err) {
      okBtn.disabled = false;
      okBtn.innerHTML = `<i data-lucide="trash-2"></i><span>Delete</span>`;
      createIcons({ icons });
      toast(err && err.message ? err.message : "Could not delete vaccination.", { type: "error" });
    }
  };

  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  okBtn.addEventListener("click", performDelete);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

function handleAction(actionEl) {
  const action = actionEl.getAttribute("data-action");
  switch (action) {
    case "edit-record":
      _ui.editing = !_ui.editing;
      _ui.mode = _ui.editing ? "edit" : null;
      _ui.openForm = null;
      _ui.vaxSelecting = false;
      paint();
      return;
    case "add-record":
      _ui.editing = !_ui.editing;
      _ui.mode = _ui.editing ? "add" : null;
      _ui.openForm = null;
      _ui.vaxSelecting = false;
      paint();
      return;
    case "open-vaccination-modal":
      openVaccinationDialog();
      return;
    case "vax-toggle-select":
      _ui.vaxSelecting = !_ui.vaxSelecting;
      paint();
      return;
    case "vax-delete-selected":
      deleteSelectedVaccinations();
      return;
    case "vax-edit-row": {
      const idx = parseInt(actionEl.getAttribute("data-idx") || "-1", 10);
      if (!Number.isNaN(idx) && idx >= 0) openVaccinationDialog(idx);
      return;
    }
    case "view-all-vaccinations":
      openAllVaccinationsDrawer(_record.vaccinations);
      return;
    case "view-all-documents":
      openAllDocumentsDrawer(_record && _record.documents);
      return;
    case "explain-vaccinations":
      openVaccinationExplainer(_record.vaccinations);
      return;
    case "vaccine-schedule":
      openVaccineScheduleDrawer(_record);
      return;
    case "open-vital-modal":
      openVitalDialog();
      return;
    case "open-document-modal":
      openDocumentDialog();
      return;
    case "view-document": {
      const di = parseInt(actionEl.getAttribute("data-idx") || "-1", 10);
      if (!Number.isNaN(di) && di >= 0) openDocumentPreview(di);
      return;
    }
    default:
      return;
  }
}

function openVaccinationDialog(editIdx = null) {
  const species = _record && _record.species;
  const editing = editIdx !== null && Array.isArray(_record.vaccinations) && !!_record.vaccinations[editIdx];
  const current = editing ? _record.vaccinations[editIdx] : {};
  const curVaccine = current.vaccine || "";
  const curStatus = current.status || "complete";
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="vax-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="syringe" class="dialog-icon"></i>
          <h3 class="dialog-title" id="vax-title">${editing ? "Edit Vaccination Record" : "Add Vaccination Record"}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        <div class="hr-form-row">
          <label class="dialog-label">Vaccine${selectField({ id: "vax-vaccine", name: "vaccine", options: vaccineOptionList(species), value: curVaccine, placeholder: "Select vaccine…" })}</label>
          <label class="dialog-label">Date Given${DatePicker({ id: "vax-date-given", name: "dateGiven", value: current.dateGiven || "", placeholder: "Pick a date" })}</label>
          <label class="dialog-label">Dose #<input class="hr-input" type="number" id="vax-dose" min="1" placeholder="1" value="${esc(current.doseNumber || "")}"></label>
          <label class="dialog-label">Next Schedule${DatePicker({ id: "vax-next-due", name: "nextDue", value: current.nextDue || "", placeholder: "Pick a date" })}</label>
          <label class="dialog-label">Status${selectField({ id: "vax-status", name: "status", options: STATUS_OPTIONS, value: curStatus, placeholder: "Status" })}</label>
        </div>
        <div class="hr-form-row">
          <label class="dialog-label">Manufacturer<input class="hr-input" id="vax-mfr" value="${esc(current.manufacturer || "")}" placeholder="e.g. Zoetis"></label>
          <label class="dialog-label">Product<input class="hr-input" id="vax-product" value="${esc(current.productName || "")}" placeholder="e.g. Vanguard"></label>
          <label class="dialog-label">Batch No.<input class="hr-input" id="vax-batch" value="${esc(current.batchNumber || "")}" placeholder="Batch"></label>
          <label class="dialog-label">Route<input class="hr-input" id="vax-route" value="${esc(current.route || "")}" placeholder="injectable"></label>
        </div>
        <label class="dialog-label">Notes<input class="hr-input" id="vax-notes" value="${esc(current.notes || "")}" placeholder="Optional notes"></label>
        <p class="dialog-error" id="vax-error" hidden></p>
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
        ${Button({ text: "Save", variant: "default", attrs: 'data-act="ok"' })}
      </div>
    </div>`;

  document.body.appendChild(overlay);
  createIcons({ icons });
  initSelect(overlay, {
    "vax-vaccine": (val) => syncHidden("vax-vaccine", val),
    "vax-status": (val) => syncHidden("vax-status", val),
  });
  initDatePicker(overlay);

  const errorEl = overlay.querySelector("#vax-error");
  const okBtn = overlay.querySelector('[data-act="ok"]');
  const close = () => overlay.remove();

  const submit = async () => {
    const vaccine = (overlay.querySelector("#vax-vaccine-value")?.value || "").trim();
    if (!vaccine) {
      errorEl.textContent = "Please select a vaccine.";
      errorEl.hidden = false;
      return;
    }
    const administeredDate = overlay.querySelector("#vax-date-given-value")?.value || null;
    const nextDue = overlay.querySelector("#vax-next-due-value")?.value || null;
    const doseInput = overlay.querySelector("#vax-dose").value;
    const doseNumber = parseInt(doseInput || "", 10);
    const entry = {
      vaccine,
      administered_date: administeredDate,
      next_due: nextDue,
      dose_number: Number.isNaN(doseNumber) ? ((_record.vaccinations || []).length + 1) : doseNumber,
      status: overlay.querySelector("#vax-status-value")?.value || "complete",
      manufacturer: (overlay.querySelector("#vax-mfr").value || "").trim() || null,
      product_name: (overlay.querySelector("#vax-product").value || "").trim() || null,
      batch_number: (overlay.querySelector("#vax-batch").value || "").trim() || null,
      route: (overlay.querySelector("#vax-route").value || "").trim() || null,
      notes: (overlay.querySelector("#vax-notes").value || "").trim() || null,
    };
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Saving…</span>`;
    try {
      let records;
      if (editing && Array.isArray(_record.vaccinations)) {
        records = _record.vaccinations.map((v, i) => (i === editIdx ? entry : v));
      } else {
        records = [...(_record.vaccinations || []), entry];
      }
      const details = records.map(mapRecordToLegacyDetails);
      await upsertAnimalVaccinations(_record.id, records, details);
      toast("Vaccination saved.", { type: "success" });
      if (!editing) maybeNotifyAdoptionReady("vaccination");
      close();
      await reloadRecord();
    } catch (err) {
      okBtn.disabled = false;
      okBtn.innerHTML = `<i data-lucide="syringe"></i><span>Save</span>`;
      createIcons({ icons });
      errorEl.textContent = err && err.message ? err.message : "Could not save vaccination.";
      errorEl.hidden = false;
    }
  };

  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

async function openVitalDialog() {
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="vital-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="heart-pulse" class="dialog-icon"></i>
          <h3 class="dialog-title" id="vital-title">Add Vital Sign</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        <div class="hr-form-row">
          <label class="dialog-label">Vital${selectField({ id: "vital-type", name: "vital", options: VITAL_OPTIONS, value: "Weight", placeholder: "Select vital…" })}</label>
          <label class="dialog-label">Value<input class="hr-input" id="vital-value" type="number" step="any" placeholder="0"></label>
          <label class="dialog-label">Unit<span id="vital-unit-display" class="hr-input hr-input--readonly">kg</span></label>
        </div>
        <p class="dialog-error" id="vital-error" hidden></p>
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
        ${Button({ text: "Save", variant: "default", attrs: 'data-act="ok"' })}
      </div>`;

  document.body.appendChild(overlay);
  createIcons({ icons });
  initSelect(overlay, {
    "vital-type": (val) => {
      const opt = VITAL_OPTIONS.find((o) => o.value === val);
      const display = overlay.querySelector("#vital-unit-display");
      if (opt && display) display.textContent = opt.unit;
      syncHidden("vital-type", val);
    },
  });

  const errorEl = overlay.querySelector("#vital-error");
  const okBtn = overlay.querySelector('[data-act="ok"]');
  const close = () => overlay.remove();

  const submit = async () => {
    const label = (overlay.querySelector("#vital-type-value")?.value || "").trim();
    if (!label) {
      errorEl.textContent = "Please select a vital.";
      errorEl.hidden = false;
      return;
    }
    const rawValue = (overlay.querySelector("#vital-value")?.value || "").trim();
    if (!rawValue) {
      errorEl.textContent = "Please enter a value.";
      errorEl.hidden = false;
      return;
    }
    const value = parseFloat(rawValue);
    if (Number.isNaN(value)) {
      errorEl.textContent = "Value must be a number.";
      errorEl.hidden = false;
      return;
    }
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Saving…</span>`;
    try {
      if (label === "Weight") {
        await upsertAnimalMedical(_record.id, { weight_kg: value });
      } else if (label === "Body Temperature") {
        await upsertAnimalMedical(_record.id, { temperature_c: value });
      } else {
        await addAnimalVital(_record.id, { heart_rate_bpm: value });
      }
      toast("Vital sign saved.", { type: "success" });
      maybeNotifyAdoptionReady("vital");
      close();
      await reloadRecord();
    } catch (err) {
      okBtn.disabled = false;
      okBtn.innerHTML = `<i data-lucide="heart-pulse"></i><span>Save</span>`;
      createIcons({ icons });
      errorEl.textContent = err && err.message ? err.message : "Could not save vital sign.";
      errorEl.hidden = false;
    }
  };

  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

function openDocumentPreview(idx) {
  const doc = (_record.documents || [])[idx];
  if (!doc) return;
  const rawUrl = doc.fileUrl || doc.url || doc.file_url || (doc.file && doc.file.url) || "";
  const fileUrl = resolveDocUrl(rawUrl);
  const nameHint = doc.fileUrl || doc.url || doc.file_url || doc.name || "";
  const isImage = /\.(jpe?g|png|gif|webp|avif|bmp)$/i.test(nameHint) || (fileUrl && /\.(jpe?g|png|gif|webp|avif|bmp)$/i.test(fileUrl));
  const body = fileUrl
    ? isImage
      ? `<img class="hr-doc-preview-img" src="${esc(fileUrl)}" alt="${esc(doc.name)}" onerror="this.style.display='none';this.nextElementSibling.style.display='flex';"><div class="empty-state" style="display:none"><i data-lucide="image-off"></i><span>Could not load image preview.</span></div>`
      : `<iframe class="hr-doc-preview-frame" src="${esc(fileUrl)}" title="${esc(doc.name)}"></iframe>`
    : `<div class="empty-state"><i data-lucide="file-x"></i><span>No file attached to this document.</span></div>`;
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="doc-prev-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="file-text" class="dialog-icon"></i>
          <h3 class="dialog-title" id="doc-prev-title">${esc(doc.name)}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body hr-doc-preview-body">
        ${body}
        ${doc.type || doc.meta ? `<p class="hr-doc-preview-meta">${esc([doc.type, doc.meta].filter(Boolean).join(" · "))}</p>` : ""}
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Close", variant: "outline", attrs: 'data-act="cancel"' })}
        ${fileUrl ? Button({ text: "Open original", variant: "default", attrs: `data-act="open-orig" data-url="${esc(fileUrl)}"` }) : ""}
      </div>`;
  document.body.appendChild(overlay);
  createIcons({ icons });

  const close = () => overlay.remove();
  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  const openOrig = overlay.querySelector('[data-act="open-orig"]');
  if (openOrig) {
    openOrig.addEventListener("click", () => window.open(openOrig.getAttribute("data-url"), "_blank", "noopener"));
  }
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

function openDocumentDialog(editIdx = null) {
  const editing = editIdx !== null && Array.isArray(_record.documents) && !!_record.documents[editIdx];
  const current = editing ? _record.documents[editIdx] : {};
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="doc-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="file-text" class="dialog-icon"></i>
          <h3 class="dialog-title" id="doc-title">${editing ? "Edit Document" : "Upload Document"}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        ${
          editing
            ? ""
            : `<label class="dialog-label">File (PDF or image)</label>
        <div class="aa-photo">
          <div class="aa-photo-preview" id="doc-file-preview"><i data-lucide="file-plus"></i></div>
          <input type="file" id="doc-file" class="aa-photo-input" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp" required />
          <span class="hr-doc-filename" id="doc-filename"></span>
        </div>`
        }
        <label class="dialog-label">Name<input class="hr-input" id="doc-name" value="${esc(current.name || "")}" placeholder="e.g. Vaccination Certificate"></label>
        <label class="dialog-label">Type<input class="hr-input" id="doc-type" value="${esc(current.type || "")}" placeholder="e.g. Certificate"></label>
        <label class="dialog-label">Notes / Meta<input class="hr-input" id="doc-meta" value="${esc(current.meta || "")}" placeholder="Optional"></label>
        <p class="dialog-error" id="doc-error" hidden></p>
      </div>
      <div class="dialog-foot">
        ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
        ${Button({ text: editing ? "Save" : "Upload", variant: "default", attrs: 'data-act="ok"' })}
      </div>`;
  document.body.appendChild(overlay);
  createIcons({ icons });

  const fileInput = overlay.querySelector("#doc-file");
  const fileNameEl = overlay.querySelector("#doc-filename");
  const filePreview = overlay.querySelector("#doc-file-preview");
  if (fileInput && fileNameEl) {
    fileInput.addEventListener("change", () => {
      const f = fileInput.files && fileInput.files[0];
      fileNameEl.textContent = f ? f.name : "";
      if (filePreview) {
        if (f && f.type.startsWith("image/")) {
          const reader = new FileReader();
          reader.onload = (e) => {
            filePreview.style.backgroundImage = "";
            filePreview.innerHTML = `<img src="${e.target.result}" alt="${esc(f.name)}" style="width:100%;height:100%;object-fit:cover;" />`;
          };
          reader.readAsDataURL(f);
        } else {
          filePreview.style.backgroundImage = "";
          filePreview.innerHTML = '<i data-lucide="file-plus"></i>';
          createIcons({ icons });
        }
      }
    });
  }

  const errorEl = overlay.querySelector("#doc-error");
  const okBtn = overlay.querySelector('[data-act="ok"]');
  const close = () => overlay.remove();

  const submit = async () => {
    const name = (overlay.querySelector("#doc-name")?.value || "").trim();
    const type = (overlay.querySelector("#doc-type")?.value || "").trim() || null;
    const meta = (overlay.querySelector("#doc-meta")?.value || "").trim() || null;
    if (!name) {
      errorEl.textContent = "Please enter a document name.";
      errorEl.hidden = false;
      return;
    }
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Saving…</span>`;
    try {
      if (editing) {
        await updateAnimalDocument(current.id, { name, doc_type: type, meta });
        toast("Document updated.", { type: "success" });
      } else {
        const fileInput = overlay.querySelector("#doc-file");
        if (!fileInput || !fileInput.files || !fileInput.files[0]) {
          errorEl.textContent = "Please choose a PDF or image file.";
          errorEl.hidden = false;
          okBtn.disabled = false;
          okBtn.innerHTML = `<span>Upload</span>`;
          return;
        }
        const fd = new FormData();
        fd.append("file", fileInput.files[0]);
        fd.append("name", name);
        if (type) fd.append("doc_type", type);
        if (meta) fd.append("meta", meta);
        await uploadAnimalDocument(_record.id, fd);
        toast("Document uploaded.", { type: "success" });
      }
      close();
      await reloadRecord();
    } catch (err) {
      okBtn.disabled = false;
      okBtn.innerHTML = `<span>${editing ? "Save" : "Upload"}</span>`;
      createIcons({ icons });
      errorEl.textContent = err && err.message ? err.message : "Could not save document.";
      errorEl.hidden = false;
    }
  };

  overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
  overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
  overlay.querySelector(".dialog-x").addEventListener("click", close);
  overlay.addEventListener("click", (e) => {
    if (e.target === overlay) close();
  });
}

export function renderHealthRecord(user, record) {
  _user = user;
  _record = record || null;
  _ui = { editing: false, mode: null, openForm: null, saving: false, addedVaccination: false, addedVital: false };
  paint();

  const app = document.getElementById("app");
  if (!app) return;
  app.addEventListener("click", (e) => {
    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      handleAction(actionEl);
    }
  });

  restoreAdoptionToast();
  maybeNotifyFromRecord();
}
