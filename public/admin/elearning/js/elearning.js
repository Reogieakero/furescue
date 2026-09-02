import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { initDropdownMenu } from "/assets/js/components/ui/dropdown-menu.js";
import { ElearningPage } from "./components.js";
import { hydrateModules, loadModules, state } from "./state.js";
import { initElearningEvents } from "./workflow.js";
import { toast } from "/assets/js/components/ui/toast.js";

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
  initElearningEvents();
  initDate();
}

function render(user, { loading = false } = {}) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = ElearningPage(user, { loading });
  if (loading) {
    createIcons({ icons });
    initShell();
    return;
  }
  initPageInteractions();
}

function boot() {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    hydrateModules(state.modules);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = ElearningPage(getSessionUser(), { loading: false });
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user, { loading: true });
  loadModules()
    .catch((err) => {
      toast(err.message || "Could not load modules.", { type: "error" });
    })
    .finally(() => render(user, { loading: false }));
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
