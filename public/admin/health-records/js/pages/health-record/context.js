import { createIcons, icons } from "lucide";
import { initShell } from "/admin/js/layout/app-shell.js";
import { initDatePicker } from "/js/components/ui/date-picker.js";
import { initSelect } from "/js/components/ui/select.js";
import { fetchAnimalHealthRecord } from "/admin/js/lib/admin-data.js";

export let user = null;
export let record = null;
export let ui = {
  editing: false,
  mode: null,
  openForm: null,
  saving: false,
  vaxSelecting: false,
  addedVaccination: false,
  addedVital: false,
};

export function setSession(nextUser, nextRecord, nextUi) {
  user = nextUser;
  record = nextRecord || null;
  if (nextUi) Object.assign(ui, nextUi);
}

export function setRecord(next) {
  record = next;
}

export function syncHidden(id, val) {
  const el = document.getElementById(`${id}-value`);
  if (el) el.value = val;
}

let pageHtml = () => "";
let afterReload = () => {};

export function setPageHtml(fn) {
  pageHtml = fn;
}

export function setAfterReload(fn) {
  afterReload = fn;
}

export function paint() {
  const app = document.getElementById("app");
  if (!app) return;
  try {
    app.innerHTML = pageHtml();
  } catch (err) {
    console.error("health-record paint failed", err);
    return;
  }
  createIcons({ icons });
  initShell();
  initDatePicker(app);
  initSelect(app, {
    "hr-adoption-status": (val) => syncHidden("hr-adoption-status", val),
    "hr-deworming": (val) => syncHidden("hr-deworming", val),
    "hr-neutered": (val) => syncHidden("hr-neutered", val),
  });
}

export async function reloadRecord() {
  const id = record && record.id;
  if (!id) return;
  const fresh = await fetchAnimalHealthRecord(id);
  if (fresh) record = fresh;
  paint();
  afterReload();
}
