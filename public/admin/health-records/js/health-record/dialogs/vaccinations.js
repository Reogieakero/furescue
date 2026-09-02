import { createIcons, icons } from "lucide";
import { Button } from "/assets/js/components/ui/button.js";
import { initSelect } from "/assets/js/components/ui/select.js";
import { DatePicker, initDatePicker } from "/assets/js/components/ui/date-picker.js";
import { Spinner } from "/assets/js/components/ui/spinner.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { upsertAnimalVaccinations } from "/assets/js/admin/admin-data.js";
import { record, ui, paint, reloadRecord, syncHidden } from "../context.js";
import { vaccineOptionList, selectField, STATUS_OPTIONS } from "../util.js";
import { maybeNotifyAdoptionReady } from "../adoption-toast.js";
import { esc } from "../../health-records/components/util.js";

function toApiRecord(v) {
  return {
    vaccine: v.vaccine ?? null,
    administered_date: v.administered_date ?? v.dateGiven ?? v.date ?? null,
    next_due: v.next_due ?? v.nextDue ?? null,
    status: v.status ?? null,
    dose_number: v.dose_number ?? v.doseNumber ?? null,
    manufacturer: v.manufacturer ?? null,
    product_name: v.product_name ?? v.productName ?? null,
    batch_number: v.batch_number ?? v.batchNumber ?? null,
    route: v.route ?? null,
    notes: v.notes ?? null,
  };
}

function mapRecordToLegacyDetails(v) {
  const rec = toApiRecord(v);
  return {
    vaccine: rec.vaccine,
    dateGiven: rec.administered_date,
    nextDue: rec.next_due,
    status: rec.status,
    doseNumber: rec.dose_number,
    manufacturer: rec.manufacturer,
    productName: rec.product_name,
    batchNumber: rec.batch_number,
    route: rec.route,
    notes: rec.notes,
  };
}

export async function deleteSelectedVaccinations() {
  if (!record || !record.id) return;
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
    const remaining = (record.vaccinations || []).filter((_, i) => !removeIdx.has(i));
    const records = remaining.map(toApiRecord);
    const details = records.map(mapRecordToLegacyDetails);
    okBtn.disabled = true;
    okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Deleting…</span>`;
    try {
      await upsertAnimalVaccinations(record.id, records, details);
      toast("Vaccination record(s) deleted.", { type: "success" });
      ui.vaxSelecting = false;
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

export function openVaccinationDialog(editIdx = null) {
  const species = record && record.species;
  const editing = editIdx !== null && Array.isArray(record.vaccinations) && !!record.vaccinations[editIdx];
  const current = editing ? record.vaccinations[editIdx] : {};
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
      dose_number: Number.isNaN(doseNumber) ? ((record.vaccinations || []).length + 1) : doseNumber,
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
      if (editing && Array.isArray(record.vaccinations)) {
        records = record.vaccinations.map((v, i) => toApiRecord(i === editIdx ? entry : v));
      } else {
        records = [...(record.vaccinations || []).map(toApiRecord), entry];
      }
      const details = records.map(mapRecordToLegacyDetails);
      await upsertAnimalVaccinations(record.id, records, details);
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
