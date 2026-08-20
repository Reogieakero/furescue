import { createIcons, icons } from "lucide";
import { apiFetch } from "../../../../../js/lib/api.js";
import * as api from "../../../lib/admin-data.js";
import { toast } from "../../../../../js/components/ui/toast.js";
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

export function initCaseDetailEvents() {
  if (!state.caseData) return;
  const root = document.getElementById("app");
  if (!root) return;
  const triggers = root.querySelectorAll("[data-cd-action]");
  if (!triggers.length) return;
  triggers.forEach((btn) => {
    btn.addEventListener("click", async () => {
      const action = btn.dataset.cdAction;
      if (action === "resolve") await resolveCase(state.caseData.id, state.caseData);
      if (action === "assign") await assignRescuer(state.caseData.id, state.caseData, state.rescuers);
      if (action === "location") renderLocation(state.caseData);
      if (action === "add-proof") {
        const input = document.getElementById("cd-proof-input");
        const url = input && input.value.trim();
        if (!url) {
          toast("Enter a proof photo URL.", { type: "error" });
          return;
        }
        const exists = (state.caseData.proof || []).some((p) => p.url === url);
        if (exists) {
          toast("That photo is already added.", { type: "error" });
          return;
        }
        try {
          await apiFetch(`/cases/${state.caseData.id}/proof`, {
            method: "POST",
            body: { url },
          });
          toast("Proof photo added.");
          await loadAndRemount();
        } catch (e) {
          toast(e.message || "Failed to add proof.", { type: "error" });
        }
      }
    });
  });
  createIcons({ icons });
}

async function loadAndRemount() {
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

async function assignRescuer(caseId, caseData, rescuers) {
  try {
    await api.assignRescuer(caseId, caseData.assigned_rescuer_id || rescuers[0]?.id);
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
