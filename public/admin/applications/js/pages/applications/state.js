import { listAdoptions } from "../../api.js";

export const state = {
  items: [],
  filter: "all",
  query: "",
  page: 1,
  selectedId: null,
  loadError: "",
};

async function pull() {
  try {
    const result = await listAdoptions();
    state.items = result.items || [];
    state.loadError = "";
    return state.items;
  } catch (err) {
    state.loadError = (err && err.message) || "Could not load applications.";
    throw err;
  }
}

export function loadAdoptions() {
  return pull();
}

export function reloadData() {
  return pull();
}
