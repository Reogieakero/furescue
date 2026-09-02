import { createIcons, icons } from "lucide";
import { state } from "../state.js";
import { ListingTable, rerenderAll } from "../components.js";
import { filteredListings } from "../components/table.js";
import { runApprove, runReject } from "./actions.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

export function initListingsEvents() {
  const main = document.getElementById("app");
  if (!main || main.dataset.listingEvents) return;
  main.dataset.listingEvents = "1";

  main.addEventListener("click", async (e) => {
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = filteredListings();
      if (!list.length) {
        toast("No listings match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("listings"),
        ["id", "animal", "poster", "status", "posted"],
        list.map((row) => [row.id, row.animal_name || "", row.poster_name || "", row.status, row.created_at || ""])
      );
      toast("CSV downloaded.", { type: "success" });
      return;
    }

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
      const table = document.getElementById("listing-table");
      if (table) {
        table.innerHTML = ListingTable();
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
    }
  });

  main.addEventListener("input", (e) => {
    const search = e.target.closest("#listing-search");
    if (!search) return;
    state.query = search.value;
    state.page = 1;
    const table = document.getElementById("listing-table");
    if (table) {
      table.innerHTML = ListingTable();
      createIcons({ icons });
    }
  });
}
