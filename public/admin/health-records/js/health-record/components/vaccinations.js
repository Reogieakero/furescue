import { Button } from "/assets/js/components/ui/button.js";
import { openDrawer } from "/assets/js/components/ui/drawer.js";
import { esc } from "../../health-records/components/util.js";
import { vaxStatusPill, emptyState, titleCase } from "../util.js";

export function VaccinationPanel(vax, { editing = false, mode = null, species = null, selecting = false } = {}) {
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

export function openAllVaccinationsDrawer(vax) {
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
    : status === "TOO_EARLY" && r.minimumAgeWeeks
      ? `<p class="hr-explain-note">Minimum recommended age for this vaccine: <strong>${esc(r.minimumAgeWeeks)} weeks</strong>. It becomes appropriate once the animal reaches that age.</p>`
      : `<p class="hr-explain-note">No due window yet — usually because no administration date was recorded.</p>`;
  return `
    <div class="hr-explain-entry">
      <div class="hr-explain-entry-head"><span class="hr-explain-name">${esc(r.vaccine || "Vaccine")}</span>${vaxStatusPill(status)}</div>
      <p class="hr-explain-why">${esc(why)}</p>
      ${dueNote}
      ${flags}
    </div>`;
}

export function openVaccinationExplainer(vax) {
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

export function openVaccineScheduleDrawer(record) {
  const protocols = (record && record.protocols) || [];
  const ageWeeks = record && typeof record.ageWeeks === "number" ? record.ageWeeks : null;
  const weeksToMonths = (w) => (w == null ? "—" : (w / 4.345).toFixed(1).replace(/\.0$/, ""));
  const row = (p) => {
    const min = p.minimum_age_weeks != null ? p.minimum_age_weeks : null;
    const series = p.series_completion_age_weeks != null ? p.series_completion_age_weeks : null;
    const allowedNow = ageWeeks !== null && min !== null && ageWeeks >= min;
    const tag =
      ageWeeks === null
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
  const intro =
    ageWeeks !== null
      ? `<p class="hr-explain-intro">Animal age: <strong>~${ageWeeks} weeks (~${weeksToMonths(ageWeeks)} months)</strong>. Vaccines marked "Too early" are not yet appropriate at this age.</p>`
      : `<p class="hr-explain-intro">No age recorded for this animal, so eligibility can't be determined. Enter an age estimate to see what's allowed.</p>`;
  openDrawer({
    title: `Vaccine schedule${record && record.species ? ` — ${esc(titleCase(record.species))}` : ""}`,
    body: protocols.length
      ? `<div class="hr-sched-list">${intro}${protocols.map(row).join("")}</div>`
      : `<div class="empty-state"><i data-lucide="clipboard-list"></i><span>No vaccine protocols for this species.</span></div>`,
  });
}
