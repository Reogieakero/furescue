import { createIcons, icons } from "lucide";
import { state, loadRescuers } from "../state.js";
import { RescuerTable, rerenderAll } from "../components.js";
import { runApprove, runReject, runSuspend, runActivate, openRescuer } from "./actions.js";

export function initRescuerEvents() {
  const main = document.getElementById("app");

  main.addEventListener("click", async (e) => {
    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      rerenderAll();
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      const table = document.getElementById("rescuer-table");
      if (table) {
        table.innerHTML = RescuerTable();
        createIcons({ icons });
      }
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      if (action === "approve") return runApprove(id);
      if (action === "reject") return runReject(id);
      if (action === "suspend") return runSuspend(id);
      if (action === "activate") return runActivate(id);
      if (action === "view") return openRescuer(id);
      return;
    }

    const row = e.target.closest("tr[data-id]");
    if (row) return openRescuer(row.dataset.id);
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#rescuer-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    const table = document.getElementById("rescuer-table");
    if (table) {
      table.innerHTML = RescuerTable();
      createIcons({ icons });
    }
  });
}
