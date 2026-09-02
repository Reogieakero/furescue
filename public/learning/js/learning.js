import { createIcons, icons } from "lucide";
import { apiFetch, apiFetchFull, PORTAL_ROLES, requireAuth } from "/js/lib/api.js";
import { bootstrapPageAuth } from "/js/lib/page-auth.js";
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
  loadError: "",
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

  if (state.loadError) {
    grid.innerHTML = `
      <div class="rcard">
        <div class="rempty">
          <i data-lucide="wifi-off"></i>
          <p class="rempty-title">Could not load modules</p>
          <p class="rempty-text">${esc(state.loadError)}</p>
        </div>
      </div>`;
    createIcons({ icons });
    return;
  }

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
  bootstrapPageAuth();
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
    const done = statusFor(mod.id)?.status === "completed";
    const titleEl = document.getElementById("learn-lesson-title");
    const catEl = document.getElementById("learn-lesson-category");
    const bodyEl = document.getElementById("learn-lesson-content");
    const statusEl = document.getElementById("learn-lesson-status");
    if (titleEl) titleEl.textContent = mod.title;
    if (catEl) catEl.textContent = meta.label;
    if (bodyEl) bodyEl.innerHTML = String(mod.content_body || "");
    if (statusEl) {
      statusEl.textContent = done ? "Completed" : "In progress";
      statusEl.className = `rchip ${done ? "rchip--success" : "rchip--sky"}`;
      statusEl.hidden = false;
    }
    list.classList.add("is-hidden");
    section.classList.remove("is-hidden");

    const btn = document.getElementById("learn-complete");
    if (btn) {
      btn.disabled = done;
      const label = btn.querySelector("span");
      if (label) label.textContent = done ? "Completed" : "Mark Complete";
    }

    createIcons({ icons });
    renderProgress();
    window.scrollTo({ top: 0, behavior: "smooth" });
  } catch (err) {
    toast(err.message || "Could not load that module.", { type: "error" });
  }
}

async function markComplete() {
  if (!state.currentId || state.busy) return;
  bootstrapPageAuth();
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
    const label = btn && btn.querySelector("span");
    if (label) label.textContent = "Completed";
    toast("Module completed. Nice work!", { type: "success" });
  } catch (err) {
    toast(err.message || "Could not save your progress.", { type: "error" });
    if (btn) btn.disabled = false;
  } finally {
    state.busy = false;
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

async function boot() {
  bootstrapPageAuth();
  const user = requireAuth(PORTAL_ROLES);
  if (!user) return;
  initResidentShell();
  bindEvents();

  try {
    const modulesFull = await apiFetchFull("/elearning/modules?per_page=100");
    const items = (modulesFull && modulesFull.data) || [];
    state.modules = Array.isArray(items)
      ? items.filter((m) => m && m.published_status === "published")
      : [];
    state.loadError = "";
  } catch (err) {
    state.loadError = err.message || "Could not load modules.";
    toast(state.loadError, { type: "error" });
  }

  try {
    const progress = await apiFetch("/elearning/progress");
    const rows = (progress && progress.progress) || [];
    state.progress = new Map(
      (Array.isArray(rows) ? rows : []).map((r) => [r.module_id, r])
    );
  } catch {
    toast("Saved progress is temporarily unavailable. You can still read modules.", {
      type: "error",
    });
  }

  renderGrid();
  renderProgress();

  const fromHash = decodeURIComponent(window.location.hash.slice(1));
  if (fromHash && state.modules.some((m) => m.id === fromHash)) {
    void openLesson(fromHash);
  }
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", () => {
    void boot();
  });
} else {
  void boot();
}
