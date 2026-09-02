import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/assets/js/lib/api.js";
import { bootstrapPageAuth } from "/assets/js/lib/page-auth.js";
import { initShell } from "/assets/js/admin/app-shell.js";
import { MessagesPage } from "./components.js";
import { state } from "./state.js";
import { initMessagesEvents, refreshThreads, startPolling } from "./workflow.js";
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

function initPageInteractions() {
  createIcons({ icons });
  initShell();
  initDropdownMenu(document);
  initDate();
  initMessagesEvents();
  void refreshThreads();
  startPolling();
}

document.addEventListener("DOMContentLoaded", () => {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    Object.assign(state, window.__PAGE_STATE__);
    state.me = getSessionUser() || state.user;
    const app = document.getElementById("app");
    if (app && !app.childElementCount) {
      app.innerHTML = MessagesPage(getSessionUser() || state.user);
    }
    initPageInteractions();
    return;
  }
  const user = requireAuth(["admin"]);
  if (!user) return;
  state.me = user;
  const app = document.getElementById("app");
  if (app) app.innerHTML = MessagesPage(user);
  initPageInteractions();
});
