import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { RescuersPage } from "./components.js";
import { loadRescuers, hydrateFromCache, hydrateSelection, state } from "./state.js";
import { initRescuerEvents, restoreSelection } from "./workflow.js";
import { initDropdownMenu } from "/assets/js/components/ui/dropdown-menu.js";
import { setNavBadge } from "/assets/js/lib/swr.js";

function render(user, { loading = false } = {}) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = RescuersPage(user, { loading });
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  if (loading) return;
  initRescuerEvents();
}

function boot() {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    setNavBadge("rescuers", state.pending.length);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = RescuersPage(getSessionUser(), { loading: false });
    }
    hydrateSelection();
    if (state.selectedId) {
      document
        .querySelectorAll("#rescuer-table tr[data-id]")
        .forEach((tr) => tr.classList.toggle("is-selected", tr.dataset.id === state.selectedId));
    }
    createIcons({ icons });
    initShell();
    initDropdownMenu(document);
    initRescuerEvents();
    restoreSelection();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  const cached = hydrateFromCache();
  hydrateSelection();
  render(user, { loading: !cached });
  loadRescuers().finally(() => {
    render(user, { loading: false });
    restoreSelection();
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
