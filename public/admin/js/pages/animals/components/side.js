import { createIcons, icons } from "lucide";
import { state, animalCounts, getAnimal, statusTone } from "../state.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { esc } from "./util.js";

function StatTile({ icon, value, label, cls = "" }) {
  return `
  <div class="mini-stat ${cls}">
    <div class="mini-stat-icon"><i data-lucide="${icon}"></i></div>
    <div class="mini-stat-value">${value}</div>
    <div class="mini-stat-label">${esc(label)}</div>
  </div>`;
}

export function StatsPanel() {
  const c = animalCounts();
  const tiles = [
    { icon: "paw-print", value: c.all, label: "Total", cls: "mini-stat--jungle" },
    { icon: "check-circle-2", value: c.Available, label: "Available", cls: "mini-stat--accent" },
    { icon: "hourglass", value: c.Pending, label: "Pending", cls: "mini-stat--muted" },
    { icon: "heart-pulse", value: c.Adopted, label: "Adopted", cls: "mini-stat--coral" },
  ].map(StatTile).join("");
  return `
  <div class="panel panel--padded animal-stats">
    <div class="panel-title-wrap"><i data-lucide="layout-grid"></i><h2 class="panel-title panel-title--sm">Overview</h2></div>
    <div class="mini-stat-grid">${tiles}</div>
  </div>`;
}

export function DetailPanel() {
  const a = state.selectedId ? getAnimal(state.selectedId) : null;
  if (!a) {
    return `
    <div class="panel panel--padded animal-detail animal-detail--empty">
      <div class="rescuer-detail-empty">
        <i data-lucide="mouse-pointer-click"></i>
        <p>Select an animal card to view its full profile.</p>
      </div>
    </div>`;
  }
  const rows = [
    { label: "ID", value: a.id },
    { label: "Species", value: a.species },
    { label: "Breed", value: a.breed },
    { label: "Age", value: a.age },
    { label: "Sex", value: a.sex },
    { label: "Status", value: a.status },
    { label: "Description", value: a.barangay },
    { label: "Intake", value: a.intake },
  ]
    .map(
      (r) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(r.label)}</span>
      <span class="dialog-info-value">${esc(r.value)}</span>
    </div>`
    )
    .join("");
  return `
  <div class="panel panel--padded animal-detail">
    <div class="panel-title-wrap">
      <i data-lucide="${esc(({ Dog: "paw-print", Cat: "cat", Bird: "bird", Rabbit: "rabbit", Other: "paw-print" })[a.species] || "paw-print")}"></i>
      <h2 class="panel-title panel-title--sm">${esc(a.name)}</h2>
      <span class="stamp stamp--sm ${statusTone(a.status)}" style="margin-left:auto">${esc(a.status)}</span>
    </div>
    <div class="dialog-info">${rows}</div>
    <div class="animal-detail-actions">
      <div class="animal-detail-actions-row">
        ${Button({
          text: a.hasMedical ? "View medical records" : "Add medical records",
          variant: "default",
          attrs: 'data-act="add-health"',
        })}
        ${Button({ text: "Delete", variant: "destructive", attrs: 'data-act="delete-animal"' })}
      </div>
    </div>
  </div>`;
}

export function SidePanel() {
  return `
  <div class="animal-side">
    ${StatsPanel()}
    <div id="animal-detail">${DetailPanel()}</div>
  </div>`;
}

export function renderSideStats() {
  const wrap = document.getElementById("animal-side");
  if (wrap) {
    wrap.innerHTML = SidePanel();
    createIcons({ icons });
  }
}

export function renderDetail() {
  const wrap = document.getElementById("animal-detail");
  if (wrap) {
    wrap.innerHTML = DetailPanel();
    createIcons({ icons });
  }
}
