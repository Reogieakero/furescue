import { createIcons, icons } from "lucide";
import { Button } from "../../../../../js/components/ui/button.js";
import { Spinner } from "../../../../../js/components/ui/spinner.js";
import { updateAnimal } from "../../../lib/admin-data.js";
import { parsePhoto360 } from "../state.js";
import { resizeImage } from "./modal.js";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

const STATUS_TO_API = {
  Available: "available",
  Pending: "pending",
  Adopted: "adopted",
  "Not listed": "not_listed",
};
const API_TO_STATUS = {
  available: "Available",
  pending: "Pending",
  adopted: "Adopted",
  not_listed: "Not listed",
};

function tabsHtml(attr, options, active) {
  return options
    .map(
      (o) =>
        `<button type="button" class="q-btn${o.value === active ? " is-active" : ""}" data-${attr}="${esc(o.value)}">${esc(o.label)}</button>`
    )
    .join("");
}

export function openEditAnimalDialog(animal) {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";

    const statusTabs = [
      { value: "not_listed", label: "Just listed" },
      { value: "available", label: "Ready for adoption" },
      { value: "pending", label: "Pending" },
      { value: "adopted", label: "Adopted" },
    ];

    const m = (animal.age || "").match(/^(\d+)\s*(\w+)$/);
    const ageNum = m ? m[1] : "";
    const ageUnitRaw = m ? m[2] : "yr";
    const ageUnit = ageUnitRaw.startsWith("day") ? "day" : ageUnitRaw;

    const form = {
      status: STATUS_TO_API[animal.status] || "not_listed",
      ageUnit,
      photo: animal.photo || null,
    };

    overlay.innerHTML = `
      <div class="dialog dialog--wide" role="dialog" aria-modal="true" aria-labelledby="edit-animal-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="pencil" class="dialog-icon"></i>
            <h3 class="dialog-title" id="edit-animal-title">Edit &middot; ${esc(animal.name)}</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body rescuer-modal-body">
          <div class="edit-animal-readonly">
            <span><i data-lucide="paw-print"></i>${esc(animal.species)}</span>
            <span><i data-lucide="tag"></i>${esc(animal.breed)}</span>
            <span><i data-lucide="venus-mars"></i>${esc(animal.sex)}</span>
          </div>
          <div class="add-animal-form">
            <label class="dialog-label" for="ea-name">Name</label>
            <input class="dialog-input" id="ea-name" value="${esc(animal.name)}" autocomplete="off" />

            <div class="add-animal-row">
              <div class="add-animal-col">
                <label class="dialog-label" for="ea-age">Age</label>
                <input class="dialog-input" id="ea-age" inputmode="numeric" value="${esc(ageNum)}" autocomplete="off" />
                <p class="dialog-error" id="ea-age-error" hidden>Enter numbers only.</p>
              </div>
              <div class="add-animal-col">
                <label class="dialog-label">Unit</label>
                <div class="q-tabs" id="ea-age-tabs">${tabsHtml("unit", [
                  { value: "yr", label: "yr" },
                  { value: "mon", label: "mon" },
                  { value: "day", label: "day" },
                ], form.ageUnit)}</div>
              </div>
            </div>

            <label class="dialog-label">Status</label>
            <div class="q-tabs" id="ea-status-tabs">${tabsHtml("status", statusTabs, form.status)}</div>

            <label class="dialog-label" for="ea-color">Color / markings</label>
            <input class="dialog-input" id="ea-color" value="${esc(animal.barangay)}" autocomplete="off" />

            <label class="dialog-label">Photo</label>
            <div class="aa-photo">
              <div class="aa-photo-preview" id="ea-photo-preview">${
                animal.photo
                  ? `<img src="${esc(animal.photo)}" alt="${esc(animal.name)}">`
                  : `<i data-lucide="image-plus"></i>`
              }</div>
              <input type="file" id="ea-photo" accept="image/*" class="aa-photo-input" />
            </div>

            <label class="dialog-label" for="ea-model3d">3D model URL <span class="dialog-hint">optional — .glb/.gltf/.obj</span></label>
            <input class="dialog-input" id="ea-model3d" value="${esc(animal.model3d || "")}" autocomplete="off" />

            <label class="dialog-label" for="ea-photo360">360° photo set <span class="dialog-hint">optional — JSON array of image URLs</span></label>
            <textarea class="dialog-input aa-textarea" id="ea-photo360" rows="3">${esc(animal.photo360 || "")}</textarea>

            <p class="dialog-error" id="ea-error" hidden></p>
          </div>
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
          ${Button({ text: "Save changes", variant: "default", icon: "check", attrs: 'data-act="ok"' })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });

    const nameEl = overlay.querySelector("#ea-name");
    const ageEl = overlay.querySelector("#ea-age");
    const ageErrorEl = overlay.querySelector("#ea-age-error");
    const colorEl = overlay.querySelector("#ea-color");
    const statusTabsEl = overlay.querySelector("#ea-status-tabs");
    const ageTabsEl = overlay.querySelector("#ea-age-tabs");
    const photoInput = overlay.querySelector("#ea-photo");
    const photoPreview = overlay.querySelector("#ea-photo-preview");
    const errorEl = overlay.querySelector("#ea-error");
    nameEl && nameEl.focus();

    const activate = (container, attr, value) =>
      container.querySelectorAll(".q-btn").forEach((b) => b.classList.toggle("is-active", b.dataset[attr] === value));

    statusTabsEl.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-status]");
      if (!btn) return;
      form.status = btn.dataset.status;
      activate(statusTabsEl, "status", form.status);
    });
    ageTabsEl.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-unit]");
      if (!btn) return;
      form.ageUnit = btn.dataset.unit;
      activate(ageTabsEl, "unit", form.ageUnit);
    });
    if (ageEl) {
      ageEl.addEventListener("input", () => {
        const digits = ageEl.value.replace(/[^0-9]/g, "");
        if (digits !== ageEl.value) {
          ageErrorEl.textContent = "Enter numbers only.";
          ageErrorEl.hidden = false;
        } else {
          ageErrorEl.hidden = true;
        }
        ageEl.value = digits;
      });
    }
    if (photoInput) {
      photoInput.addEventListener("change", () => {
        const file = photoInput.files && photoInput.files[0];
        if (!file) return;
        resizeImage(file, 640, 0.8)
          .then((dataUrl) => {
            form.photo = dataUrl;
            photoPreview.style.backgroundImage = `url("${dataUrl}")`;
            photoPreview.innerHTML = "";
          })
          .catch(() => {});
      });
    }

    const close = () => {
      overlay.remove();
      resolve(null);
    };

    const submit = async () => {
      const name = nameEl.value.trim();
      const age = ageEl && ageEl.value.trim() ? `${ageEl.value.trim()} ${form.ageUnit}` : null;
      const body = {
        name,
        age_estimate: age,
        color_markings: colorEl ? colorEl.value.trim() : null,
        adoption_status: form.status,
        model_3d_url: overlay.querySelector("#ea-model3d").value.trim() || null,
      };
      const photo360Text = overlay.querySelector("#ea-photo360").value;
      try {
        body.photo_360_set = parsePhoto360(photo360Text);
      } catch (err) {
        errorEl.textContent = err.message;
        errorEl.hidden = false;
        return;
      }
      if (form.photo && form.photo !== animal.photo) body.photo_urls = [form.photo];
      const okBtn = overlay.querySelector('[data-act="ok"]');
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Saving…</span>`;
      try {
        const updated = await updateAnimal(animal.id, body);
        overlay.remove();
        resolve(updated || animal);
      } catch (err) {
        okBtn.disabled = false;
        okBtn.innerHTML = `<i data-lucide="check"></i><span>Save changes</span>`;
        createIcons({ icons });
        errorEl.textContent = err && err.message ? err.message : "Failed to save changes.";
        errorEl.hidden = false;
      }
    };

    overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
    overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
    overlay.querySelector(".dialog-x").addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
  });
}
