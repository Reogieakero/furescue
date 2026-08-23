import { createIcons, icons } from "lucide";
import { apiFetch, apiFetchFull, requireAuth } from "/js/lib/api.js";
import { esc } from "/js/lib/format.js";
import { initResidentShell } from "/js/components/resident-shell.js";
import { toast } from "/js/components/ui/toast.js";

const CATEGORY_META = {
  dog_behavior: { label: "Dog Behavior", icon: "dog" },
  cat_behavior: { label: "Cat Behavior", icon: "cat" },
  basic_training: { label: "Basic Training", icon: "award" },
  general_care: { label: "General Care", icon: "heart-pulse" },
};

const state = {
  modules: [],
  progress: new Map(),
  currentId: null,
  busy: false,
};

function categoryMeta(category) {
  return CATEGORY_META[category] || { label: String(category || "Module").replaceAll("_", " "), icon: "book-open" };
}

function statusFor(moduleId) {
  return state.progress.get(moduleId) || null;
}

function statusChip(moduleId) {
  const row = statusFor(moduleId);
  if (!row) return "";
  if (row.status === "completed") return '<span class="rchip rchip--success">Completed</span>';
  if (row.status === "in_progress") return '<span class="rchip rchip--sky">In progress</span>';
  return "";
}

function renderGrid() {
  const grid = document.getElementById("learn-grid");
  if (!grid) return;

  if (!state.modules.length) {
    grid.innerHTML = `
      <div class="rcard">
        <div class="rempty">
          <i data-lucide="book-open"></i>
          <p class="rempty-title">No modules yet</p>
          <p class="rempty-text">Learning modules will appear here once the City Veterinary Office publishes them.</p>
        </div>
      </div>`;
    createIcons({ icons });
    return;
  }

  grid.innerHTML = state.modules
    .map((m) => {
      const meta = categoryMeta(m.category);
      const done = statusFor(m.id)?.status === "completed";
      const excerpt = String(m.content_body || "").replace(/<[^>]*>/g, " ").replace(/\s+/g, " ").trim();
      return `
      <button type="button" class="rcard learn-card" data-module="${esc(m.id)}" aria-label="Open module: ${esc(m.title)}">
        <div class="learn-card-top">
          <span class="learn-card-icon"><i data-lucide="${esc(meta.icon)}"></i></span>
          ${statusChip(m.id)}
        </div>
        <span class="rchip rchip--brand">${esc(meta.label)}</span>
        <span class="learn-card-title">${esc(m.title)}</span>
        <p class="learn-card-desc">${esc(excerpt || "Read this short module to learn the basics.")}</p>
        <div class="learn-card-foot">
          <span class="learn-card-cta"><i data-lucide="${done ? "rotate-ccw" : "play"}"></i>${done ? "Read again" : "Start"}</span>
        </div>
      </button>`;
    })
    .join("");
  createIcons({ icons });
}

function renderProgress() {
  const chip = document.getElementById("learn-progress-chip");
  const fill = document.querySelector("#learn-progress-bar .learn-progress-fill");
  const bar = document.getElementById("learn-progress-bar");
  const note = document.getElementById("learn-progress-note");
  const total = state.modules.length;
  const completed = state.modules.filter((m) => statusFor(m.id)?.status === "completed").length;
  const pct = total ? Math.round((completed / total) * 100) : 0;

  if (chip) chip.textContent = `${completed} of ${total} completed`;
  if (fill) fill.style.width = `${pct}%`;
  if (bar) bar.setAttribute("aria-valuenow", String(pct));
  if (note) {
    note.textContent = !total
      ? "No published modules yet."
      : completed === total
        ? "Great job — you have completed every available module!"
        : `Keep going! ${total - completed} module${total - completed === 1 ? "" : "s"} left to complete.`;
  }
}

function showList() {
  state.currentId = null;
  if (window.location.hash) history.replaceState(null, "", window.location.pathname);
  document.getElementById("learn-lesson-section")?.classList.add("is-hidden");
  document.getElementById("learn-list-section")?.classList.remove("is-hidden");
}

