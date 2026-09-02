import { createIcons, icons } from "lucide";
import { state, reloadData } from "../state.js";
import { ReportTable, rerenderAll, attachReportTooltips, hideReportMapDrawer } from "../components.js";
import { openReportDrawer, openTimelineDrawer } from "./drawer.js";
import { runVerify, runDismiss, assignDialog } from "./actions.js";
import { filteredReports, enrich } from "../components/table.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

export function initReportsEvents() {
  const main = document.getElementById("app");

  main.addEventListener("click", async (e) => {
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = filteredReports();
      if (!list.length) {
        toast("No reports match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("reports"),
        ["id", "barangay", "reporter", "status", "case_status", "rescuer", "submitted"],
        list.map((r) => {
          const v = enrich(r);
          return [r.id, v.brgy, v.reporter, v.status, v.caseStatus || "", v.rescuer, r.created_at];
        })
      );
      toast("CSV downloaded.", { type: "success" });
      return;
    }

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
      if ((action === "progress" || action === "resolve") && caseId) {
        window.location.href = `/admin/cases/case-detail.php?id=${encodeURIComponent(caseId)}`;
        return;
      }
      if (action === "timeline") {
        openTimelineDrawer(caseId, id);
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
