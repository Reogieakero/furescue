import { createIcons, icons } from "lucide";
import { requireAuth } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
import { initShell } from "/admin/js/layout/app-shell.js";
import { HealthRecordsPage, rerenderAll } from "./pages/health-records/components.js";
import { initHealthRecordsEvents } from "./pages/health-records/workflow.js";
import { initDropdownMenu } from "/js/components/ui/dropdown-menu.js";
import { initAnimalsFlyout } from "./pages/health-records/components/animals-flyout.js";
import { state, loadHealthRecords, loadHealthActivity, allAttentionCount } from "./pages/health-records/state.js";
import { mountCharts } from "./pages/health-records/components/charts.js";
import { setNavBadge } from "/js/lib/swr.js";

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
  app.innerHTML = HealthRecordsPage(user);
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initHealthRecordsEvents();
  initAnimalsFlyout();
  initDate();
  rerenderAll();
}

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      const user = requireAuth(["admin"]);
      if (!user) return;
      render(user);
      return;
    }
    // Server-rendered shell: skip initial render, wire interactivity only.
    setNavBadge("health", allAttentionCount());
    createIcons({ icons });
    initShell();
    initDropdownMenu(document);
    initHealthRecordsEvents();
    initAnimalsFlyout();
    initDate();
    mountCharts();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user);
  Promise.all([loadHealthRecords(), loadHealthActivity()])
    .catch(() => {})
    .finally(() => render(user));
});
