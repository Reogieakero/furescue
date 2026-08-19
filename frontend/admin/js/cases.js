// Admin — Cases page entry.
import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { CasesPage, rerenderAll, initCaseSort, initCaseMapMode, renderCaseMap, renderStatusBreakdown } from "./pages/cases/components.js";
import { loadCases, loadFilterPref } from "./pages/cases/state.js";
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

function render(user) {
  const app = document.getElementById("app");
  if (!app) return;
  loadFilterPref();
  app.innerHTML = CasesPage(user);
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
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
  loadCases().finally(() => render(user));
});
