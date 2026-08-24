import { createIcons, icons } from "lucide";
import { apiFetch } from "/js/lib/api.js";
import * as api from "/admin/js/lib/admin-data.js";
import { toast } from "/js/components/ui/toast.js";
import { Button } from "/js/components/ui/button.js";
import { Select, initSelect } from "/js/components/ui/select.js";
import { Spinner } from "/js/components/ui/spinner.js";
import { shortId, titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { state, loadCaseDetail } from "../state.js";
import { CaseDetailPage } from "./actions.js";
import { renderLocation } from "./location.js";

export function mountCaseDetail() {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = CaseDetailPage(state.caseData);
  initCaseDetailEvents();
  createIcons({ icons });
}

function proofFormHtml() {
  return `<div class="cd-proof-add">
        <input id="cd-proof-input" class="cd-proof-input" type="url" placeholder="Paste proof photo URL…">
        ${Button({ text: "Add", variant: "outline", size: "sm", icon: "plus", attrs: 'data-cd-action="add-proof"' })}
      </div>`;
}

function ensureProofForm(root) {
  if (root.querySelector("#cd-proof-input")) return;
  const panel = root.querySelector(".cd-col-rescuer .panel-body")
    || Array.from(root.querySelectorAll(".case-detail-panel, .panel")).find((el) => {
      const title = el.querySelector(".panel-title");
      return title && /rescue proof/i.test(title.textContent || "");
    })?.querySelector(".panel-body");
  if (!panel) return;
  panel.insertAdjacentHTML("beforeend", proofFormHtml());
}

function caseId() {
  return (state.caseData && state.caseData.id) || state.caseId
    || new URLSearchParams(window.location.search).get("id");
}

async function addProofPhoto() {
  const input = document.getElementById("cd-proof-input");
  const url = input && input.value.trim();
  if (!url) {
    toast("Enter a proof photo URL.", { type: "error" });
    return;
  }
  const id = caseId();
  if (!id) {
    toast("No case specified.", { type: "error" });
    return;
  }
  const exists = (state.proof || []).some((p) => p === url);
  if (exists) {
    toast("That photo is already added.", { type: "error" });
    return;
  }
  try {
    await apiFetch(`/cases/${id}/proof`, {
      method: "POST",
      body: { url },
    });
    toast("Proof photo added.");
    await loadAndRemount();
  } catch (e) {
    toast(e.message || "Failed to add proof.", { type: "error" });
  }
}

export function initCaseDetailEvents() {
  if (!state.caseData && window.__PAGE_STATE__ && window.__PAGE_STATE__.caseData) {
    Object.assign(state, window.__PAGE_STATE__);
  }
  const root = document.getElementById("app");
  if (!root) return;
  ensureProofForm(root);
  if (!root.dataset.cdEventsBound) {
    root.dataset.cdEventsBound = "1";
    root.addEventListener("click", async (e) => {
      const btn = e.target.closest("[data-cd-action]");
      if (!btn || !root.contains(btn)) return;
      const action = btn.dataset.cdAction;
      const id = caseId();
      if (action === "resolve" && id) await resolveCase(id, state.caseData);
      if (action === "assign" && id) await assignCase(id, state.caseData && state.caseData.report_id);
      if (action === "location") renderLocation(state.caseData || {});
      if (action === "add-proof") await addProofPhoto();
    });
  }
  createIcons({ icons });
}

async function loadAndRemount() {
  if (window.__PAGE_STATE__) {
    window.location.reload();
    return;
  }
  await loadCaseDetail(state.caseId);
  mountCaseDetail();
}

async function resolveCase(caseId, caseData) {
  try {
    await api.updateCaseStatus(caseId, "resolved");
    toast("Case resolved.");
    await loadAndRemount();
  } catch (e) {
    toast(e.message || "Failed to resolve case.", { type: "error" });
  }
}

async function openAssignDialog(caseId, reportId) {
  let rescuers = [];
  try {
    const { items } = await api.fetchRescuers();
    rescuers = items || [];
  } catch {
    rescuers = [];
  }
  const onDuty = rescuers.filter(
    (u) => u.role === "rescuer" && u.account_status === "active" && (u.duty_status || "off_duty") === "on_duty"
  );
  const options = onDuty.map((u) => ({ value: u.id, label: u.full_name || "Unnamed rescuer" }));

  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="cd-assign-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="user-plus" class="dialog-icon"></i>
            <h3 class="dialog-title" id="cd-assign-title">Assign rescuer</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          <p class="dialog-message">Assign a rescuer to case ${shortId(caseId)}${reportId ? ` (report ${shortId(reportId)})` : ""}. Only on-duty rescuers can be assigned.</p>
          ${options.length
            ? `<label class="dialog-label" for="cd-assign-rescuer">Rescuer<span class="dialog-req"> *</span></label>
               ${Select({ id: "cd-assign-rescuer", options, placeholder: "Select a rescuer…", className: "w-full" })}`
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
    initSelect(overlay, { "cd-assign-rescuer": (val) => { selected = val; } });
    if (options.length) {
      const trigger = overlay.querySelector("#cd-assign-rescuer [data-select-value]");
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
        const name = onDuty.find((u) => u.id === selected);
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

async function assignCase(caseId, reportId) {
  try {
    const payload = await openAssignDialog(caseId, reportId);
    if (!payload) return;
    toast("Rescuer assigned.");
    await loadAndRemount();
  } catch (e) {
    toast(e.message || "Failed to assign rescuer.", { type: "error" });
  }
}

export function initCaseDetail() {
  if (!state.caseData) return;
  mountCaseDetail();
  createIcons({ icons });
}
