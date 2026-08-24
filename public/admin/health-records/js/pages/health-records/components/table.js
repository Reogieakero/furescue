import { PaginationBar } from "/js/components/ui/pagination.js";
import { Badge } from "/js/components/ui/badge.js";
import { state, pagedRecords, recordCounts, FILTERS, PAGE_SIZE } from "../state.js";
import { esc, fmtDate, daysUntil, VACC_TONE } from "./util.js";

export function FilterTabs() {
  const c = recordCounts();
  const count = {
    all: c.all,
    complete: c.complete,
    partial: c.partial,
    none: c.none,
    overdue: c.overdue,
    under_treatment: c.under_treatment,
  };
  return FILTERS.map(
    (f) =>
      `<button data-filter="${f.key}" class="q-btn${state.filter === f.key ? " is-active" : ""}">${f.label} &middot; ${count[f.key]}</button>`
  ).join("");
}

function cap(s) {
  return s ? s.charAt(0).toUpperCase() + s.slice(1) : s;
}

const VACC_VARIANT = {
  complete: "success",
  partial: "accent",
  none: "destructive",
};

function Row(r) {
  const v = VACC_TONE[r.vaccinationStatus];
  const due = daysUntil(r.nextCheckupDue);
  const dueStamp = due < 0 ? "stamp--coral" : due <= 14 ? "stamp--muted" : "stamp--accent";
  const initials = r.animalName.slice(0, 2).toUpperCase();
  const condVariant = r.condition === "Healthy" ? "success" : "destructive";
  return `
  <tr>
    <td class="table-cell">
      <span class="hr-cell-animal">
        <span class="hr-avatar">${esc(initials)}</span>
        <span>
          <span class="table-cell--strong"><a href="/admin/health-records/health-record.php?id=${esc(r.id)}">${esc(r.animalName)}</a></span><br>
          <span class="hr-id">${esc(r.id)}</span>
        </span>
      </span>
    </td>
    <td class="table-cell hr-species">${esc(cap(r.species))} · ${esc(cap(r.breedType))}</td>
    <td class="table-cell table-cell--center">${Badge({ text: v.label, variant: VACC_VARIANT[r.vaccinationStatus] })}</td>
    <td class="table-cell table-cell--mono table-cell--muted table-cell--center">${fmtDate(r.lastCheckupDate, "mono")}</td>
    <td class="table-cell table-cell--mono table-cell--muted table-cell--center"><span class="stamp stamp--sm ${dueStamp}">${fmtDate(r.nextCheckupDue, "mono")}</span></td>
    <td class="table-cell table-cell--center">${Badge({ text: esc(r.condition), variant: condVariant })}</td>
    <td class="table-cell table-cell--mono table-cell--muted table-cell--center">${fmtDate(r.updatedAt, "mono")}</td>
  </tr>`;
}

export function RecordsTable() {
  const { rows, total } = pagedRecords();
  if (rows.length === 0) {
    return `<div class="queue-empty"><div class="empty-state"><i data-lucide="clipboard-list"></i><span>No records match the current filters.</span></div></div>`;
  }
  const body = rows.map(Row).join("");
  const pagination =
    total > PAGE_SIZE
      ? `<div class="queue-pagination" id="hr-pagination">${PaginationBar({ total, perPage: PAGE_SIZE, page: state.page })}</div>`
      : "";
  return `
    <div class="table-wrap">
      <table class="table hr-table">
        <thead>
          <tr class="table-head">
            <th>Animal</th><th>Species / Breed</th><th class="table-cell--center">Vaccination</th><th class="table-cell--center">Last checkup</th>
            <th class="table-cell--center">Next due</th><th class="table-cell--center">Condition</th><th class="table-cell--center">Updated</th>
          </tr>
        </thead>
        <tbody>${body}</tbody>
      </table>
    </div>
    ${pagination}`;
}

export function RecordsPanel() {
  return `
  <div class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap">
        <i data-lucide="clipboard-list"></i>
        <h2 class="panel-title">Health records</h2>
      </div>
      <span class="stamp stamp--sm stamp--accent">${pagedRecords().total} in view</span>
    </div>
    <div class="panel-body" id="hr-records-body">${RecordsTable()}</div>
  </div>`;
}
