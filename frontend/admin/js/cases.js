import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { CasesPage, rerenderAll, initCaseSort, initCaseMapMode, renderCaseMap, renderStatusBreakdown } from "./pages/cases/components.js";
import { loadCases, loadFilterPref, hydrateFromCache } from "./pages/cases/state.js";
import { initCasesEvents } from "./pages/cases/workflow.js";
import { initDropdownMenu } from "../../js/components/ui/dropdown-menu.js";

function initDate() {
  const el = document.getElementById("admin-date");
  if (!el) return;
  el.textContent = new Date().toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
}

function render(user, { loading = false } = {}) {
  const app = document.getElementById("app");
  if (!app) return;
  loadFilterPref();
  app.innerHTML = CasesPage(user, { loading });
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  if (loading) return;
  initCasesEvents();
  initCaseSort();
  initCaseMapMode();
  initDate();
  renderCaseMap();
  renderStatusBreakdown();
}

document.addEventListener("DOMContentLoaded", () => {
  const user = requireAuth(["admin"]);
  if (!user) return;
  const cached = hydrateFromCache();
  render(user, { loading: !cached });
  loadCases().finally(() => render(user, { loading: false }));
});
