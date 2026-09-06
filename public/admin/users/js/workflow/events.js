import { createIcons, icons } from "lucide";
import { readPageClick, readPageSizeChange } from "/shared/components/pagination/pagination.js";
import { state } from "../state.js";
import { UserTable, rerenderAll } from "../components.js";
import { filteredUsers } from "../components/table.js";
import { runActivate, runCreate, runDelete, runEdit, runSuspend, runView } from "./actions.js";
import { toast } from "/shared/components/toast/toast.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

export function initUsersEvents() {
  const main = document.getElementById("app");
  if (!main || main.dataset.userEvents) return;
  main.dataset.userEvents = "1";

  main.addEventListener("click", async (e) => {
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = filteredUsers();
      if (!list.length) {
        toast("No accounts match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("users"),
        ["id", "name", "email", "phone", "role", "status", "joined"],
        list.map((row) => [
          row.id,
          row.full_name || "",
          row.email || "",
          row.phone_number || "",
          row.role || "",
          row.account_status || "",
          row.created_at || "",
        ])
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

    const page = readPageClick(e.target, state.page);
    if (page) {
      state.page = page;
      const table = document.getElementById("user-table");
      if (table) {
        table.innerHTML = UserTable();
        createIcons({ icons });
      }
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      e.stopPropagation();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      if (action === "create") return runCreate();
      if (action === "edit") return runEdit(id);
      if (action === "suspend") return runSuspend(id);
      if (action === "activate") return runActivate(id);
      if (action === "delete") return runDelete(id);
      return;
    }

    const row = e.target.closest("#user-table tr[data-id]");
    if (row) return runView(row.dataset.id);
  });

  main.addEventListener("change", (e) => {
    const next = readPageSizeChange(e.target, state.pageSize);
    if (!next) return;
    state.pageSize = next;
    state.page = 1;
    const table = document.getElementById("user-table");
    if (table) {
      table.innerHTML = UserTable();
      createIcons({ icons });
    }
  });

  main.addEventListener("input", (e) => {
    const search = e.target.closest("#user-search");
    if (!search) return;
    state.query = search.value;
    state.page = 1;
    const table = document.getElementById("user-table");
    if (table) {
      table.innerHTML = UserTable();
      createIcons({ icons });
    }
  });
}
