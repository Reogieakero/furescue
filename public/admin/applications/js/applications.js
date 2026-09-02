import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { ApplicationsPage } from "./components.js";
import { state, loadAdoptions } from "./state.js";
import { initApplicationEvents } from "./workflow.js";
import { initDropdownMenu } from "/assets/js/components/ui/dropdown-menu.js";
import { setNavBadge } from "/assets/js/lib/swr.js";
import { applicationCounts } from "./components/kpis.js";

function initDate() {
  const el = document.getElementById("admin-date");
  if (!el) return;
  el.textContent = new Date().toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
}

function initPageInteractions() {
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initApplicationEvents();
  initDate();
}

function render(user, { loading = false } = {}) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = ApplicationsPage(user, { loading });
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  if (loading) return;
  initApplicationEvents();
  initDate();
}

function boot() {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    if (!Array.isArray(state.items)) state.items = [];
    setNavBadge("applications", applicationCounts().pending);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = ApplicationsPage(getSessionUser(), { loading: false });
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user, { loading: true });
  loadAdoptions()
    .catch(() => {})
    .finally(() => render(user, { loading: false }));
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
