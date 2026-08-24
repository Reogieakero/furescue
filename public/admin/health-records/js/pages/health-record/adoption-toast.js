import { createIcons, icons } from "lucide";
import { toast } from "/js/components/ui/toast.js";
import { esc } from "../health-records/components/util.js";
import { createAdoptionListing } from "/admin/js/lib/admin-data.js";
import { ui, record } from "./context.js";

const ADOPTION_NOTIFY_MS = 15 * 60 * 1000;
const ADOPTION_STORE_KEY = "furescue_adoption_ready";
let adoptionToastEl = null;

function ensureToastViewport() {
  let vp = document.querySelector(".toast-viewport--adoption");
  if (!vp) {
    vp = document.createElement("div");
    vp.className = "toast-viewport toast-viewport--adoption";
    vp.setAttribute("aria-live", "polite");
    document.body.appendChild(vp);
  }
  return vp;
}

function readAdoptionStore() {
  try {
    const raw = localStorage.getItem(ADOPTION_STORE_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

function writeAdoptionStore(entry) {
  try {
    if (entry) localStorage.setItem(ADOPTION_STORE_KEY, JSON.stringify(entry));
    else localStorage.removeItem(ADOPTION_STORE_KEY);
  } catch {
    /* storage unavailable */
  }
}

export function maybeNotifyAdoptionReady(kind) {
  if (kind === "vaccination") ui.addedVaccination = true;
  if (kind === "vital") ui.addedVital = true;
  if (adoptionToastEl || !ui.addedVaccination || !ui.addedVital) return;
  if (!record || !record.id) return;
  const entry = {
    animalId: record.id,
    animalName: record.name || "this animal",
    createdAt: Date.now(),
    expiresAt: Date.now() + ADOPTION_NOTIFY_MS,
    listed: false,
  };
  writeAdoptionStore(entry);
  showAdoptionToast(entry);
}

export function maybeNotifyFromRecord() {
  if (adoptionToastEl || !record || !record.id) return;
  const hasVaccination = Array.isArray(record.vaccinations) && record.vaccinations.length > 0;
  const hasVital = Array.isArray(record.vitals) && record.vitals.length > 0;
  if (!hasVaccination || !hasVital) return;
  const stored = readAdoptionStore();
  if (stored && (stored.listed || stored.animalId === record.id)) return;
  const entry = {
    animalId: record.id,
    animalName: record.name || "this animal",
    createdAt: Date.now(),
    expiresAt: Date.now() + ADOPTION_NOTIFY_MS,
    listed: false,
  };
  writeAdoptionStore(entry);
  showAdoptionToast(entry);
}

async function autoListOnExpiry(entry) {
  if (entry.listed) return;
  entry.listed = true;
  writeAdoptionStore(entry);
  try {
    await createAdoptionListing(entry.animalId);
    toast(`${esc(entry.animalName)} moved to adoption ready.`, { type: "success" });
  } catch {
    entry.listed = false;
    writeAdoptionStore(entry);
  }
  dismissAdoptionToast();
}

function showAdoptionToast(entry) {
  const stored = entry || readAdoptionStore();
  if (!stored) return;
  const now = Date.now();
  const expiresAt = stored.expiresAt || now + ADOPTION_NOTIFY_MS;
  if (expiresAt <= now && !stored.listed) {
    autoListOnExpiry(stored);
    return;
  }

  const viewport = ensureToastViewport();
  const el = document.createElement("div");
  el.className = "toast toast--adoption is-visible";
  el.setAttribute("role", "status");
  el.innerHTML = `
    <i data-lucide="list-plus" class="toast-icon"></i>
    <div class="toast-adoption-body">
      <p class="toast-message">Health record updated. You can list <strong>${esc(stored.animalName || "this animal")}</strong> for adoption.</p>
      <div class="toast-countdown">
        <div class="toast-countdown-bar"><span class="toast-countdown-fill"></span></div>
        <span class="toast-countdown-text">Ready in 15:00</span>
      </div>
      <button type="button" class="toast-adoption-btn">Add to adoption list</button>
    </div>
    <button class="toast-close" aria-label="Dismiss"><i data-lucide="x"></i></button>`;
  viewport.appendChild(el);
  createIcons({ icons });
  adoptionToastEl = el;

  const fill = el.querySelector(".toast-countdown-fill");
  const text = el.querySelector(".toast-countdown-text");
  const btn = el.querySelector(".toast-adoption-btn");
  const tick = () => {
    const remaining = Math.max(0, expiresAt - Date.now());
    const mins = Math.floor(remaining / 60000);
    const secs = Math.floor((remaining % 60000) / 1000);
    text.textContent = `Ready in ${String(mins).padStart(2, "0")}:${String(secs).padStart(2, "0")}`;
    fill.style.width = `${(remaining / ADOPTION_NOTIFY_MS) * 100}%`;
    if (remaining <= 0) {
      clearInterval(timer);
      btn.disabled = true;
      text.textContent = "Ready to list";
      autoListOnExpiry(stored);
    }
  };
  const timer = setInterval(tick, 1000);
  tick();

  btn.addEventListener("click", async () => {
    if (btn.disabled) return;
    btn.disabled = true;
    btn.innerHTML = `<span>Saving…</span>`;
    try {
      await createAdoptionListing(stored.animalId);
      stored.listed = true;
      writeAdoptionStore(stored);
      btn.innerHTML = `<span>Added to adoption list</span>`;
      toast("Added to adoption list.", { type: "success" });
      setTimeout(dismissAdoptionToast, 1500);
    } catch (err) {
      btn.disabled = false;
      btn.innerHTML = `<span>Add to adoption list</span>`;
      toast(err && err.message ? err.message : "Could not add to adoption list.", { type: "error" });
    }
  });

  el.querySelector(".toast-close").addEventListener("click", dismissAdoptionToast);
}

export function restoreAdoptionToast() {
  const stored = readAdoptionStore();
  if (stored && !stored.listed && !adoptionToastEl) showAdoptionToast(stored);
}

export function dismissAdoptionToast() {
  if (!adoptionToastEl) return;
  const el = adoptionToastEl;
  adoptionToastEl = null;
  el.classList.remove("is-visible");
  setTimeout(() => el.remove(), 200);
}
