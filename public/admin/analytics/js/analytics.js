import { createIcons, icons } from "lucide";
import { API_BASE_URL, getAccessToken } from "../../../js/lib/api.js";
import { initShell } from "../../js/layout/app-shell.js";
import { toast } from "../../../js/components/ui/toast.js";

const state = {
  range: { start: "", end: "" },
  overview: [],
  trends: [],
  updates: [],
};

const EXPORT_PATHS = {
  overview: "analytics/overview/export",
  "adoption-trends": "analytics/adoption-trends/export",
  "health-updates": "health/updates/export",
};

const JSON_PATHS = {
  overview: "analytics/overview",
  trends: "analytics/adoption-trends",
  updates: "health/updates",
};

const OVERVIEW_LABELS = {
  reports: "Total reports",
  reports_verified: "Reports verified",
  cases: "Total cases",
  cases_resolved: "Cases resolved",
  animals: "Total animals",
  animals_adopted: "Animals adopted",
  adoptions_pending: "Adoptions pending",
  adoptions_completed: "Adoptions completed",
  rescuers_on_duty: "Rescuers on duty",
  residents: "Residents",
};

function esc(value) {
  return String(value ?? "").replace(/[&<>"']/g, (c) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;",
  }[c]));
}

function timeAgo(value) {
  if (!value) return "—";
  const ts = new Date(value).getTime();
  if (Number.isNaN(ts)) return "—";
  const day = new Date(ts);
  day.setHours(0, 0, 0, 0);
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  const diff = Math.round((today - day) / 86400000);
  if (diff === 0) {
    return new Date(ts).toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit", hour12: true });
  }
  if (diff === 1) return "Yesterday";
  if (diff < 7) return `${diff} days ago`;
  return new Date(ts).toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

function shortId(id) {
  if (!id) return "—";
  return `#${String(id).replace(/-/g, "").slice(0, 4).toUpperCase()}`;
}

function emptyState(icon, text) {
  return `<div class="empty-state"><i data-lucide="${icon}"></i><span>${esc(text)}</span></div>`;
}

function mapHealthUpdate(h) {
  const healthy = (h.health_status ?? "") === "healthy";
  const parts = [h.animal_name ?? "", h.breed_type ?? ""].filter((p) => p !== "");
  return {
    id: shortId(h.id),
    animal: parts.length ? parts.join(", ") : "Unnamed animal",
    by: h.logged_by_name || "—",
    when: timeAgo(h.logged_at),
    status: healthy ? "Stable" : "Needs Attention",
    statusCls: healthy ? "stamp--accent" : "stamp--coral",
  };
}

function setWrapMode(wrap, isEmpty) {
  wrap.classList.toggle("queue-empty", isEmpty);
  wrap.classList.toggle("table-wrap", !isEmpty);
}

function renderOverview(rows) {
  const wrap = document.getElementById("table-overview");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("inbox", "No records.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Metric</th><th>Value</th></tr></thead>
      <tbody>${rows.map((r) => `
        <tr>
          <td class="table-cell">${esc(OVERVIEW_LABELS[r.key] ?? r.key ?? "")}</td>
          <td class="table-cell table-cell--mono table-cell--strong">${esc(r.value ?? 0)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

function renderTrends(rows) {
  const wrap = document.getElementById("table-trends");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("bar-chart-3", "No completed adoptions in this range.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Day</th><th>Completed adoptions</th></tr></thead>
      <tbody>${rows.map((t) => `
        <tr>
          <td class="table-cell table-cell--mono">${esc(t.day ?? "")}</td>
          <td class="table-cell table-cell--mono table-cell--strong">${esc(t.completed ?? 0)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

function renderUpdates(rows) {
  const wrap = document.getElementById("table-health");
  if (!wrap) return;
  setWrapMode(wrap, !rows.length);
  if (!rows.length) {
    wrap.innerHTML = emptyState("heart-pulse", "No health updates in this range.");
    return;
  }
  wrap.innerHTML = `
    <table class="table">
      <thead><tr class="table-head"><th>Update</th><th>Animal</th><th>Logged by</th><th>Status</th><th>When</th></tr></thead>
      <tbody>${rows.map(mapHealthUpdate).map((r) => `
        <tr>
          <td class="table-cell table-cell--mono table-cell--strong">${esc(r.id)}</td>
          <td class="table-cell">${esc(r.animal)}</td>
          <td class="table-cell">${esc(r.by)}</td>
          <td class="table-cell"><span class="stamp stamp--sm ${esc(r.statusCls)}">${esc(r.status)}</span></td>
          <td class="table-cell table-cell--mono table-cell--muted">${esc(r.when)}</td>
        </tr>`).join("")}</tbody>
    </table>`;
}

function renderAll() {
  renderOverview(state.overview);
  renderTrends(state.trends);
  renderUpdates(state.updates);
}

function rangeQuery() {
  const params = new URLSearchParams();
  if (state.range.start) params.set("start", state.range.start);
  if (state.range.end) params.set("end", state.range.end);
  const qs = params.toString();
  return qs ? `?${qs}` : "";
}

function updateRangeLabel() {
  const el = document.getElementById("range-label");
  if (!el) return;
  el.textContent = state.range.start && state.range.end
    ? `${state.range.start} to ${state.range.end}`
    : "Last 30 adoption days · 50 latest health updates";
}

async function refreshData() {
  const qs = rangeQuery();
  const [overview, trends, updates] = await Promise.all([
    apiData(JSON_PATHS.overview + qs),
    apiData(JSON_PATHS.trends + qs),
    apiData(JSON_PATHS.updates + qs),
  ]);
  const stats = (overview && overview.stats) || {};
  state.overview = Object.entries(stats).map(([key, value]) => ({ key, value }));
  state.trends = (trends && trends.trends) || [];
  state.updates = (updates && updates.updates) || [];
  renderAll();
}

async function apiData(path) {
  const payload = await apiFetchJson(path);
  return payload && payload.data;
}

async function apiFetchJson(path) {
  const headers = {};
  const token = getAccessToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  const res = await fetch(`${API_BASE_URL}/${path.replace(/^\//, "")}`, { headers });
  let payload = null;
  try {
    payload = await res.json();
  } catch {
    /* non-JSON response */
  }
  if (!res.ok) {
    throw new Error((payload && payload.error && payload.error.message) || `Request failed (${res.status})`);
  }
  return payload;
}

async function downloadExport(metric) {
  const path = EXPORT_PATHS[metric];
  if (!path) return;
  const headers = {};
  const token = getAccessToken();
  if (token) headers.Authorization = `Bearer ${token}`;
  const res = await fetch(`${API_BASE_URL}/${path}${rangeQuery()}`, { headers });
  if (!res.ok) {
    throw new Error(`Export failed (${res.status})`);
  }
  const blob = await res.blob();
  const match = /filename="([^"]+)"/.exec(res.headers.get("Content-Disposition") || "");
  const name = match
    ? match[1]
    : `furescue-${metric}-${new Date().toISOString().slice(0, 10)}.csv`;
  const href = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = href;
  a.download = name;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(href), 1000);
}

function setBusy(busy) {
  document.querySelectorAll("[data-export]").forEach((el) => {
    el.setAttribute("aria-busy", busy ? "true" : "false");
    if (el.disabled !== undefined) el.disabled = busy;
  });
  const apply = document.getElementById("range-apply");
  if (apply) apply.disabled = busy;
}

function bindEvents() {
  document.querySelectorAll("[data-export]").forEach((el) => {
    el.addEventListener("click", async (e) => {
      e.preventDefault();
      if (el.getAttribute("aria-busy") === "true") return;
      setBusy(true);
      try {
        await downloadExport(el.dataset.export);
        toast("CSV downloaded.", { type: "success" });
      } catch (err) {
        console.error(err);
        toast(err.message || "Export failed.", { type: "error" });
      } finally {
        setBusy(false);
      }
    });
  });

  const applyBtn = document.getElementById("range-apply");
  const resetBtn = document.getElementById("range-reset");
  const startInput = document.getElementById("range-start");
  const endInput = document.getElementById("range-end");

  const readInputs = () => {
    state.range.start = startInput ? startInput.value.trim() : "";
    state.range.end = endInput ? endInput.value.trim() : "";
  };

  applyBtn?.addEventListener("click", async () => {
    if (applyBtn.disabled) return;
    readInputs();
    setBusy(true);
    try {
      await refreshData();
      updateRangeLabel();
    } catch (err) {
      console.error(err);
      toast(err.message || "Could not refresh analytics.", { type: "error" });
    } finally {
      setBusy(false);
    }
  });

  resetBtn?.addEventListener("click", async () => {
    if (startInput) startInput.value = "";
    if (endInput) endInput.value = "";
    readInputs();
    setBusy(true);
    try {
      await refreshData();
      updateRangeLabel();
    } catch (err) {
      console.error(err);
      toast(err.message || "Could not refresh analytics.", { type: "error" });
    } finally {
      setBusy(false);
    }
  });

  endInput?.addEventListener("change", () => {
    if (startInput && endInput.value && startInput.value && startInput.value > endInput.value) {
      startInput.value = endInput.value;
    }
  });
}

function initDate() {
  const el = document.getElementById("admin-date");
  if (!el) return;
  el.textContent = new Date().toLocaleDateString("en-US", {
    weekday: "short",
    month: "short",
    day: "numeric",
  });
}

document.addEventListener("DOMContentLoaded", () => {
  Object.assign(state, window.__PAGE_STATE__ || {});
  createIcons({ icons });
  initShell();
  initDate();
  bindEvents();
});
