import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { initDropdownMenu } from "../../js/components/ui/dropdown-menu.js";
import { state, loadCaseDetail, hydrateFromCache } from "./pages/case-detail/state.js";
import { CaseDetailPage, initCaseDetailEvents } from "./pages/case-detail/components.js";
import { loadCases } from "./pages/cases/state.js";
import { getCase } from "./pages/cases/components.js";

function getCaseId() {
  const params = new URLSearchParams(window.location.search);
  return params.get("id");
}

function renderNotFound() {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = `
    <div class="admin-shell">
      <main class="admin-main">
        <div class="empty-state" style="margin-top:48px">
          <i data-lucide="alert-circle"></i>
          <span>${""}</span>
          <a href="cases.html" class="cd-back"><i data-lucide="arrow-left"></i> Back to cases</a>
        </div>
      </main>
    </div>`;
  createIcons({ icons });
}

function render(caseData, { loading = false } = {}) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = CaseDetailPage(caseData, { loading });
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  if (loading) return;
  initCaseDetailEvents();
}

async function bootstrap() {
  const user = requireAuth(["admin"]);
  if (!user) return;

  const caseId = getCaseId();
  if (!caseId) {
    alert("No case specified.");
    return;
  }

  const cached = hydrateFromCache(caseId);
  render(cached ? state.caseData : null, { loading: !cached });

  await loadCases();
  const existing = getCase(caseId);
  if (!existing) {
    alert("Case not found.");
    return;
  }

  await loadCaseDetail(caseId);

  if (!state.caseData) {
    renderNotFound();
    return;
  }

  render(state.caseData, { loading: false });
}

document.addEventListener("DOMContentLoaded", bootstrap);
