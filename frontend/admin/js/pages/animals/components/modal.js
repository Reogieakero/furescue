import { createIcons, icons } from "lucide";
import { Button } from "../../../../../js/components/ui/button.js";
import { Spinner } from "../../../../../js/components/ui/spinner.js";
import { addAnimal } from "../state.js";

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function tabsHtml(attr, options, active) {
  return options
    .map(
      (o) =>
        `<button type="button" class="q-btn${o.value === active ? " is-active" : ""}" data-${attr}="${esc(o.value)}">${esc(o.label)}</button>`
    )
    .join("");
}

export function resizeImage(file, maxDim, quality) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.onerror = reject;
    reader.onload = () => {
      const img = new Image();
      img.onerror = reject;
      img.onload = () => {
        const scale = Math.min(1, maxDim / Math.max(img.width, img.height));
        const w = Math.round(img.width * scale);
        const h = Math.round(img.height * scale);
        const canvas = document.createElement("canvas");
        canvas.width = w;
        canvas.height = h;
        canvas.getContext("2d").drawImage(img, 0, 0, w, h);
        resolve(canvas.toDataURL("image/jpeg", quality));
      };
      img.src = reader.result;
    };
    reader.readAsDataURL(file);
  });
}

export function openAddAnimalDialog() {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "dialog-overlay";

    const speciesTabs = [
      { value: "dog", label: "Dog" },
      { value: "cat", label: "Cat" },
    ];
    const sexTabs = [
      { value: "male", label: "Male" },
      { value: "female", label: "Female" },
    ];
    const breedTabs = [
      { value: "aspin", label: "Aspin" },
      { value: "puspin", label: "Puspin" },
    ];
    const statusTabs = [
      { value: "pending", label: "Pending" },
      { value: "adopted", label: "Adopted" },
    ];
    const ageUnitTabs = [
      { value: "yr", label: "yr" },
      { value: "mon", label: "mon" },
      { value: "day", label: "day" },
    ];

    const form = {
      species: "dog",
      breed: "aspin",
      sex: "male",
      status: "pending",
      ageUnit: "yr",
      photo: null,
    };

    overlay.innerHTML = `
      <div class="dialog dialog--wide" role="dialog" aria-modal="true" aria-labelledby="add-animal-title">
        <div class="dialog-head">
          <div class="dialog-title-wrap">
            <i data-lucide="plus-circle" class="dialog-icon"></i>
            <h3 class="dialog-title" id="add-animal-title">Add animal</h3>
          </div>
          <button type="button" class="dialog-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="dialog-body rescuer-modal-body">
          <div class="add-animal-form">
            <label class="dialog-label" for="aa-name">Name <span class="dialog-req">*</span></label>
            <input class="dialog-input" id="aa-name" placeholder="e.g. Bantay" autocomplete="off" />

            <label class="dialog-label">Species</label>
            <div class="q-tabs" id="aa-species-tabs">${tabsHtml("species", speciesTabs, form.species)}</div>

            <label class="dialog-label">Breed <span class="dialog-hint">auto-set by species</span></label>
            <div class="q-tabs" id="aa-breed-tabs">${tabsHtml("breed", breedTabs, form.breed)}</div>

            <div class="add-animal-row">
              <div class="add-animal-col">
                <label class="dialog-label" for="aa-age">Age</label>
                <input class="dialog-input" id="aa-age" inputmode="numeric" placeholder="e.g. 2" autocomplete="off" />
                <p class="dialog-error" id="aa-age-error" hidden>Enter numbers only.</p>
              </div>
              <div class="add-animal-col">
                <label class="dialog-label">Unit</label>
                <div class="q-tabs" id="aa-age-tabs">${tabsHtml("unit", ageUnitTabs, form.ageUnit)}</div>
              </div>
            </div>

            <label class="dialog-label">Sex</label>
            <div class="q-tabs" id="aa-sex-tabs">${tabsHtml("sex", sexTabs, form.sex)}</div>

            <label class="dialog-label">Status</label>
            <div class="q-tabs" id="aa-status-tabs">${tabsHtml("status", statusTabs, form.status)}</div>

            <label class="dialog-label" for="aa-color">Color / markings</label>
            <input class="dialog-input" id="aa-color" placeholder="e.g. Brown with white patch" autocomplete="off" />

            <label class="dialog-label">Photo</label>
            <div class="aa-photo">
              <div class="aa-photo-preview" id="aa-photo-preview"><i data-lucide="image-plus"></i></div>
              <input type="file" id="aa-photo" accept="image/*" class="aa-photo-input" />
            </div>

            <p class="dialog-error" id="aa-error" hidden>Please provide a name.</p>
          </div>
        </div>
        <div class="dialog-foot">
          ${Button({ text: "Cancel", variant: "outline", attrs: 'data-act="cancel"' })}
          ${Button({ text: "Add animal", variant: "default", icon: "plus", attrs: 'data-act="ok"' })}
        </div>
      </div>`;

    document.body.appendChild(overlay);
    createIcons({ icons });

    const nameEl = overlay.querySelector("#aa-name");
    const ageEl = overlay.querySelector("#aa-age");
    const ageErrorEl = overlay.querySelector("#aa-age-error");
    const colorEl = overlay.querySelector("#aa-color");
    const speciesTabsEl = overlay.querySelector("#aa-species-tabs");
    const breedTabsEl = overlay.querySelector("#aa-breed-tabs");
    const sexTabsEl = overlay.querySelector("#aa-sex-tabs");
    const statusTabsEl = overlay.querySelector("#aa-status-tabs");
    const ageTabsEl = overlay.querySelector("#aa-age-tabs");
    const photoInput = overlay.querySelector("#aa-photo");
    const photoPreview = overlay.querySelector("#aa-photo-preview");
    const errorEl = overlay.querySelector("#aa-error");
    nameEl && nameEl.focus();

    const activate = (container, attr, value) =>
      container.querySelectorAll(".q-btn").forEach((b) => b.classList.toggle("is-active", b.dataset[attr] === value));

    speciesTabsEl.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-species]");
      if (!btn) return;
      form.species = btn.dataset.species;
      form.breed = form.species === "cat" ? "puspin" : "aspin";
      activate(speciesTabsEl, "species", form.species);
      activate(breedTabsEl, "breed", form.breed);
    });
    breedTabsEl.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-breed]");
      if (!btn) return;
      form.breed = btn.dataset.breed;
      activate(breedTabsEl, "breed", form.breed);
    });
    sexTabsEl.addEventListener("click", (e) => {
      const btn = e.target.closest("[data-sex]");
      if (!btn) return;
      form.sex = btn.dataset.sex;
      activate(sexTabsEl, "sex", form.sex);
    });
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
      if (!name) {
        errorEl.hidden = false;
        nameEl.focus();
        return;
      }
      errorEl.hidden = true;
      const age = ageEl && ageEl.value.trim() ? `${ageEl.value.trim()} ${form.ageUnit}` : null;
      const okBtn = overlay.querySelector('[data-act="ok"]');
      okBtn.disabled = true;
      okBtn.innerHTML = `${Spinner({ size: 16 })}<span>Adding…</span>`;
      try {
        const animal = await addAnimal({
          name,
          species: form.species,
          breed: form.breed,
          age,
          sex: form.sex,
          status: form.status,
          color: colorEl ? colorEl.value.trim() : null,
          photo: form.photo,
        });
        overlay.remove();
        resolve(animal);
      } catch (err) {
        okBtn.disabled = false;
        okBtn.innerHTML = `<i data-lucide="plus"></i><span>Add animal</span>`;
        createIcons({ icons });
        errorEl.textContent = err && err.message ? err.message : "Failed to add animal.";
        errorEl.hidden = false;
      }
    };

    overlay.querySelector('[data-act="cancel"]').addEventListener("click", close);
    overlay.querySelector('[data-act="ok"]').addEventListener("click", submit);
    overlay.querySelector(".dialog-x").addEventListener("click", close);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) close();
    });
    overlay.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && e.target.tagName !== "TEXTAREA" && e.target.type !== "file") {
        e.preventDefault();
        submit();
      }
    });
  });
}
