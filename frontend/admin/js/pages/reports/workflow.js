// Reports page workflow — verify/dismiss, assign rescuer, case status.
import { createIcons, icons } from "lucide";
import * as api from "../../lib/admin-data.js";
import { toast } from "../../../../js/components/ui/toast.js";
import { confirmDialog } from "../../../../js/components/ui/dialog.js";
import { openDrawer } from "../../../../js/components/ui/drawer.js";
import { Button } from "../../../../js/components/ui/button.js";
import { Select, initSelect } from "../../../../js/components/ui/select.js";
import { Spinner } from "../../../../js/components/ui/spinner.js";
import { state, reloadData } from "./state.js";
import { rerenderAll, ReportTable, hideReportMapDrawer, attachReportTooltips } from "./components.js";
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

function report(id) {
  return state.reports.find((r) => r.id === id) || null;
}

function caseOf(reportId) {
  return state.cases.find((c) => c.report_id === reportId) || null;
}

// Builds the secondary line for a resolved location: the broader context that
// follows the specific place name in Nominatim's full display string.
function locationSub(loc, name) {
  if (loc && loc.full && typeof name === "string" && strStartsWith(loc.full, name)) {
    return loc.full.slice(name.length).replace(/^\s*,\s*/, "");
  }
  if (loc && loc.road) return loc.road;
  return "";
}

function strStartsWith(haystack, needle) {
  return typeof haystack === "string" && haystack.indexOf(needle) === 0;
}

/* ---------- details drawer (map + photo + typewriter) ---------- */

function infoRows(id) {
  const r = report(id);
  if (!r) return "";
  const rows = [
    { label: "Case", value: shortId(r.id) },
    { label: "Reported area", value: titleCase(r.address_text) || "—" },
    { label: "Reporter", value: shortId(r.resident_id) },
    { label: "Animal description", value: r.animal_description || "—" },
    { label: "Latitude", value: r.latitude != null ? String(r.latitude) : "—" },
    { label: "Longitude", value: r.longitude != null ? String(r.longitude) : "—" },
    { label: "Validation", value: titleCase(r.validation_status) || "—" },
    { label: "Status", value: titleCase(r.status) || "—" },
    { label: "Submitted", value: new Date(r.created_at).toLocaleString() },
  ];
  return rows
    .map(
      (row) => `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(row.label)}</span>
      <span class="dialog-info-value">${esc(row.value)}</span>
    </div>`
    )
    .join("");
}

function typewriter(el, text, speed = 26) {
  let i = 0;
  const cursor = '<span class="tw-cursor">|</span>';
  const step = () => {
    if (i >= text.length) {
      el.innerHTML = text;
      return;
    }
    el.innerHTML = text.slice(0, i) + cursor;
    i += 1;
    setTimeout(step, speed);
  };
  step();
}

function openReportDrawer(id) {
  const r = report(id);
  if (!r) return;
  openDrawer({
    title: "Report details",
    body: `
      <div class="dialog-info">${infoRows(id)}</div>
      <div class="drawer-location">
        <span class="drawer-location-pin"><i data-lucide="map-pin"></i></span>
        <div class="drawer-location-text">
          <div class="drawer-location-name" id="report-detail-location">Resolving location…</div>
          <div class="drawer-location-sub" id="report-detail-location-sub"></div>
        </div>
      </div>
      <div id="report-detail-map" class="drawer-map"></div>
      <div class="drawer-reported">
        <img class="drawer-photo" src="../public/reported.png" alt="Report">
        <span class="drawer-reported-text" id="drawer-reported-text"></span>
      </div>`,
    onMount: (bodyEl) => {
      const lat = Number(r.latitude);
      const lng = Number(r.longitude);
      const mapEl = bodyEl.querySelector("#report-detail-map");
      let marker = null;
      if (window.L && mapEl && Number.isFinite(lat) && Number.isFinite(lng)) {
        const map = window.L.map(mapEl).setView([lat, lng], 15);
        window.L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
          attribution: "&copy; OpenStreetMap contributors",
        }).addTo(map);
        marker = window.L.marker([lat, lng]).addTo(map).bindPopup(esc(r.address_text || "Report location"));
        setTimeout(() => map.invalidateSize(), 300);
      }
      const capEl = bodyEl.querySelector("#drawer-reported-text");
      if (capEl) typewriter(capEl, `Reported at ${r.address_text || "Mati City"}`);

      const locEl = bodyEl.querySelector("#report-detail-location");
      const subEl = bodyEl.querySelector("#report-detail-location-sub");
      const fallback = r.address_text ? titleCase(r.address_text) : "Unknown location";
      if (Number.isFinite(lat) && Number.isFinite(lng)) {
        api.reverseGeocode(lat, lng).then((loc) => {
          const specific = (loc && (loc.name || loc.road || loc.full)) || null;
          const sub = specific ? locationSub(loc, specific) : "";
          if (locEl) {
            locEl.textContent = specific || fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = sub || (specific ? "" : fallback);
          if (specific && marker) marker.setPopupContent(esc(specific));
        }).catch(() => {
          if (locEl) {
            locEl.textContent = fallback;
            locEl.classList.remove("loc-loading");
          }
          if (subEl) subEl.textContent = "";
        });
      } else if (locEl) {
        locEl.textContent = fallback;
        locEl.classList.remove("loc-loading");
      }
    },
  });
}

