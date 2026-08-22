import { createIcons, icons } from "lucide";
import * as api from "../../lib/admin-data.js";
import { toast } from "../../../../js/components/ui/toast.js";
import { Button } from "../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../js/components/ui/select.js";
import { Spinner } from "../../../../js/components/ui/spinner.js";
import { state, reloadData, saveFilterPref } from "./state.js";
import { rerenderAll, renderCaseList } from "./components.js";
import { shortId, titleCase } from "../dashboard/helpers.js";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function assignDialog(caseId, reportId) {
  return new Promise((resolve) => {
    const rescuers = state.rescuers.filter(
      (u) => u.role === "rescuer" && u.account_status === "active" && (u.duty_status || "off_duty") === "on_duty"
    );
    const options = rescuers.map((u) => ({ value: u.id, label: u.full_name || "Unnamed rescuer" }));

    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";
    overlay.innerHTML = `
      <div class="dialog" role="dialog" aria-modal="true" aria-labelledby="assign-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="user-plus" class="dialog-icon"></i>
            <h3 class="dialog-title" id="assign-title">Assign rescuer</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body">
          <p class="dialog-message">Assign a rescuer to case ${shortId(caseId)}${reportId ? ` (report ${shortId(reportId)})` : ""}. Only on-duty rescuers can be assigned.</p>
          ${options.length
            ? `<label class="dialog-label" for="assign-rescuer">Rescuer<span class="dialog-req"> *</span></label>
               ${Select({ id: "assign-rescuer", options, placeholder: "Select a rescuer…", className: "w-full" })}`
            : `<div class="empty-state"><i data-lucide="siren"></i><span>No on-duty rescuers available.</span></div>`}
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
          ${Button({ text: "Assign", variant: "default", attrs: 'data-act="ok"', className: options.length ? "" : "hidden" })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });
    let selected = "";
    initSelect(overlay, { "assign-rescuer": (val) => { selected = val; } });
    if (options.length) {
      const trigger = overlay.querySelector("#assign-rescuer [data-select-value]");
      if (trigger) trigger.textContent = "";
    }

    const close = () => {
      overlay.remove();
      resolve(null);
    };

    const submit = async () => {
      if (!selected) {
        toast("Please select a rescuer.", { type: "error" });
        return;
      }
      const okBtn = overlay.querySelector('[data-act="ok"]');
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Assign</span>`;
      createIcons({ icons });
      try {
        const payload = await api.assignRescuer(caseId, selected);
        const name = rescuers.find((u) => u.id === selected);
        overlay.remove();
        resolve(payload);
        toast(`Case ${shortId(caseId)} assigned to ${(name && name.full_name) || "rescuer"}.`, { type: "success" });
      } catch (err) {
        okBtn.disabled = false;
        okBtn.innerHTML = `<span>Assign</span>`;
        toast(err && err.message ? err.message : "Assign failed.", { type: "error" });
      }
    };

    overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
    overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
    overlay.querySelector(".dialog-x").addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
  });
}

export function initCasesEvents() {
  const main = document.getElementById("app");

  main.addEventListener("click", async (e) => {
    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      saveFilterPref(state.filter);
      const tabs = document.getElementById("case-tabs");
      if (tabs) {
        tabs.querySelectorAll("[data-filter]").forEach((b) => b.classList.toggle("is-active", b === tab));
      }
      renderCaseList();
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      renderCaseList();
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      const action = actionEl.dataset.action;
      const caseId = actionEl.dataset.case;
      const reportId = actionEl.dataset.report;
      if (action === "assign") {
        assignDialog(caseId, reportId).then((payload) => {
          if (!payload) return;
          reloadData().then(() => {
            rerenderAll();
            createIcons({ icons });
          });
        });
        return;
      }
      return;
    }

    const card = e.target.closest("article[data-case-id]");
    if (card) {
      window.location.href = "case-detail.html?id=" + encodeURIComponent(card.dataset.caseId);
    }
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#case-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    renderCaseList();
  });
}
