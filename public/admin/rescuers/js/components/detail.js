import { createIcons, icons } from "lucide";
import { state, persistSelection } from "../state.js";
import * as api from "/assets/js/admin/admin-data.js";
import { RescuerInfo } from "./detail-profile.js";
import { caseTree, rescuerCaseList } from "./detail-cases.js";

export { openRescuerModal, toggleCaseNode } from "./detail-cases.js";

let selectGen = 0;

function highlightRow(id) {
  document
    .querySelectorAll("#rescuer-table tr[data-id]")
    .forEach((tr) => tr.classList.toggle("is-selected", tr.dataset.id === id));
}

export function RescuerDetail() {
  if (!state.selectedId) {
    return `<div class="rescuer-detail-empty"><i data-lucide="user-round-search"></i><span>Select a rescuer to view their profile and past cases.</span></div>`;
  }
  if (state.selectedRescuer === undefined) {
    return `<div class="rescuer-detail-empty"><i data-lucide="loader" class="spin"></i><span>Loading rescuer…</span></div>`;
  }
  if (!state.selectedRescuer) {
    return `<div class="rescuer-detail-empty"><i data-lucide="user-x"></i><span>Rescuer no longer available.</span></div>`;
  }
  const r = state.selectedRescuer;
  const cases = rescuerCaseList();
  return `
  <div class="rescuer-detail">
    ${RescuerInfo(r)}
    <div class="rescuer-detail-section">
      <div class="rescuer-detail-section-head">
        <i data-lucide="history"></i>
        <h3>Past cases</h3>
        <span class="stamp stamp--sm stamp--accent">${cases.length}</span>
      </div>
      <div class="rescuer-detail-tree">${caseTree(cases)}</div>
    </div>
  </div>`;
}

export function renderRescuerDetail() {
  const el = document.getElementById("rescuer-detail");
  if (!el) return;
  el.innerHTML = RescuerDetail();
  createIcons({ icons });
}

function rowRescuer(id) {
  return [...state.rescuers, ...state.pending].find((x) => x.id === id) || null;
}

export async function selectRescuer(id, { force = false } = {}) {
  if (!id) return;
  highlightRow(id);

  const fromRow = rowRescuer(id);
  const alreadyLoaded =
    !force &&
    state.selectedId === id &&
    state.selectedRescuer &&
    state.selectedRescuer.id === id;

  if (alreadyLoaded) {
    persistSelection();
    renderRescuerDetail();
    return;
  }

  const gen = ++selectGen;
  state.selectedId = id;
  state.selectedRescuer =
    fromRow ||
    (state.selectedRescuer && state.selectedRescuer.id === id ? state.selectedRescuer : fromRow);
  persistSelection();
  renderRescuerDetail();

  let rescuer = state.selectedRescuer;
  let casesRes = { items: state.selectedRescuerCases || [] };
  try {
    const userRes = await api.fetchUser(id);
    if (gen !== selectGen || state.selectedId !== id) return;
    const fetched = (userRes && (userRes.user || userRes)) || null;
    if (fetched) {
      rescuer = { ...(fromRow || {}), ...fetched };
    }
    const cases = await api.fetchRescuerCases(id);
    casesRes = cases || { items: [] };
  } catch {
    if (!rescuer) rescuer = fromRow;
    casesRes = { items: [] };
  }
  if (gen !== selectGen || state.selectedId !== id) return;
  state.selectedRescuer = rescuer || fromRow || null;
  state.selectedRescuerCases = casesRes.items || [];
  persistSelection();
  renderRescuerDetail();
}
