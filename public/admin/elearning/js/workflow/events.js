import { createIcons, icons } from "lucide";
import { state } from "../state.js";
import { LibraryBody, rerenderLibrary } from "../components.js";
import { closeEditor, openEdit, openNew, runPublish, runSave, runUnpublish } from "./actions.js";

export function initElearningEvents() {
  const main = document.getElementById("app");
  if (!main || main.dataset.elearnEvents) return;
  main.dataset.elearnEvents = "1";

  main.addEventListener("click", (e) => {
    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter || "all";
      state.page = 1;
      rerenderLibrary();
      return;
    }

    const cat = e.target.closest("button[data-category]");
    if (cat) {
      state.category = cat.dataset.category || "all";
      state.page = 1;
      rerenderLibrary();
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      const table = document.getElementById("elearn-table");
      if (table) {
        table.innerHTML = LibraryBody();
        createIcons({ icons });
      }
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (!actionEl) return;
    e.preventDefault();
    const action = actionEl.dataset.action;
    const id = actionEl.dataset.id;
    if (action === "new") return openNew();
    if (action === "edit") return openEdit(id);
    if (action === "cancel") return closeEditor();
    if (action === "save") return runSave(e);
    if (action === "publish") return runPublish(id);
    if (action === "unpublish") return runUnpublish(id);
  });

  main.addEventListener("submit", (e) => {
    if (!e.target.closest("#elearn-form")) return;
    e.preventDefault();
    return runSave(e);
  });

  main.addEventListener("input", (e) => {
    const search = e.target.closest("#elearn-search");
    if (search) {
      state.query = search.value;
      state.page = 1;
      const table = document.getElementById("elearn-table");
      if (table) {
        table.innerHTML = LibraryBody();
        createIcons({ icons });
      }
      return;
    }
    if (e.target.id === "elearn-title") {
      const count = document.getElementById("elearn-title-count");
      if (count) count.textContent = String(e.target.value.length);
    }
    if (e.target.id === "elearn-body") {
      const count = document.getElementById("elearn-body-count");
      if (count) count.textContent = String(e.target.value.length);
    }
  });
}
