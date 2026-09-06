import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { UsersPage } from "./components.js";
import { state, loadUsers } from "./state.js";
import { initUsersEvents } from "./workflow.js";
import { initDropdownMenu } from "/shared/components/dropdown-menu/dropdown-menu.js";

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
  initUsersEvents();
  initDate();
}

function render(user) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = UsersPage(user);
  initPageInteractions();
}

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    if (!Array.isArray(state.users)) state.users = [];
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = UsersPage(getSessionUser());
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  state.user = user;
  render(user);
  loadUsers().finally(() => render(user));
});
