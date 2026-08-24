import { createIcons, icons } from "lucide";
import { apiFetch, apiFetchFull, redirectToLogin } from "../../js/lib/api.js";
import { bootstrapPageAuth } from "../../js/lib/page-auth.js";
import { initResidentShell } from "../../js/components/resident-shell.js";
import { toast } from "../../js/components/ui/toast.js";
import { esc, timeAgo } from "../../js/lib/format.js";
import { openApplyModal } from "/animals/js/apply-modal.js";

const el = (id) => document.getElementById(id);

const STATUS_CHIPS = {
  pending: { text: "Pending review", cls: "rchip--brand" },
  approved: { text: "Approved 🎉", cls: "rchip--success" },
  rejected: { text: "Rejected", cls: "rchip--alert" },
  completed: { text: "Completed", cls: "rchip--sky" },
  cancelled: { text: "Cancelled", cls: "" },
};

let currentStatus = "";

function rowHtml(row) {
  const chip = STATUS_CHIPS[row.status] || STATUS_CHIPS.cancelled;
  const name = row.animal_name && String(row.animal_name).trim() !== "" ? String(row.animal_name).trim() : "an animal";

  return `
  <li class="rrow" data-id="${esc(row.id)}">
    <span class="rrow-icon"><i data-lucide="paw-print"></i></span>
    <div class="rrow-main">
      <p class="rrow-title">Application for ${esc(name)}</p>
      <p class="rrow-sub">Applied ${esc(timeAgo(row.created_at))}${
    row.message ? ` · “${esc(String(row.message))}”` : ""
  }</p>
      ${
        row.status === "rejected" && row.rejection_reason
          ? `<p class="rform-error mt-1"><i data-lucide="alert-circle" class="h-3.5 w-3.5"></i><span>Reason: ${esc(String(row.rejection_reason))}</span></p>`
          : ""
      }
      ${row.status === "approved" ? `<p class="rrow-sub mt-1 font-semibold text-primary">Congratulations! The shelter will contact you about next steps.</p>` : ""}
    </div>
    <div class="rrow-side">
      <span class="rchip ${chip.cls}">${esc(chip.text)}</span>
      ${
        row.status === "pending"
          ? `<button type="button" class="rbtn rbtn--ghost rbtn--sm" data-cancel><i data-lucide="x-circle"></i><span>Cancel</span></button>`
          : ""
      }
      <a class="rbtn rbtn--ghost rbtn--sm" href="/animals/detail.php?id=${esc(row.animal_id)}"><i data-lucide="eye"></i><span>View</span></a>
    </div>
  </li>`;
}

async function load() {
  const list = el("adoption-list");
  const params = new URLSearchParams({ per_page: "100" });
  if (currentStatus) params.set("status", currentStatus);
  list.innerHTML = `<li class="rempty"><i data-lucide="loader-circle"></i><p class="rempty-text">Loading…</p></li>`;
  createIcons({ icons });

  try {
    const payload = await apiFetchFull(`/adoptions?${params}`);
    const rows = Array.isArray(payload.data) ? payload.data : [];
    if (!rows.length) {
      list.innerHTML = "";
      el("adoptions-empty").hidden = false;
    } else {
      el("adoptions-empty").hidden = true;
      list.innerHTML = rows.map(rowHtml).join("");
    }
    createIcons({ icons });
  } catch (err) {
    if (err && err.status === 401) {
      redirectToLogin();
      return;
    }
    list.innerHTML = `<li class="rempty"><i data-lucide="triangle-alert"></i><p class="rempty-text">${esc(
      err.message || "Could not load your applications."
    )}</p></li>`;
    createIcons({ icons });
  }
}

async function cancelApplication(rowEl) {
  const btn = rowEl.querySelector("[data-cancel]");
  if (!btn) return;
  btn.disabled = true;
  try {
    await apiFetch(`/adoptions/${encodeURIComponent(rowEl.dataset.id)}/cancel`, { method: "POST" });
    toast("Application cancelled.", { type: "success" });
    await load();
  } catch (err) {
    btn.disabled = false;
    toast(err.message || "Could not cancel this application.", { type: "error" });
  }
}

async function maybeOpenDeepLinkApply(animalId) {
  try {
    const payload = await apiFetchFull(`/animals/${encodeURIComponent(animalId)}`);
    const animal = payload.data && payload.data.animal;
    if (!animal || animal.adoption_status !== "available") {
      toast("This animal is no longer available for adoption.", { type: "error" });
      return;
    }
    openApplyModal(animal, { onApplied: load });
  } catch (err) {
    if (err && err.status !== 401) {
      toast(err.message || "Could not open the application form.", { type: "error" });
    }
  }
}

function boot() {
  const user = bootstrapPageAuth();
  if (!user) {
    redirectToLogin();
    return;
  }
  initResidentShell();

  document.querySelector(".rtabs").addEventListener("click", (event) => {
    const tab = event.target.closest(".rtab");
    if (!tab) return;
    document.querySelectorAll(".rtab").forEach((t) => {
      t.classList.toggle("is-active", t === tab);
      t.setAttribute("aria-selected", t === tab ? "true" : "false");
    });
    currentStatus = tab.dataset.status || "";
    load();
  });

  el("adoption-list").addEventListener("click", (event) => {
    if (!event.target.closest("[data-cancel]")) return;
    const rowEl = event.target.closest(".rrow");
    if (rowEl) cancelApplication(rowEl);
  });

  load().then(() => {
    if (window.__PAGE_STATE__ && window.__PAGE_STATE__.applyAnimalId) {
      maybeOpenDeepLinkApply(window.__PAGE_STATE__.applyAnimalId);
    }
  });
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
