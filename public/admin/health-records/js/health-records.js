import { createIcons, icons } from "lucide";
import { requireAuth } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { HealthRecordsPage, rerenderAll } from "./health-records/components.js";
import { initHealthRecordsEvents } from "./health-records/workflow.js";
import { initDropdownMenu } from "/assets/js/components/ui/dropdown-menu.js";
import { initAnimalsFlyout } from "./health-records/components/animals-flyout.js";
import { state, loadHealthRecords, loadHealthActivity, allAttentionCount } from "./health-records/state.js";
import { mountCharts } from "./health-records/components/charts.js";
import { setNavBadge } from "/assets/js/lib/swr.js";

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
