/**
 * Case-detail handoff: Register animal after resolve (Workstream E).
 *
 * Self-registering. Does not edit C's four files (actions / files / workflow / reports table).
 *
 * Mount — import from the case-detail entry (not C's files):
 *   import "./pages/case-detail/components/register-animal.js";
 * in public/admin/cases/js/case-detail.js
 *
 * Shows "Register animal" only when the case is resolved and no animal has
 * animals.case_id yet. If linked, shows that animal instead of the button.
 * The button goes to /admin/animals/?from_case= which prefills source=rescued_case.
 */
import { createIcons, icons } from "lucide";
import { Button } from "/js/components/ui/button.js";
import { apiFetchFull } from "/js/lib/api.js";
import { shortId } from "/admin/js/pages/dashboard/helpers.js";
import { state } from "../state.js";
import { esc } from "./util.js";

const STATUS_LABELS = {
  not_listed: "Not listed",
  available: "Available",
  pending: "Pending",
  adopted: "Adopted",
};

function currentCase() {
  if (state && state.caseData) return state.caseData;
  if (window.__PAGE_STATE__ && window.__PAGE_STATE__.caseData) return window.__PAGE_STATE__.caseData;
  return null;
}

async function findLinkedAnimal(caseId) {
  try {
    const payload = await apiFetchFull(`/animals?case_id=${encodeURIComponent(caseId)}&per_page=5`);
    const items = Array.isArray(payload.data) ? payload.data : [];
    return items[0] || null;
  } catch {
    return null;
  }
}

function handoffHtml(caseData, linked) {
  const caseId = caseData.id;
  const href = `/admin/animals/?from_case=${encodeURIComponent(caseId)}`;
  let body;
  if (linked) {
    const name = linked.name || "Unnamed";
    const status = STATUS_LABELS[linked.adoption_status] || "Not listed";
    const recordHref = `/admin/health-records/health-record.php?id=${encodeURIComponent(linked.id)}`;
    body = `
      <p class="page-sub">This resolved case is linked to <strong>${esc(name)}</strong>
        <span class="stamp stamp--sm stamp--muted">${esc(status)}</span>
      </p>
      <div class="cd-actions">
        ${Button({ text: "View animal", variant: "default", size: "sm", icon: "paw-print", href: recordHref })}
      </div>`;
  } else {
    body = `
      <p class="page-sub">Case ${esc(shortId(caseId))} is resolved. Register the rescued animal to continue intake. It will be saved as not listed and linked to this case.</p>
      <div class="cd-actions">
        ${Button({ text: "Register animal", variant: "default", size: "sm", icon: "plus", href })}
      </div>`;
  }
  return `
    <section class="panel case-detail-panel" data-register-animal data-case-id="${esc(caseId)}" data-linked="${esc(linked ? linked.id : "")}">
      <div class="panel-head">
        <div class="panel-title-wrap">
          <i data-lucide="paw-print"></i>
          <h2 class="panel-title">${linked ? "Registered animal" : "Register animal"}</h2>
        </div>
      </div>
      <div class="panel-body handoff-body">${body}</div>
    </section>`;
}

export async function mountRegisterAnimal(root = document.getElementById("app")) {
  if (!root || !root.querySelector(".case-detail-grid, .page-head")) return;
  const caseData = currentCase();
  const existing = root.querySelector("[data-register-animal]");
  if (!caseData || caseData.status !== "resolved") {
    if (existing) existing.remove();
    return;
  }

  const linked = await findLinkedAnimal(caseData.id);
  if (currentCase() !== caseData && currentCase() && currentCase().id !== caseData.id) return;

  const next = handoffHtml(caseData, linked);
  const slot = root.querySelector("[data-register-animal]");
  if (slot && slot.dataset.caseId === caseData.id && slot.dataset.linked === (linked ? linked.id : "")) {
    return;
  }
  if (slot) {
    slot.outerHTML = next;
  } else {
    const head = root.querySelector(".page-head");
    if (head) head.insertAdjacentHTML("afterend", next);
    else {
      const grid = root.querySelector(".case-detail-grid");
      if (grid) grid.insertAdjacentHTML("beforebegin", next);
    }
  }
  createIcons({ icons });
}

function watchCaseDetail() {
  const app = document.getElementById("app");
  if (!app || app.dataset.registerAnimalBound) return;
  app.dataset.registerAnimalBound = "1";
  mountRegisterAnimal(app);
  const obs = new MutationObserver(() => {
    if (!app.querySelector("[data-register-animal]")) mountRegisterAnimal(app);
  });
  obs.observe(app, { childList: true });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", watchCaseDetail);
} else {
  watchCaseDetail();
}
