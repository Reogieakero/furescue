import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { HealthRecordsPage, rerenderAll } from "./pages/health-records/components.js";
import { initHealthRecordsEvents } from "./pages/health-records/workflow.js";
import { initDropdownMenu } from "../../js/components/ui/dropdown-menu.js";
import { initAnimalsFlyout } from "./pages/health-records/components/animals-flyout.js";
import { loadHealthRecords, loadHealthActivity } from "./pages/health-records/state.js";

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
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user);
  Promise.all([loadHealthRecords(), loadHealthActivity()])
    .catch(() => {})
    .finally(() => render(user));
});
