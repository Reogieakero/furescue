import { esc } from "../../health-records/components/util.js";
import { cap, selectField, ADOPTION_OPTIONS } from "../util.js";
import { HEALTH_READY_HINT, isHealthReady } from "../actions.js";

export function ProfilePanel(r, { editing = false } = {}) {
  const ready = isHealthReady(r);
  const adoptionOptions = ADOPTION_OPTIONS.filter(
    (o) => o.value !== "available" || ready || r.adoptionStatus === "available"
  );
  const photo = r.photoUrl
    ? `<img src="${esc(r.photoUrl)}" alt="${esc(r.name)}" class="hr-photo">`
    : `<span class="hr-photo hr-photo--ph">${esc((r.name || "?").charAt(0).toUpperCase())}</span>`;

  const rows = [
    ["paw-print", "Species", r.species],
    ["dog", "Breed", r.breedType],
    ["venus-mars", "Sex", r.sex],
    ["calendar", "Age", r.ageEstimate],
    ["calendar-check", "Date of birth", r.birthDate],
    ["map-pin", "Location", r.barangay],
    ["heart-handshake", "Status", r.adoptionStatus],
  ].filter((row) => row[2] != null && row[2] !== "");

  const editFields = editing
    ? `<div class="hr-edit-fields">
        <label class="dialog-label">Name<input class="hr-input" id="hr-name" value="${esc(r.name || "")}" autocomplete="off"></label>
        <label class="dialog-label">Adoption status${selectField({
          id: "hr-adoption-status",
          name: "adoption_status",
          options: adoptionOptions,
          value: r.adoptionStatus || "not_listed",
          placeholder: "Status",
        })}</label>
        ${ready ? "" : `<p class="health-ready-hint">${esc(HEALTH_READY_HINT)}</p>`}
      </div>`
    : "";

  return `
  <section class="panel hr-profile-panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="dog"></i><h3 class="panel-title">Animal Profile</h3></div>
    </div>
    <div class="panel-body hr-profile-body">
      <div class="hr-profile">
        ${photo}
        <div class="hr-profile-info">
          <h2 class="hr-profile-name">${esc(cap(r.name))}</h2>
          <div class="hr-info-card">
            <ul class="hr-detail-list">
            ${rows
              .map(
                ([ic, label, val]) => `
              <li class="hr-detail-row">
                <i data-lucide="${ic}" class="hr-detail-ic"></i>
                <div class="hr-detail-text">
                  <span class="hr-detail-label">${esc(label)}</span>
                  <span class="hr-detail-value">${esc(cap(String(val)))}</span>
                </div>
              </li>`
              )
              .join("")}
            </ul>
            ${editFields}
          </div>
        </div>
      </div>
    </div>
  </section>`;
}
