import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { AnimalsPage, rerenderAll } from "./pages/animals/components.js";
import { initAnimalsEvents } from "./pages/animals/workflow.js";
import { loadAnimals, restoreSelectedId, state } from "./pages/animals/state.js";
import { renderSelection } from "./pages/animals/components/grid.js";
import { renderDetail } from "./pages/animals/components/side.js";
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
  app.innerHTML = AnimalsPage(user);
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initAnimalsEvents();
  initDate();
  rerenderAll();
}

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    Object.assign(state, window.__PAGE_STATE__);
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = AnimalsPage(getSessionUser());
    }
    createIcons({ icons });
    initShell();
    initDropdownMenu(document);
    initAnimalsEvents();
    initDate();
    restoreSelectedId();
    if (state.selectedId) {
      renderSelection();
      renderDetail();
    }
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  render(user);
  loadAnimals()
    .catch(() => {})
    .finally(() => render(user));
});
