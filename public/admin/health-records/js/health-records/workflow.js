import { createIcons, icons } from "lucide";
import { readPageClick, readPageSizeChange } from "/shared/components/pagination/pagination.js";
import { state, visibleRecords } from "./state.js";
import { rerenderAll } from "./components.js";
import { RecordsPanel } from "./components/table.js";
import { AttentionPanel } from "./components/queue.js";
import { StackedPanel, destroyCharts, mountCharts } from "./components/charts.js";
import { initSelect } from "/shared/components/select/select.js";
import { toast } from "/shared/components/toast/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

let eventsReady = false;
let searchTimer = null;

function setRegion(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

function remountStacked() {
  // Only the stacked canvas is replaced; destroy+remount all keeps the
  // shared registry consistent and avoids leaking the old Chart instance.
  destroyCharts();
  setRegion("hr-stacked", StackedPanel());
  createIcons({ icons });
  void mountCharts();
}

function refreshRecords() {
  setRegion("hr-records", RecordsPanel());
  createIcons({ icons });
}

function refreshQueue() {
  setRegion("hr-queue", AttentionPanel());
  createIcons({ icons });
}

export function initHealthRecordsEvents() {
  const app = document.getElementById("app");
  if (!app) return;

  if (!eventsReady) {
    eventsReady = true;

    app.addEventListener("click", (e) => {
      const tab = e.target.closest("button[data-filter]");
      if (tab) {
        state.filter = tab.dataset.filter;
        state.page = 1;
        const tabs = document.getElementById("hr-tabs");
        if (tabs) {
          tabs.querySelectorAll("[data-filter]").forEach((b) =>
            b.classList.toggle("is-active", b.dataset.filter === state.filter)
          );
        }
        rerenderAll();
        return;
      }

      const species = e.target.closest("button[data-species]");
      if (species) {
        state.species = species.dataset.species;
        state.page = 1;
        const toggle = document.getElementById("hr-species-tabs");
        if (toggle)
          toggle.querySelectorAll("[data-species]").forEach((b) => b.classList.toggle("is-active", b === species));
        remountStacked();
        return;
      }

      const page = readPageClick(e.target, state.page);
      if (page) {
        state.page = page;
        refreshRecords();
        return;
      }

      const row = e.target.closest("#hr-records tr[data-href]");
      if (row) {
        if (e.target.closest("a, button")) return;
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        window.location.href = row.dataset.href;
        return;
      }

      const queueAll = e.target.closest("button[data-queue-all]");
      if (queueAll) {
        state.queueExpanded = !state.queueExpanded;
        refreshQueue();
        return;
      }

      const exportBtn = e.target.closest("[data-export]");
      if (exportBtn) {
        const rows = visibleRecords();
        if (!rows.length) {
          toast("No health records match the current filters.", { type: "error" });
          return;
        }
        downloadCsv(
          datedCsvName("health-records"),
          ["id", "animal", "species", "breed", "barangay", "vaccination", "last_checkup", "next_due", "condition", "updated"],
          rows.map((r) => [
            r.id,
            r.animalName,
            r.species,
            r.breedType,
            r.barangay,
            r.vaccinationStatus,
            r.lastCheckupDate,
            r.nextCheckupDue,
            r.condition,
            r.updatedAt,
          ])
        );
        toast("CSV downloaded.", { type: "success" });
      }
    });

    app.addEventListener("change", (e) => {
      const next = readPageSizeChange(e.target, state.pageSize);
      if (!next) return;
      state.pageSize = next;
      state.page = 1;
      refreshRecords();
    });

    app.addEventListener("input", (e) => {
      const s = e.target.closest("#hr-search");
      if (!s) return;
      clearTimeout(searchTimer);
      searchTimer = setTimeout(() => {
        state.query = s.value;
        state.page = 1;
        rerenderAll();
      }, 180);
    });
  }

  // render() rebuilds the controls DOM on every load (initial + after the
  // API resolves), so the freshly created range/sort triggers must be
  // re-bound each time — otherwise their dropdown content stays hidden.
  initSelect(app, {
    "hr-range": (val) => {
      if (state.range === val) return;
      state.range = val;
      state.page = 1;
      rerenderAll();
    },
  });
}
