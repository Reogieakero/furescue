import { createIcons, icons } from "lucide";
import { requireAuth } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
import { initShell } from "/admin/js/layout/app-shell.js";
import { initDropdownMenu } from "/js/components/ui/dropdown-menu.js";
import { fetchAnimalHealthRecord } from "/admin/js/lib/admin-data.js";
import {
  HealthRecordPage,
  HealthRecordLoading,
  HealthRecordError,
  HealthRecordEmpty,
  hydrateHealthRecord,
  renderHealthRecord,
} from "./pages/health-record/page.js";

function getAnimalId() {
  return new URLSearchParams(window.location.search).get("id");
}

function paint(html) {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = html;
  createIcons({ icons });
  initShell();
}

document.addEventListener("DOMContentLoaded", async () => {
  if (window.__PAGE_STATE__) {
    bootstrapPageAuth();
    const state = window.__PAGE_STATE__;
    const app = document.getElementById("app");
    const user = requireAuth(["admin"]);
    if (!user) return;

    const record = state.record || null;
    if (!app || !app.childElementCount) {
      renderHealthRecord(user, record);
      return;
    }

    createIcons({ icons });
    initShell();
    initDropdownMenu(document);
    hydrateHealthRecord(user, record);
    return;
  }

  const user = requireAuth(["admin"]);
  if (!user) return;

  const id = getAnimalId();
  if (!id) {
    paint(HealthRecordEmpty(user));
    return;
  }

  paint(HealthRecordLoading(user));

  try {
    const record = await fetchAnimalHealthRecord(id);
    if (!record) {
      paint(HealthRecordEmpty(user));
      return;
    }
    renderHealthRecord(user, record);
  } catch (err) {
    paint(HealthRecordError(user, err && err.message ? err.message : "Could not load this health record."));
  }
});
