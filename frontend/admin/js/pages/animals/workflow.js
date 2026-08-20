import { createIcons, icons } from "lucide";
import { state, getAnimal } from "./state.js";
import { renderAnimalGrid, renderSelection, FilterTabs } from "./components/grid.js";
import { renderDetail, renderSideStats } from "./components/side.js";
import { openAddAnimalDialog } from "./components/modal.js";
import { openHealthRecordDialog } from "./components/health.js";
import { openEditAnimalDialog } from "./components/edit.js";
import { confirmDialog } from "../../../../js/components/ui/dialog.js";
import { deleteAnimal } from "../../lib/admin-data.js";
import { normalize } from "./state.js";

export function initAnimalsEvents() {
  const app = document.getElementById("app");
  if (!app) return;
  if (app.dataset.animalsEventsBound) return;
  app.dataset.animalsEventsBound = "1";

  app.addEventListener("click", async (e) => {
    const openBtn = e.target.closest('[data-act="open-add"]');
    if (openBtn) {
      const animal = await openAddAnimalDialog();
      if (animal) {
        state.selectedId = animal.id;
        renderAnimalGrid();
        renderSideStats();
        renderSelection();
      }
      return;
    }

    const healthBtn = e.target.closest('[data-act="add-health"]');
    if (healthBtn) {
      const animal = getAnimal(state.selectedId);
      if (animal) await openHealthRecordDialog(animal);
      return;
    }

    const editBtn = e.target.closest('[data-act="edit-animal"]');
    if (editBtn) {
      const animal = getAnimal(state.selectedId);
      if (animal) {
        const updated = await openEditAnimalDialog(animal);
        if (updated) {
          const idx = state.animals.findIndex((a) => a.id === animal.id);
          if (idx !== -1) state.animals[idx] = normalize(updated);
          renderAnimalGrid();
          renderSideStats();
          renderSelection();
        }
      }
      return;
    }

    const delBtn = e.target.closest('[data-act="delete-animal"]');
    if (delBtn) {
      const animal = getAnimal(state.selectedId);
      if (animal) {
        const ok = await confirmDialog({
          title: "Delete animal?",
          message: `Remove "${animal.name}"? This is a soft delete and can be restored by an admin.`,
          confirmText: "Delete",
          cancelText: "Cancel",
          danger: true,
        });
        if (ok) {
          await deleteAnimal(animal.id);
          state.animals = state.animals.filter((a) => a.id !== animal.id);
          state.selectedId = null;
          renderAnimalGrid();
          renderSideStats();
        }
      }
      return;
    }

    const card = e.target.closest(".animal-card");
    if (card) {
      state.selectedId = card.dataset.animal;
      renderSelection();
      renderDetail();
      return;
    }

    const tab = e.target.closest("[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      renderAnimalGrid();
      return;
    }
  });

  app.addEventListener("input", (e) => {
    if (e.target && e.target.id === "animal-search") {
      state.query = e.target.value;
      renderAnimalGrid();
    }
  });

  createIcons({ icons });
}
