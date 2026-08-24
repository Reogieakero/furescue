import { createIcons, icons } from "lucide";
import { openDrawer } from "/js/components/ui/drawer.js";
import { Spinner } from "/js/components/ui/spinner.js";
import * as api from "/admin/js/lib/admin-data.js";
import { esc } from "../components/util.js";
import { shortId, titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { report, infoRows, typewriter, locationSub } from "./helpers.js";

export function openReportDrawer(id) {
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
        <img class="drawer-photo" src="/reported.png" alt="Report">
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

const ACTION_LABEL = {
  assigned: "Rescuer assigned",
  status_change: "Status updated",
  created: "Case created",
  note: "Note added",
  verified: "Report verified",
};

function timelineItem(a) {
  const label = ACTION_LABEL[a.action] || titleCase(String(a.action || "activity").replace(/_/g, " "));
  const icon = { assigned: "user-plus", status_change: "refresh-cw", created: "file-plus-2", note: "sticky-note", verified: "badge-check" }[a.action] || "circle-dot";
  const actor = a.actor_role ? (a.actor_role === "admin" ? "Admin" : titleCase(a.actor_role)) : "System";
  const when = a.created_at ? new Date(a.created_at).toLocaleString() : "";
  return `
    <div class="timeline-item">
      <div class="timeline-dot"><i data-lucide="${icon}"></i></div>
      <div class="timeline-body">
        <div class="timeline-title">${esc(label)}</div>
        <div class="timeline-meta">${esc(actor)}${a.actor_id ? ` &middot; ${esc(a.actor_id)}` : ""}${when ? ` &middot; ${esc(when)}` : ""}</div>
        ${a.notes ? `<div class="timeline-notes">${esc(a.notes)}</div>` : ""}
      </div>
    </div>`;
}

export async function openTimelineDrawer(caseId, reportId) {
  const r = report(reportId);
  openDrawer({
    title: "Case timeline",
    body: `<div id="case-timeline" class="timeline"><span class="tl-loading">${Spinner({ size: 16 })} Loading activity…</span></div>`,
    onMount: async (bodyEl) => {
      const wrap = bodyEl.querySelector("#case-timeline");
      let activity = [];
      try {
        activity = await api.fetchCaseActivity(caseId);
      } catch {
        if (wrap) wrap.innerHTML = `<div class="drawer-empty"><i data-lucide="alert-circle"></i><span>Unable to load timeline.</span></div>`;
        createIcons({ icons });
        return;
      }
      if (!activity.length) {
        if (wrap) wrap.innerHTML = `<div class="drawer-empty"><i data-lucide="clock"></i><span>No activity recorded yet.</span></div>`;
        createIcons({ icons });
        return;
      }
      const submitted = r ? new Date(r.created_at).toLocaleString() : "";
      const head = `
        <div class="timeline-item">
          <div class="timeline-dot timeline-dot--open"><i data-lucide="file-text"></i></div>
          <div class="timeline-body">
            <div class="timeline-title">Report submitted</div>
            <div class="timeline-meta">${r ? `Reporter ${shortId(r.resident_id)}` : "Reporter"}${submitted ? ` &middot; ${esc(submitted)}` : ""}</div>
          </div>
        </div>`;
      const tail = `
        <div class="timeline-item">
          <div class="timeline-dot timeline-dot--now"><i data-lucide="circle-dot"></i></div>
          <div class="timeline-body">
            <div class="timeline-title">Now</div>
            <div class="timeline-meta">Awaiting the next step in this case.</div>
          </div>
        </div>`;
      if (wrap) wrap.innerHTML = head + activity.map(timelineItem).join("") + tail;
      createIcons({ icons });
    },
  });
}
