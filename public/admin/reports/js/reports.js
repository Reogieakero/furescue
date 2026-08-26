import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
import { initShell } from "/admin/js/layout/app-shell.js";
import { ReportsPage, attachReportTooltips, initReportSort } from "./pages/reports/components.js";
import { applyPageState, state, loadReports } from "./pages/reports/state.js";
import { initReportsEvents } from "./pages/reports/workflow.js";
import { initDropdownMenu } from "/js/components/ui/dropdown-menu.js";

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
  app.innerHTML = ReportsPage(user, { loading });
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  if (loading) return;
  initReportsEvents();
  initReportSort();
  initDate();
  attachReportTooltips();
}

function initPageInteractions() {
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initReportsEvents();
  initReportSort();
  initDate();
  attachReportTooltips();
}

function seedFromPageState() {
  if (typeof window === "undefined" || !window.__PAGE_STATE__) return false;
  bootstrapPageAuth();
  Object.assign(state, window.__PAGE_STATE__);
  applyPageState();
  return true;
}

seedFromPageState();

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    seedFromPageState();
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = ReportsPage(getSessionUser(), { loading: false });
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user, { loading: true });
  loadReports().finally(() => render(user, { loading: false }));
});
