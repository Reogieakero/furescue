// Queue tab switching + shadcn pagination behaviour for "Needs your attention".
import { createIcons, icons } from "lucide";
import { queueState } from "./state.js";
import {
  ReportsQueueInner,
  RescuersQueueInner,
  HealthQueueInner,
  AdoptionQueueInner,
} from "./components.js";

const QUEUE_INNERS = {
  reports: ReportsQueueInner,
  rescuers: RescuersQueueInner,
  health: HealthQueueInner,
  adopt: AdoptionQueueInner,
};

export function initQueueTabs() {
  const tabs = document.querySelectorAll(".q-btn");
  const panels = document.querySelectorAll(".queue-panel");

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      panels.forEach((p) => p.classList.add("is-hidden"));
      const panel = document.getElementById("queue-" + btn.dataset.q);
      if (panel) panel.classList.remove("is-hidden");
      tabs.forEach((b) => b.classList.toggle("is-active", b === btn));
    });
  });
}

export function renderQueuePanel(key) {
  const panel = document.getElementById("queue-" + key);
  if (!panel) return;
  panel.innerHTML = QUEUE_INNERS[key]();
  createIcons({ icons });
}

export function initQueuePagination() {
  document.querySelectorAll(".queue-panel").forEach((panel) => {
    panel.addEventListener("click", (e) => {
      const btn = e.target.closest("button[data-page]");
      if (!btn || btn.getAttribute("aria-disabled") === "true") return;
      const key = panel.id.replace("queue-", "");
      const page = parseInt(btn.dataset.page, 10);
      if (!page || page === queueState[key]) return;
      queueState[key] = page;
      renderQueuePanel(key);
    });
  });
}