import { createIcons, icons } from "lucide";
import { state, persistSelection } from "../state.js";
import * as api from "/admin/js/lib/admin-data.js";
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

export async function selectRescuer(id, { force = false } = {}) {
  if (!id) return;
  highlightRow(id);
  if (!force && state.selectedId === id) {
    if (state.selectedRescuer) {
      persistSelection();
      renderRescuerDetail();
      return;
    }
    if (state.selectedRescuer === undefined) return;
  }

  const gen = ++selectGen;
  state.selectedId = id;
  state.selectedRescuer = undefined;
  persistSelection();
  renderRescuerDetail();

  let rescuer = null;
  let casesRes = { items: [] };
  try {
    const userRes = await api.fetchUser(id);
    if (gen !== selectGen || state.selectedId !== id) return;
    rescuer = (userRes && (userRes.user || userRes)) || null;
    const cases = await api.fetchRescuerCases(id);
    casesRes = cases || { items: [] };
  } catch {
    rescuer = null;
    casesRes = { items: [] };
  }
  if (gen !== selectGen || state.selectedId !== id) return;
  state.selectedRescuer = rescuer;
  state.selectedRescuerCases = casesRes.items || [];
  persistSelection();
  renderRescuerDetail();
}
