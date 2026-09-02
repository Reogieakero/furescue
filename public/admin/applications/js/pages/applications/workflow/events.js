import { createIcons, icons } from "lucide";
import { state } from "../state.js";
import { ApplicationTable } from "../components.js";
import { filteredApplications } from "../components/table.js";
import { openDetailsDrawer } from "./drawer.js";
import { runDecline, runRetry } from "./actions.js";
import { toast } from "/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/js/lib/csv.js";
import { applicantName, animalName } from "../components/util.js";

function paintTable() {
  const table = document.getElementById("application-table");
  if (!table) return;
  table.innerHTML = ApplicationTable();
  createIcons({ icons });
}

export function initApplicationEvents() {
  const main = document.getElementById("app");
  if (!main || main.dataset.applicationEvents) return;
  main.dataset.applicationEvents = "1";

  main.addEventListener("click", async (e) => {
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = filteredApplications();
      if (!list.length) {
        toast("No applications match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("applications"),
        ["id", "applicant", "animal", "status", "message", "rejection_reason", "submitted"],
        list.map((a) => [
          a.id,
          applicantName(a),
          animalName(a),
          a.status,
          a.message || "",
          a.rejection_reason || "",
          a.created_at,
        ])
      );
      toast("CSV downloaded.", { type: "success" });
      return;
    }

    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      const filters = document.getElementById("application-filters");
      if (filters) {
        filters.querySelectorAll("[data-filter]").forEach((b) => b.classList.toggle("is-active", b === tab));
      }
      paintTable();
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      if (pageBtn.getAttribute("aria-disabled") === "true") return;
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      paintTable();
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      e.stopPropagation();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      try {
        if (action === "retry") return await runRetry();
        if (action === "view" || action === "details") {
          state.selectedId = id;
          paintTable();
          return openDetailsDrawer(id);
        }
        if (action === "reject" || action === "decline") return await runDecline(id);
      } catch (err) {
        toast((err && err.message) || "Action failed.", { type: "error" });
      }
      return;
    }

    const row = e.target.closest("tr[data-id]");
    if (row) {
      state.selectedId = row.dataset.id;
      paintTable();
      openDetailsDrawer(row.dataset.id);
    }
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#application-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    paintTable();
  });
}
