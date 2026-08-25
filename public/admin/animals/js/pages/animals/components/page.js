import { createIcons, icons } from "lucide";
import { AppShell } from "/admin/js/layout/app-shell.js";
import { Button } from "/js/components/ui/button.js";
import { state } from "../state.js";
import { AnimalGrid } from "./grid.js";
import { SidePanel } from "./side.js";
import { AnimalKpis, renderAnimalKpis } from "./kpis.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--jungle">Animal Management</span>
      <h1 class="page-title">Animals</h1>
      <p class="page-sub">Browse every animal in the system, add new rescues, and review their profiles.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Add animal", variant: "default", icon: "plus", attrs: 'data-act="open-add"' })}
      ${Button({ text: "Export CSV", variant: "outline", icon: "download", attrs: 'data-export="csv"' })}
    </div>
  </div>`;
}

export function AnimalsPage(user) {
  return AppShell({
    user,
    notifications: 0,
    badges: { animals: state.animals.length },
    activeNav: "animals",
    children: [
      `<div class="animals-list">
        ${PageHead()}
        ${AnimalKpis()}
        <div class="animal-split">
          <div class="animal-grid-col">${AnimalGrid()}</div>
          <div id="animal-side" class="animal-side-col">${SidePanel()}</div>
        </div>
      </div>`,
    ].join(""),
  });
}

export function rerenderAll() {
  renderAnimalKpis();
  const side = document.getElementById("animal-side");
  if (side) {
    side.innerHTML = SidePanel();
    createIcons({ icons });
  }
  const navBadge = document.querySelector('.sidebar-link[href="/admin/animals/"] .sidebar-badge');
  if (navBadge) navBadge.textContent = String(state.animals.length);
  createIcons({ icons });
}
