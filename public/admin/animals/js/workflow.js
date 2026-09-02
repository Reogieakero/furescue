import { createIcons, icons } from "lucide";
import { state, getAnimal, setSelectedId, visibleAnimals, normalize } from "./state.js";
import { renderAnimalGrid, renderSelection } from "./components/grid.js";
import { renderDetail, renderSideStats } from "./components/side.js";
import { renderAnimalKpis } from "./components/kpis.js";
import { openAddAnimalDialog } from "./components/modal.js";
import { openHealthRecordDialog } from "./components/health.js";
import { openEditAnimalDialog } from "./components/edit.js";
import { confirmDialog } from "/assets/js/components/ui/dialog.js";
import { deleteAnimal } from "/assets/js/admin/admin-data.js";
import { toast } from "/assets/js/components/ui/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

export function initAnimalsEvents() {
  const app = document.getElementById("app");
  if (!app) return;
  if (app.dataset.animalsEventsBound) return;
  app.dataset.animalsEventsBound = "1";

  app.addEventListener("click", async (e) => {
    const openBtn = e.target.closest('[data-act="open-add"]');
    if (openBtn) {
      await openAddAnimalDialog();
      return;
    }

    const healthBtn = e.target.closest('[data-act="add-health"]');
    if (healthBtn) {
      const animal = getAnimal(state.selectedId);
      if (animal) {
        const saved = await openHealthRecordDialog(animal);
        if (saved) {
          animal.hasMedical = true;
          renderAnimalGrid();
          renderAnimalKpis();
          renderDetail();
        }
      }
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
          renderAnimalKpis();
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
          setSelectedId(null);
          renderAnimalGrid();
          renderAnimalKpis();
          renderSideStats();
        }
      }
      return;
    }

    const card = e.target.closest(".animal-card");
    if (card) {
      setSelectedId(card.dataset.animal);
      renderSelection();
      renderDetail();
      return;
    }

    const tab = e.target.closest("[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      renderAnimalGrid();
      const tabs = document.getElementById("animal-filter-tabs");
      if (tabs) {
        tabs.querySelectorAll("[data-filter]").forEach((b) =>
          b.classList.toggle("is-active", b.dataset.filter === state.filter)
        );
      }
      return;
    }

    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const rows = visibleAnimals();
      if (!rows.length) {
        toast("No animals match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("animals"),
        ["id", "name", "species", "breed", "age", "sex", "status", "barangay", "intake"],
        rows.map((a) => [a.id, a.name, a.species, a.breed, a.age, a.sex, a.status, a.barangay, a.intake])
      );
      toast("CSV downloaded.", { type: "success" });
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
