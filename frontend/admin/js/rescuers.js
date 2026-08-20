import { createIcons, icons } from "lucide";
import { requireAuth } from "../../js/lib/api.js";
import { initShell } from "./layout/app-shell.js";
import { RescuersPage } from "./pages/rescuers/components.js";
import { loadRescuers, hydrateFromCache } from "./pages/rescuers/state.js";
import { initRescuerEvents } from "./pages/rescuers/workflow.js";
import { initDropdownMenu } from "../../js/components/ui/dropdown-menu.js";

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

document.addEventListener("DOMContentLoaded", () => {
  const user = requireAuth(["admin"]);
  if (!user) return;
  const cached = hydrateFromCache();
  render(user, { loading: !cached });
  loadRescuers().finally(() => render(user, { loading: false }));
});
