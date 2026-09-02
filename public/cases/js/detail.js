import { createIcons, icons } from "lucide";
import { hasPageSession, requireAuth, redirectToLogin } from "../../js/lib/api.js";
import { bootstrapPageAuth } from "../../js/lib/page-auth.js";
import { initResidentShell } from "../../js/components/resident-shell.js";
import { fetchCase } from "./api.js";
import { bindCaseActions } from "./actions.js";
import { bindProofForm } from "./proof.js";
import { renderDetail, renderDetailError, renderDetailLoading } from "./detail-render.js";

const el = (id) => document.getElementById(id);
const state = window.__PAGE_STATE__ || {};

function paint(html) {
  const root = el("case-detail-root");
  if (!root) return;
  root.innerHTML = html;
  createIcons({ icons });
}

function bindInteractive(item) {
  const root = el("case-detail-root");
  bindCaseActions(root, {
    onAccepted: () => loadCase(),
    onDeclined: () => {
      window.location.assign("/cases/");
    },
  });
  bindProofForm(root, {
    caseId: item.id,
    onUploaded: () => loadCase(),
  });
}

async function loadCase() {
  const id = String(state.caseId || "").trim();
  if (!id) {
    paint(renderDetailError("This case link is missing an id.", { missing: true }));
    return;
  }
  paint(renderDetailLoading());
  try {
    const item = await fetchCase(id);
    if (!item) {
      paint(renderDetailError("This case is not in your queue.", { missing: true }));
      return;
    }
    paint(renderDetail(item));
    bindInteractive(item);
  } catch (err) {
    if (err && err.status === 401 && !hasPageSession()) {
      redirectToLogin();
      return;
    }
    const missing = err && err.status === 404;
    paint(renderDetailError(err.message || "Please try again.", { missing }));
  }
}

function boot() {
  bootstrapPageAuth();
  initResidentShell();
  if (!requireAuth(["rescuer"])) return;
  createIcons({ icons });
  loadCase();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