/* ---------- verify / dismiss ---------- */

async function runVerify(id) {
  const r = report(id);
  const ok = await confirmDialog({
    title: "Verify report",
    message: `Are you sure you want to verify ${shortId(id)}?`,
    info: [
      { label: "Case", value: shortId(id) },
      { label: "Barangay", value: r && r.address_text ? titleCase(r.address_text) : "—" },
      { label: "Reporter", value: shortId(r.resident_id) },
    ],
    confirmText: "Verify",
    cancelText: "Cancel",
    run: () => api.verifyReport(id),
  });
  if (!ok) return;
  const caseId = ok.data && ok.data.case_id;
  toast(caseId ? `Report ${shortId(id)} verified · Case ${shortId(caseId)} created.` : `Report ${shortId(id)} verified.`, {
    type: "success",
  });
  await reloadData();
  rerenderAll();
  createIcons({ icons });
}

async function runDismiss(id) {
  const r = report(id);
  const ok = await confirmDialog({
    title: "Dismiss report",
    message: `Are you sure you want to dismiss ${shortId(id)}?`,
    info: [
      { label: "Case", value: shortId(id) },
      { label: "Barangay", value: r && r.address_text ? titleCase(r.address_text) : "—" },
    ],
    confirmText: "Dismiss",
    cancelText: "Cancel",
    danger: true,
    withReason: true,
    reasonLabel: "Dismiss reason",
    reasonRequired: true,
    run: ({ reason }) => api.dismissReport(id, reason),
  });
  if (!ok) return;
  toast(`Report ${shortId(id)} dismissed.`, { type: "success" });
  await reloadData();
  rerenderAll();
  createIcons({ icons });
}

/* ---------- assign rescuer ---------- */

function assignDialog(caseId, reportId) {
  return new Promise((resolve) => {
    const rescuers = state.rescuers.filter((u) => u.role === "rescuer" && u.account_status === "active");
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
          <p class="dialog-message">Assign a rescuer to case ${shortId(caseId)} (report ${shortId(reportId)}). Only on-duty rescuers can be assigned.</p>
          ${options.length
            ? `<label class="dialog-label" for="assign-rescuer">Rescuer<span class="dialog-req"> *</span></label>
               ${Select({ id: "assign-rescuer", options, placeholder: "Select a rescuer…", className: "w-full" })}`
            : `<div class="empty-state"><i data-lucide="siren"></i><span>No active rescuers available.</span></div>`}
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

/* ---------- events ---------- */

export function initReportsEvents() {
  const main = document.getElementById("app");

  main.addEventListener("click", async (e) => {
    const tab = e.target.closest("button[data-filter]");
    if (tab) {
      state.filter = tab.dataset.filter;
      state.page = 1;
      const filters = document.getElementById("report-filters");
      if (filters) {
        // re-render tab active state without losing the search input focus
        filters.querySelectorAll("[data-filter]").forEach((b) => b.classList.toggle("is-active", b === tab));
      }
      const table = document.getElementById("report-table");
      if (table) {
        table.innerHTML = ReportTable();
        createIcons({ icons });
        attachReportTooltips();
      }
      return;
    }

    const pageBtn = e.target.closest("button[data-page]");
    if (pageBtn) {
      const page = parseInt(pageBtn.dataset.page, 10);
      if (!page || page === state.page) return;
      state.page = page;
      const table = document.getElementById("report-table");
      if (table) {
        table.innerHTML = ReportTable();
        createIcons({ icons });
        attachReportTooltips();
      }
      return;
    }

    const actionEl = e.target.closest("[data-action]");
    if (actionEl) {
      e.preventDefault();
      const action = actionEl.dataset.action;
      const id = actionEl.dataset.id;
      const caseId = actionEl.dataset.case;
      if (action === "verify") return runVerify(id);
      if (action === "dismiss") return runDismiss(id);
      if (action === "assign") {
        assignDialog(caseId, id).then((payload) => {
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

    const row = e.target.closest("tr[data-id]");
    if (row) {
      hideReportMapDrawer();
      openReportDrawer(row.dataset.id);
    }
  });

  main.addEventListener("input", (e) => {
    const s = e.target.closest("#report-search");
    if (!s) return;
    state.query = s.value;
    state.page = 1;
    const table = document.getElementById("report-table");
    if (table) {
      table.innerHTML = ReportTable();
      createIcons({ icons });
      attachReportTooltips();
    }
  });
}