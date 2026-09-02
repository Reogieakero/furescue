import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { CasesPage, rerenderAll, initCaseSort, initCaseMapMode, renderCaseMap, renderStatusBreakdown, renderCaseList } from "./components.js";
import { state, loadCases, loadFilterPref, hydrateFromCache, applyUrlQuery } from "./state.js";
import { initCasesEvents } from "./workflow.js";
import { initDropdownMenu } from "/assets/js/components/ui/dropdown-menu.js";

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
  applyUrlQuery();
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
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    const serverFilter = window.__PAGE_STATE__.filter || "in_progress";
    Object.assign(state, window.__PAGE_STATE__);
    loadFilterPref();
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = CasesPage(getSessionUser(), { loading: false });
    }
    const fromSearch = applyUrlQuery();
    const tabs = document.getElementById("case-tabs");
    if (tabs) {
      tabs.querySelectorAll("[data-filter]").forEach((b) => b.classList.toggle("is-active", b.dataset.filter === state.filter));
    }
    if (fromSearch || state.filter !== serverFilter) renderCaseList();
    initShell();
    initDropdownMenu(document);
    initCasesEvents();
    initCaseSort();
    initCaseMapMode();
    initDate();
    renderCaseMap();
    renderStatusBreakdown();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  const cached = hydrateFromCache();
  render(user, { loading: !cached });
  loadCases().finally(() => render(user, { loading: false }));
});
