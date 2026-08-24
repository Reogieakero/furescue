import { AppShell } from "/admin/js/layout/app-shell.js";
import { emptyState } from "./util.js";
import { PageHead } from "./components/page-head.js";
import { ProfilePanel } from "./components/profile.js";
import { OverviewPanel } from "./components/overview.js";
import { HistoryPanel } from "./components/history.js";
import { VaccinationPanel } from "./components/vaccinations.js";
import { RemindersPanel } from "./components/reminders.js";
import { VitalsPanel } from "./components/vitals.js";
import { DocumentsPanel } from "./components/documents.js";
import { StatsPanel } from "./components/stats.js";
import { user, record, ui, setSession, setPageHtml, setAfterReload, paint } from "./context.js";
import { handleAction } from "./actions.js";
import { restoreAdoptionToast, maybeNotifyFromRecord } from "./adoption-toast.js";

export function HealthRecordPage(nextUser, nextRecord, nextUi = {}) {
  const r = nextRecord || {};
  const hasRecord = !!r.hasMedicalRecord;
  const editing = !!nextUi.editing;
  const openForm = nextUi.openForm || null;
  return AppShell({
    user: nextUser,
    activeNav: "health records",
    children: [
      PageHead(hasRecord, editing, nextUi.mode),
      `<div class="hr-grid">${ProfilePanel(r, { editing })}${OverviewPanel(r.overview, r.vaccinations, { editing })}</div>`,
      `<div class="hr-trio">
        ${HistoryPanel(r.history)}
        <div class="hr-trio-col">${VaccinationPanel(r.vaccinations, {
          editing,
          mode: nextUi.mode,
          selecting: nextUi.vaxSelecting,
        })}${RemindersPanel(r.reminders)}</div>
        <div class="hr-trio-col">${VitalsPanel(r.vitals, r.vitalMeta, {
          editing,
          mode: nextUi.mode,
          openForm: openForm === "vital",
        })}${DocumentsPanel(r.documents, { editing, mode: nextUi.mode })}</div>
      </div>
      ${StatsPanel(r)}`,
    ].join(""),
  });
}

export function HealthRecordLoading(nextUser) {
  return AppShell({
    user: nextUser,
    activeNav: "health records",
    children: emptyState("Loading health record…", "loader"),
  });
}

export function HealthRecordError(nextUser, message) {
  return AppShell({
    user: nextUser,
    activeNav: "health records",
    children: emptyState(message || "Could not load this health record.", "alert-triangle"),
  });
}

export function HealthRecordEmpty(nextUser) {
  return AppShell({
    user: nextUser,
    activeNav: "health records",
    children: emptyState("No health record found for this animal.", "search"),
  });
}

setPageHtml(() => HealthRecordPage(user, record, ui));
setAfterReload(maybeNotifyFromRecord);

export function initHealthRecordEvents() {
  const app = document.getElementById("app");
  if (!app || app.dataset.hrBound) return;
  app.dataset.hrBound = "1";
  app.addEventListener("click", (e) => {
    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      handleAction(actionEl);
    }
  });
}

export function hydrateHealthRecord(nextUser, nextRecord) {
  setSession(nextUser, nextRecord || null, {
    editing: false,
    mode: null,
    openForm: null,
    saving: false,
    vaxSelecting: false,
    addedVaccination: false,
    addedVital: false,
  });
  initHealthRecordEvents();
  restoreAdoptionToast();
  maybeNotifyFromRecord();
}

export function renderHealthRecord(nextUser, nextRecord) {
  setSession(nextUser, nextRecord || null, {
    editing: false,
    mode: null,
    openForm: null,
    saving: false,
    vaxSelecting: false,
    addedVaccination: false,
    addedVital: false,
  });
  paint();
  initHealthRecordEvents();
  restoreAdoptionToast();
  maybeNotifyFromRecord();
}
