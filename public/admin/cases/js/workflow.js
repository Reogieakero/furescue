import { createIcons, icons } from "lucide";
import { readPageClick, readPageSizeChange } from "/shared/components/pagination/pagination.js";
import * as api from "/assets/js/admin/admin-data.js";
import { toast } from "/shared/components/toast/toast.js";
import { confirmDialog } from "/shared/components/dialog/dialog.js";
import { Button } from "/shared/components/button/button.js";
import { Label } from "/shared/components/label/label.js";
import { Select, initSelect } from "/shared/components/select/select.js";
import { state, reloadData, saveFilterPref } from "./state.js";
import { rerenderAll, renderCaseList } from "./components.js";
import { shortId } from "/admin/js/helpers.js";
import { filteredCases } from "./components/list.js";
import { datedCsvName, downloadCsv } from "/assets/js/lib/csv.js";

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
            ? `${Label({ htmlFor: "assign-rescuer", className: "dialog-label", required: true, children: "Rescuer" })}
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

    let busy = false;
    const submit = async () => {
      if (busy) return;
      if (!selected) {
        toast("Please select a rescuer.", { type: "error" });
        return;
      }
      const name = rescuers.find((u) => u.id === selected);
      const caseRow = state.cases.find((c) => c.id === caseId);
      const isReassign = !!(caseRow && (caseRow.assigned_rescuer_id || caseRow.rescuer));
      busy = true;
      const payload = await confirmDialog({
        title: isReassign ? "Reassign this case?" : "Assign this case?",
        message: `${isReassign ? "Reassign" : "Assign"} case ${shortId(caseId)} to ${(name && name.full_name) || "this rescuer"}?`,
        confirmText: isReassign ? "Reassign" : "Assign",
        cancelText: "Cancel",
        run: () => api.assignRescuer(caseId, selected),
      });
      if (!payload) {
        busy = false;
        return;
      }
      overlay.remove();
      resolve(payload);
      toast(`Case ${shortId(caseId)} assigned to ${(name && name.full_name) || "rescuer"}.`, { type: "success" });
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
    const exportBtn = e.target.closest("[data-export]");
    if (exportBtn) {
      const list = filteredCases();
      if (!list.length) {
        toast("No cases match the current filters.", { type: "error" });
        return;
      }
      downloadCsv(
        datedCsvName("cases"),
        ["id", "status", "barangay", "animal", "rescuer", "created", "updated"],
        list.map((c) => [
          c.id,
          c.statusRaw,
          c.brgy,
          c.animal,
          (c.rescuer && c.rescuer.full_name) || "",
          c.createdAt,
          c.updatedAt,
        ])
      );
      toast("CSV downloaded.", { type: "success" });
      return;
    }

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

    const page = readPageClick(e.target, state.page);
    if (page) {
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
      window.location.href = "/admin/cases/case-detail.php?id=" + encodeURIComponent(card.dataset.caseId);
    }
  });

  main.addEventListener("change", (e) => {
    const next = readPageSizeChange(e.target, state.pageSize);
    if (!next) return;
    state.pageSize = next;
    state.page = 1;
    renderCaseList();
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#case-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    renderCaseList();
  });
}
