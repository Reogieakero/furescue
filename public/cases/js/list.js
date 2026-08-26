import { createIcons, icons } from "lucide";
import { requireAuth, redirectToLogin } from "../../js/lib/api.js";
import { bootstrapPageAuth } from "../../js/lib/page-auth.js";
import { initResidentShell } from "../../js/components/resident-shell.js";
import { fetchCases } from "./api.js";
import { bindCaseActions } from "./actions.js";
import { caseRow, countLabel, listErrorHtml, listLoadingHtml } from "./list-render.js";

const el = (id) => document.getElementById(id);

let allCases = [];
let currentStatus = "";

function paintIcons() {
  createIcons({ icons });
}

function renderRows(rows) {
  const list = el("cases-list");
  const empty = el("cases-empty");
  const count = el("cases-count");
  if (!list) return;
  if (count) count.textContent = countLabel(rows.length);
  if (!rows.length) {
    list.innerHTML = "";
    if (empty) empty.hidden = false;
    paintIcons();
    return;
  }
  if (empty) empty.hidden = true;
  list.innerHTML = rows.map(caseRow).join("");
  paintIcons();
}

function visibleRows() {
  if (!currentStatus) return allCases;
  return allCases.filter((item) => item.status === currentStatus);
}

async function loadCases({ silent = false } = {}) {
  const list = el("cases-list");
  const empty = el("cases-empty");
  if (empty) empty.hidden = true;
  if (!silent && list) list.innerHTML = listLoadingHtml();
  paintIcons();
  try {
    allCases = await fetchCases();
    renderRows(visibleRows());
  } catch (err) {
    if (err && err.status === 401) {
      redirectToLogin();
      return;
    }
    if (list) list.innerHTML = listErrorHtml(err.message);
    paintIcons();
    el("cases-retry")?.addEventListener("click", () => loadCases());
  }
}

function bindFilters() {
  document.querySelectorAll(".rtab[data-status]").forEach((tab) => {
    tab.addEventListener("click", () => {
      currentStatus = tab.getAttribute("data-status") || "";
      document.querySelectorAll(".rtab[data-status]").forEach((btn) => {
        const on = btn === tab;
        btn.classList.toggle("is-active", on);
        btn.setAttribute("aria-selected", String(on));
      });
      renderRows(visibleRows());
    });
  });
}

function boot() {
  bootstrapPageAuth();
  initResidentShell();
  if (!requireAuth(["rescuer"])) return;
  paintIcons();
  bindFilters();
  bindCaseActions(document.getElementById("cases-list"), {
    onAccepted: () => loadCases({ silent: true }),
    onDeclined: () => loadCases({ silent: true }),
  });
  el("refresh-cases")?.addEventListener("click", () => loadCases());
  loadCases();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
