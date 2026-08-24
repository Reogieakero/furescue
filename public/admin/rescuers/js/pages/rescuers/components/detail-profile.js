import { esc } from "./util.js";
import { shortId, titleCase } from "/admin/js/pages/dashboard/helpers.js";
import { rescuerAvatar } from "/admin/js/pages/dashboard/components/util.js";

export function metaRows(r) {
  const duty = r.duty_status || "off_duty";
  const dutyCls = duty === "on_duty" ? "stamp--accent" : "stamp--muted";
  return [
    ["Email", r.email || "—"],
    ["Phone", r.phone_number || "—"],
    ["Duty", `<span class="stamp stamp--sm ${dutyCls}">${duty === "on_duty" ? "On duty" : "Off duty"}</span>`],
    [
      "Status",
      `<span class="stamp stamp--sm ${r.account_status === "suspended" ? "stamp--muted" : "stamp--accent"}">${esc(titleCase(r.account_status))}</span>`,
    ],
    ["Joined", r.created_at ? new Date(r.created_at).toLocaleDateString() : "—"],
  ];
}

export function RescuerMeta(r, rows) {
  return `
  <dl class="rescuer-detail-meta">
    ${(rows || metaRows(r))
      .map(([k, v]) => `<div class="rescuer-detail-row"><dt>${esc(k)}</dt><dd>${v}</dd></div>`)
      .join("")}
  </dl>`;
}

export function RescuerInfo(r) {
  return `
  <div class="rescuer-detail-head">
    ${rescuerAvatar(r.profile_photo_url, r.full_name)}
    <div class="rescuer-detail-id">
      <div class="rescuer-detail-name">${esc(r.full_name || "Unnamed")}</div>
      <div class="rescuer-detail-sub">${esc(shortId(r.id))}</div>
    </div>
    <button class="rescuer-expand map-expand" data-act="expand" title="Open in drawer" aria-label="Open in drawer">
      <i data-lucide="maximize"></i>
    </button>
  </div>
  ${RescuerMeta(r, metaRows(r))}`;
}
