import { createIcons, icons } from "lucide";
import { Button } from "/js/components/ui/button.js";
import { initSelect } from "/js/components/ui/select.js";
import { Spinner } from "/js/components/ui/spinner.js";
import { toast } from "/js/components/ui/toast.js";
import { addAnimalVital, upsertAnimalMedical } from "/admin/js/lib/admin-data.js";
import { record, reloadRecord, syncHidden } from "../context.js";
import { selectField, VITAL_OPTIONS } from "../util.js";
import { maybeNotifyAdoptionReady } from "../adoption-toast.js";

export async function openVitalDialog(prefill = null) {
  const initial = prefill || "Weight";
  const current = (record.vitals || []).find((v) => v.label === initial);
  const initialOpt = VITAL_OPTIONS.find((o) => o.value === initial) || VITAL_OPTIONS[0];
  const overlay = document.createElement("div");
  overlay.className = "dialog-overlay";
  overlay.innerHTML = `
    <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="vital-title">
      <div class="dialog-head">
        <div class="dialog-title-wrap">
          <i data-lucide="heart-pulse" class="dialog-icon"></i>
          <h3 class="dialog-title" id="vital-title">${prefill ? "Edit Vital Sign" : "Add Vital Sign"}</h3>
        </div>
        <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
      </div>
      <div class="dialog-body">
        <div class="hr-form-row">
          <label class="dialog-label">Vital${selectField({ id: "vital-type", name: "vital", options: VITAL_OPTIONS, value: initial, placeholder: "Select vital…" })}</label>
          <label class="dialog-label">Value<input class="hr-input" id="vital-value" type="number" step="any" placeholder="0" value="${current && current.value != null ? current.value : ""}"></label>
          <label class="dialog-label">Unit<span id="vital-unit-display" class="hr-input hr-input--readonly">${initialOpt.unit}</span></label>
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
        await upsertAnimalMedical(record.id, { weight_kg: value });
      } else if (label === "Body Temperature") {
        await upsertAnimalMedical(record.id, { temperature_c: value });
      } else {
        await addAnimalVital(record.id, { heart_rate_bpm: value });
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
