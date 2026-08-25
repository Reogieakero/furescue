import { createIcons, icons } from "lucide";
import { requireAuth, getSessionUser } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
import { initShell } from "/admin/js/layout/app-shell.js";
import { MessagesPage } from "./pages/messages/components.js";
import { state } from "./pages/messages/state.js";
import { initMessagesEvents, refreshThreads, startPolling } from "./pages/messages/workflow.js";
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
