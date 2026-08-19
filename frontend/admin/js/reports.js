// Admin — Reports page entry.
import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { ReportsPage, attachReportTooltips, initReportSort } from "./pages/reports/components.js";
import { loadReports } from "./pages/reports/state.js";
import { initReportsEvents } from "./pages/reports/workflow.js";
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
  app.innerHTML = ReportsPage(user);
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initReportsEvents();
  initReportSort();
  initDate();
  attachReportTooltips();
}

document.addEventListener("DOMContentLoaded", () => {
  const user = requireAuth(["admin"]);
  if (!user) return;
  loadReports().finally(() => render(user));
});