import { createIcons, icons } from "lucide";
import { state, visibleRecords } from "./state.js";
import { rerenderAll } from "./components.js";
import { RecordsPanel, FilterTabs } from "./components/table.js";
import { AttentionPanel } from "./components/queue.js";
import { StackedPanel, destroyCharts, mountCharts } from "./components/charts.js";
import { initSelect } from "/assets/js/components/ui/select.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

let eventsReady = false;
let searchTimer = null;
let ignoreSearchInput = false;

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
  mountCharts();
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
        const toggle = document.querySelector(".hr-toggle");
        if (toggle)
          toggle.querySelectorAll("[data-species]").forEach((b) => b.classList.toggle("is-active", b === species));
        remountStacked();
        return;
      }

      const pageBtn = e.target.closest("button[data-page]");
      if (pageBtn) {
        const page = parseInt(pageBtn.dataset.page, 10);
        if (!page || page === state.page) return;
        state.page = page;
        refreshRecords();
        return;
      }

      const card = e.target.closest("button[data-queue-card]");
      if (card) {
        const name = card.dataset.animal || "";
        clearTimeout(searchTimer);
        ignoreSearchInput = true;
        state.query = name;
        state.filter = "all";
        state.page = 1;
        const tabs = document.getElementById("hr-tabs");
        if (tabs) tabs.innerHTML = FilterTabs();
        rerenderAll();
        const search = document.getElementById("hr-search");
        if (search) search.value = name;
        setTimeout(() => {
          ignoreSearchInput = false;
        }, 250);
        const reduce = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
        document.getElementById("hr-records")?.scrollIntoView({
          behavior: reduce ? "auto" : "smooth",
          block: "start",
        });
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

    app.addEventListener("input", (e) => {
      const s = e.target.closest("#hr-search");
      if (!s || ignoreSearchInput) return;
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
