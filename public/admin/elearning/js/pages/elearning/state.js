import * as api from "../../api.js";
import { emptyEditor } from "./components/util.js";

export const state = {
  modules: [],
  filter: "all",
  category: "all",
  query: "",
  page: 1,
  view: "library",
  editor: emptyEditor(),
  loadError: "",
  saving: false,
};

export function hydrateModules(rows) {
  state.modules = Array.isArray(rows) ? rows.map(listRow) : [];
}

function listRow(row) {
  if (!row || typeof row !== "object") return row;
  return {
    id: row.id,
    title: row.title,
    category: row.category,
    published_status: row.published_status,
    created_at: row.created_at,
    created_by: row.created_by,
  };
}

export async function loadModules() {
  try {
    const result = await api.fetchModules();
    state.modules = result.items || [];
    state.loadError = "";
  } catch (err) {
    state.modules = [];
    state.loadError = err.message || "Could not load modules.";
    throw err;
  }
}

export function resetEditor() {
  state.view = "library";
  state.editor = emptyEditor();
  state.saving = false;
}
