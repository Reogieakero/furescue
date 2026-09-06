import { emptyState, esc, mapHealthUpdate, mapPersonnel, OVERVIEW_LABELS } from "./format.js";

function setWrapMode(wrap, isEmpty) {
  wrap.classList.toggle("queue-empty", isEmpty);
  wrap.classList.toggle("table-wrap", !isEmpty);
}

export function renderOverview(rows) {
  const wrap = document.getElementById("table-overview");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("inbox", "No records.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Metric</th><th class="table-cell--right">Value</th></tr></thead>
      <tbody>${rows.map((r) => `
        <tr>
          <td class="table-cell">${esc(OVERVIEW_LABELS[r.key] ?? r.key ?? "")}</td>
          <td class="table-cell table-cell--mono table-cell--strong table-cell--right">${esc(r.value ?? 0)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

export function renderTrends(rows) {
  const wrap = document.getElementById("table-trends");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("bar-chart-3", "No completed adoptions in this range.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Day</th><th class="table-cell--right">Completed adoptions</th></tr></thead>
      <tbody>${rows.map((t) => `
        <tr>
          <td class="table-cell table-cell--mono">${esc(t.day ?? "")}</td>
          <td class="table-cell table-cell--mono table-cell--strong table-cell--right">${esc(t.completed ?? 0)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

export function renderUpdates(rows) {
  const wrap = document.getElementById("table-health");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("heart-pulse", "No health updates in this range.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Update</th><th>Animal</th><th>Logged by</th><th>Status</th><th>When</th></tr></thead>
      <tbody>${rows.map(mapHealthUpdate).map((r) => `
        <tr>
          <td class="table-cell table-cell--mono table-cell--strong">${esc(r.id)}</td>
          <td class="table-cell">${esc(r.animal)}</td>
          <td class="table-cell">${esc(r.by)}</td>
          <td class="table-cell"><span class="stamp stamp--sm ${esc(r.statusCls)}">${esc(r.status)}</span></td>
          <td class="table-cell table-cell--mono table-cell--muted">${esc(r.when)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

export function renderPersonnel(rows) {
  const wrap = document.getElementById("table-personnel");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("siren", "No active rescuers.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Rescuer</th><th>Contact</th><th>Duty</th><th>Updated</th></tr></thead>
      <tbody>${rows.map(mapPersonnel).map((r) => `
        <tr>
          <td class="table-cell table-cell--strong">${esc(r.name)}</td>
          <td class="table-cell">${esc(r.contact)}</td>
          <td class="table-cell"><span class="stamp stamp--sm ${esc(r.dutyCls)}">${esc(r.duty)}</span></td>
          <td class="table-cell table-cell--mono table-cell--muted">${esc(r.when)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

export function renderAll(state) {
  renderOverview(state.overview);
  renderTrends(state.trends);
  renderUpdates(state.updates);
  renderPersonnel(state.personnel || []);
}
