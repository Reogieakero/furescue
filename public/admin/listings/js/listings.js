import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
import { initShell } from "/admin/js/layout/app-shell.js";
import { ListingsPage } from "./pages/listings/components.js";
import { state, loadListings } from "./pages/listings/state.js";
import { uniqueListingsByAnimal } from "./pages/listings/unique.js";
import { initListingsEvents } from "./pages/listings/workflow.js";
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

function initPageInteractions() {
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initListingsEvents();
  initDate();
}

function render(user) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = ListingsPage(user);
  initPageInteractions();
}

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    if (!Array.isArray(state.listings)) state.listings = [];
    else state.listings = uniqueListingsByAnimal(state.listings);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = ListingsPage(getSessionUser());
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user);
  loadListings().finally(() => render(user));
});
