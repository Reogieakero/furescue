import { createIcons, icons } from "lucide";
import { toast } from "../../../../../js/components/ui/toast.js";
import { confirmDialog } from "../../../../../js/components/ui/dialog.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../../js/components/ui/select.js";
import { Spinner } from "../../../../../js/components/ui/spinner.js";
import * as api from "../../../lib/admin-data.js";
import { state, reloadData } from "../state.js";
import { rerenderAll } from "../components.js";
import { shortId, titleCase } from "../../dashboard/helpers.js";
import { report } from "./helpers.js";

async function runVerify(id) {
  const r = report(id);
  const ok = await confirmDialog({
    title: "Verify report",
    message: `Are you sure you want to verify ${shortId(id)}?`,
    info: [
      { label: "Case", value: shortId(id) },
      { label: "Barangay", value: r && r.address_text ? titleCase(r.address_text) : "—" },
      { label: "Reporter", value: shortId(r.resident_id) },
    ],
    confirmText: "Verify",
    cancelText: "Cancel",
    run: () => api.verifyReport(id),
  });
  if (!ok) return;
  const caseId = ok.data && ok.data.case_id;
  toast(caseId ? `Report ${shortId(id)} verified · Case ${shortId(caseId)} created.` : `Report ${shortId(id)} verified.`, {
    type: "success",
  });
  await reloadData();
  rerenderAll();
  createIcons({ icons });
}

async function runDismiss(id) {
  const r = report(id);
  const ok = await confirmDialog({
    title: "Dismiss report",
    message: `Are you sure you want to dismiss ${shortId(id)}?`,
    info: [
      { label: "Case", value: shortId(id) },
      { label: "Barangay", value: r && r.address_text ? titleCase(r.address_text) : "—" },
    ],
    confirmText: "Dismiss",
    cancelText: "Cancel",
    danger: true,
    withReason: true,
    reasonLabel: "Dismiss reason",
    reasonRequired: true,
    run: ({ reason }) => api.dismissReport(id, reason),
  });
  if (!ok) return;
  toast(`Report ${shortId(id)} dismissed.`, { type: "success" });
  await reloadData();
  rerenderAll();
  createIcons({ icons });
}

function assignDialog(caseId, reportId) {
  return new Promise((resolve) => {
    const rescuers = state.rescuers.filter(
      (u) => u.role === "rescuer" && u.account_status === "active" && (u.duty_status || "off_duty") === "on_duty"
    );
    const options = rescuers.map((u) => ({ value: u.id, label: u.full_name || "Unnamed rescuer" }));

    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="assign-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="user-plus" class="dialog-icon"></i>
            <h3 class="dialog-title" id="assign-title">Assign rescuer</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          <p class="dialog-message">Assign a rescuer to case ${shortId(caseId)} (report ${shortId(reportId)}). Only on-duty rescuers can be assigned.</p>
          ${options.length
            ? `<label class="dialog-label" for="assign-rescuer">Rescuer<span class="dialog-req"> *</span></label>
               ${Select({ id: "assign-rescuer", options, placeholder: "Select a rescuer…", className: "w-full" })}`
            : `<div class="empty-state"><i data-lucide="siren"></i><span>No on-duty rescuers available.</span></div>`}
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
          ${Button({ text: "Assign", variant: "default", attrs: 'data-act="ok"', className: options.length ? "" : "hidden" })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });
    let selected = "";
    initSelect(overlay, { "assign-rescuer": (val) => { selected = val; } });
    if (options.length) {
      const trigger = overlay.querySelector("#assign-rescuer [data-select-value]");
      if (trigger) trigger.textContent = "";
    }

    const close = () => {
      overlay.remove();
      resolve(null);
    };

    const submit = async () => {
      if (!selected) {
        toast("Please select a rescuer.", { type: "error" });
        return;
      }
      const okBtn = overlay.querySelector('[data-act="ok"]');
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Assign</span>`;
      createIcons({ icons });
      try {
        const payload = await api.assignRescuer(caseId, selected);
        const name = rescuers.find((u) => u.id === selected);
        overlay.remove();
        resolve(payload);
        toast(`Case ${shortId(caseId)} assigned to ${(name && name.full_name) || "rescuer"}.`, { type: "success" });
      } catch (err) {
        okBtn.disabled = false;
        okBtn.innerHTML = `<span>Assign</span>`;
        toast(err && err.message ? err.message : "Assign failed.", { type: "error" });
      }
    };

    overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
    overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
    overlay.querySelector(".dialog-x").addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
  });
}

export { runVerify, runDismiss, assignDialog };