async function openLesson(moduleId) {
  const section = document.getElementById("learn-lesson-section");
  const list = document.getElementById("learn-list-section");
  if (!section || !list) return;

  try {
    const data = await apiFetch(`/elearning/modules/${encodeURIComponent(moduleId)}`);
    const mod = data && data.module;
    if (!mod) throw new Error("Module not found");

    state.currentId = mod.id;
    if (statusFor(mod.id) == null) {
      state.progress.set(mod.id, { module_id: mod.id, status: "in_progress", completed_at: null });
      void apiFetch("/elearning/progress", {
        method: "POST",
        body: { module_id: mod.id, status: "in_progress" },
      }).catch(() => {});
    }

    const meta = categoryMeta(mod.category);
    const titleEl = document.getElementById("learn-lesson-title");
    const catEl = document.getElementById("learn-lesson-category");
    const bodyEl = document.getElementById("learn-lesson-content");
    const statusEl = document.getElementById("learn-lesson-status");
    if (titleEl) titleEl.textContent = mod.title;
    if (catEl) catEl.textContent = meta.label;
    if (bodyEl) bodyEl.innerHTML = String(mod.content_body || "");
    if (statusEl) {
      const done = statusFor(mod.id)?.status === "completed";
      statusEl.textContent = done ? "Completed" : "In progress";
      statusEl.className = `rchip ${done ? "rchip--success" : "rchip--sky"}`;
      statusEl.hidden = false;
    }
    const btn = document.getElementById("learn-complete");
    if (btn) {
      btn.disabled = done;
      btn.querySelector("span").textContent = done ? "Completed" : "Mark Complete";
    }

    list.classList.add("is-hidden");
    section.classList.remove("is-hidden");
    createIcons({ icons });
    renderProgress();
    window.scrollTo({ top: 0, behavior: "smooth" });
  } catch (err) {
    toast(err.message || "Could not load that module.", { type: "error" });
  }
}

async function markComplete() {
  if (!state.currentId || state.busy) return;
  const btn = document.getElementById("learn-complete");
  state.busy = true;
  if (btn) btn.disabled = true;
  try {
    await apiFetch("/elearning/progress", {
      method: "POST",
      body: { module_id: state.currentId, status: "completed" },
    });
    state.progress.set(state.currentId, {
      module_id: state.currentId,
      status: "completed",
      completed_at: new Date().toISOString().slice(0, 19).replace("T", " "),
    });
    const statusEl = document.getElementById("learn-lesson-status");
    if (statusEl) {
      statusEl.textContent = "Completed";
      statusEl.className = "rchip rchip--success";
    }
    if (btn) btn.querySelector("span").textContent = "Completed";
    toast("Module completed. Nice work!", { type: "success" });
  } catch (err) {
    toast(err.message || "Could not save your progress.", { type: "error" });
  } finally {
    state.busy = false;
    if (btn) btn.disabled = false;
    renderProgress();
    renderGrid();
  }
}

function bindEvents() {
  const grid = document.getElementById("learn-grid");
  grid?.addEventListener("click", (e) => {
    const card = e.target.closest("[data-module]");
    if (!card) return;
    history.replaceState(null, "", `#${card.dataset.module}`);
    void openLesson(String(card.dataset.module));
  });

  document.getElementById("learn-back")?.addEventListener("click", () => {
    renderGrid();
    renderProgress();
    showList();
  });

  document.getElementById("learn-complete")?.addEventListener("click", markComplete);

  window.addEventListener("hashchange", () => {
    const id = decodeURIComponent(window.location.hash.slice(1));
    if (id) void openLesson(id);
    else showList();
  });
}

document.addEventListener("DOMContentLoaded", async () => {
  const user = requireAuth();
  if (!user) return;
  initResidentShell();
  bindEvents();

  try {
    const [modulesFull, progress] = await Promise.all([
      apiFetchFull("/elearning/modules?per_page=100"),
      apiFetch("/elearning/progress"),
    ]);
    const items = (modulesFull && modulesFull.data) || [];
    state.modules = Array.isArray(items)
      ? items.filter((m) => m && m.published_status === "published")
      : [];
    const rows = (progress && progress.progress) || [];
    state.progress = new Map(
      (Array.isArray(rows) ? rows : []).map((r) => [r.module_id, r])
    );
  } catch (err) {
    toast(err.message || "Could not load modules.", { type: "error" });
  }

  renderGrid();
  renderProgress();

  const fromHash = decodeURIComponent(window.location.hash.slice(1));
  if (fromHash && state.modules.some((m) => m.id === fromHash)) {
    void openLesson(fromHash);
  }
});
