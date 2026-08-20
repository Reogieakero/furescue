import { createIcons, icons } from "lucide";
import { state, reloadData } from "../state.js";
import { ReportTable, rerenderAll, attachReportTooltips, hideReportMapDrawer } from "../components.js";
import { openReportDrawer } from "./drawer.js";
import { runVerify, runDismiss, assignDialog } from "./actions.js";

export function initReportsEvents() {
  const main = document.getElementById("app");

  main.addEventListener("click", async (e) => {
    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      const filters = document.getElementById("report-filters");
      if (filters) {
                filters.querySelectorAll("[data-filter]").forEach((b) => b.classList.toggle("is-active", b === tab));
      }
      const table = document.getElementById("report-table");
      if (table) {
        table.innerHTML = ReportTable();
        createIcons({ icons });
        attachReportTooltips();
      }
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      const table = document.getElementById("report-table");
      if (table) {
        table.innerHTML = ReportTable();
        createIcons({ icons });
        attachReportTooltips();
      }
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      const caseId = actionEl.dataset.case;
      if (action === "verify") return runVerify(id);
      if (action === "dismiss") return runDismiss(id);
      if (action === "assign") {
        assignDialog(caseId, id).then((payload) => {
          if (!payload) return;
          reloadData().then(() => {
            rerenderAll();
            createIcons({ icons });
          });
        });
        return;
      }
      return;
    }

    const row = e.target.closest("tr[data-id]");
    if (row) {
      hideReportMapDrawer();
      openReportDrawer(row.dataset.id);
    }
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#report-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    const table = document.getElementById("report-table");
    if (table) {
      table.innerHTML = ReportTable();
      createIcons({ icons });
      attachReportTooltips();
    }
  });
}
