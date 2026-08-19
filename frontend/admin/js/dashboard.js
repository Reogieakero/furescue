// Admin Command Center — dashboard entry.
import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { DashboardPage, ActivityInner } from "./pages/dashboard/components.js";
import { loadDashboard, state } from "./pages/dashboard/state.js";
import { createCarousel } from "./pages/dashboard/carousel.js";
import { initQueueTabs, initQueuePagination, initQueueActions } from "./pages/dashboard/queue.js";
import { initCaseDensityMap } from "./pages/dashboard/map.js";
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

function initActivityPagination() {
  const wrap = document.getElementById("activity-table");
  if (!wrap) return;
  wrap.addEventListener("click", (e) => {
    const btn = e.target.closest("button[data-page]");
    if (!btn || btn.getAttribute("aria-disabled") === "true") return;
    const page = parseInt(btn.dataset.page, 10);
    if (!page || page === state.activityPage) return;
    state.activityPage = page;
    wrap.innerHTML = ActivityInner();
    createIcons({ icons });
  });
}

function render(user) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = DashboardPage(user);
  createIcons({ icons });
  initShell();
  initQueueTabs();
  initQueuePagination();
  initQueueActions();
  initDropdownMenu(document);
  createCarousel(document.querySelector(".health-carousel"));
  createCarousel(document.querySelector(".elearn-card"));
  initCaseDensityMap(state.heatmap);
  initActivityPagination();
  initDate();
}

document.addEventListener("DOMContentLoaded", () => {
  const user = requireAuth(["admin"]);
  if (!user) return;
  loadDashboard().finally(() => render(user));
});