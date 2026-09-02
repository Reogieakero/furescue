import { createIcons, icons } from "lucide";
import { state, loadRescuers, persistSelection } from "../state.js";
import { RescuerTable, rerenderAll, selectRescuer, toggleCaseNode, openRescuerModal, renderRescuerDetail } from "../components.js";
import { runApprove, runReject, runSuspend, runActivate } from "./actions.js";
import { exportableRescuers } from "../components/table.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

export function initRescuerEvents() {
  const main = document.getElementById("app");
  if (!main || main.dataset.rescuerEvents) return;
  main.dataset.rescuerEvents = "1";

  main.addEventListener("click", async (e) => {
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = exportableRescuers();
      if (!list.length) {
        toast("No rescuers match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("rescuers"),
        ["id", "name", "email", "phone", "account_status", "duty_status"],
        list.map((r) => [r.id, r.full_name, r.email, r.phone_number, r.account_status, r.duty_status || "off_duty"])
      );
      toast("CSV downloaded.", { type: "success" });
      return;
    }

    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      persistSelection();
      rerenderAll();
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      persistSelection();
      const table = document.getElementById("rescuer-table");
      if (table) {
        table.innerHTML = RescuerTable();
        createIcons({ icons });
      }
      return;
    }

    const toggleEl = e.target.closest("[data-case-toggle]");
    if (toggleEl) {
      e.preventDefault();
      return toggleCaseNode(toggleEl.dataset.caseToggle);
    }

    const expandEl = e.target.closest('[data-act="expand"]');
    if (expandEl) {
      e.preventDefault();
      return openRescuerModal();
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      e.stopPropagation();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      if (action === "approve") return runApprove(id);
      if (action === "reject") return runReject(id);
      if (action === "suspend") return runSuspend(id);
      if (action === "activate") return runActivate(id);
      return;
    }

    const row = e.target.closest("tr[data-id]");
    if (row) return selectRescuer(row.dataset.id);
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#rescuer-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    persistSelection();
    const table = document.getElementById("rescuer-table");
    if (table) {
      table.innerHTML = RescuerTable();
      createIcons({ icons });
    }
  });
}

export function restoreSelection() {
  if (!state.selectedId) return;
  selectRescuer(state.selectedId);
}
