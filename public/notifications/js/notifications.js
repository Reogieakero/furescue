import { createIcons, icons } from "lucide";
import { apiFetch, requireAuth } from "/js/lib/api.js";
import { esc, timeAgo } from "/js/lib/format.js";
import { initResidentShell, setResidentNavBadge } from "/js/components/resident-shell.js";
import { toast } from "/js/components/ui/toast.js";

const TYPE_STYLE = [
  [/(report_)?dismiss/, { icon: "x-circle", tone: "alert", label: "Dismissed" }],
  [/approved|verified|completed|accepted/, { icon: "check-circle-2", tone: "success" }],
  [/rejected|rejected_listing|error/, { icon: "alert-circle", tone: "alert" }],
  [/message/, { icon: "message-square", tone: "" }],
  [/adoption|listing|adopt/, { icon: "heart", tone: "" }],
  [/case|rescue/, { icon: "ambulance", tone: "" }],
  [/report/, { icon: "map-pin", tone: "" }],
  [/announce|broadcast/, { icon: "megaphone", tone: "" }],
];

const RELATED_LINK = {
  report: { href: "/reports/", label: "View report" },
  case: { href: "/reports/", label: "View case" },
  adoption: { href: "/adoptions/", label: "View adoption" },
};

const state = {
  items: [],
  filter: "all",
};

function styleFor(type) {
  const t = String(type || "").toLowerCase();
  for (const [pattern, style] of TYPE_STYLE) {
    if (pattern.test(t)) return style;
  }
  return { icon: "bell", tone: "" };
}

function relatedFor(n) {
  if (!n.related_type || !RELATED_LINK[n.related_type]) return null;
  return RELATED_LINK[n.related_type];
}

function unreadCount() {
  return state.items.filter((n) => !n.is_read).length;
}

function syncBadge() {
  const count = unreadCount();
  setResidentNavBadge("notifications", count);
}

function rowHtml(n) {
  const style = styleFor(n.type);
  const related = relatedFor(n);
  const unread = !n.is_read;
  const clickable = Boolean(related);
  return `
  <li
    class="notif-item ${unread ? "is-unread" : ""} ${clickable ? "is-clickable" : ""}"
    data-id="${esc(n.id)}"
    ${clickable ? `data-href="${esc(related.href)}" tabindex="0" role="link"` : ""}
  >
    <span class="notif-icon ${style.tone ? `notif-icon--${style.tone}` : ""}">
      <i data-lucide="${esc(style.icon)}"></i>
      ${unread ? '<span class="notif-dot" aria-hidden="true"></span>' : ""}
    </span>
    <span class="notif-body">
      <p class="notif-msg">${esc(n.message)}</p>
      <span class="notif-meta">
        <span class="notif-time">${esc(timeAgo(n.created_at))}</span>
        ${related ? `<span class="notif-link">${esc(related.label)} <i data-lucide="chevron-right"></i></span>` : ""}
      </span>
    </span>
    ${
      unread
        ? `<button type="button" class="notif-mark-btn" data-mark="${esc(n.id)}" aria-label="Mark as read">
             <i data-lucide="check"></i>
           </button>`
        : '<span aria-hidden="true"></span>'
    }
  </li>`;
}

function renderList() {
  const list = document.getElementById("notif-list");
  if (!list) return;

  const visible =
    state.filter === "unread" ? state.items.filter((n) => !n.is_read) : state.items;

  if (!visible.length) {
    const empty =
      state.filter === "unread"
        ? {
            icon: "bell-off",
            title: "You're all caught up",
            text: "No unread notifications right now.",
          }
        : {
            icon: "bell",
            title: "Nothing here yet",
            text: "Updates about your reports, adoptions, and messages will appear here.",
          };
    list.innerHTML = `
      <li class="rempty">
        <i data-lucide="${empty.icon}"></i>
        <p class="rempty-title">${empty.title}</p>
        <p class="rempty-text">${empty.text}</p>
      </li>`;
    createIcons({ icons });
    return;
  }

  list.innerHTML = visible.map(rowHtml).join("");
  createIcons({ icons });
}

function renderUnreadChip() {
  const chip = document.getElementById("notif-unread-count");
  if (!chip) return;
  const count = unreadCount();
  chip.hidden = count === 0;
  chip.textContent = String(count);
}

async function loadNotifications({ silent = false } = {}) {
  try {
    const items = await apiFetch("/notifications?per_page=100");
    state.items = Array.isArray(items) ? items : [];
    renderList();
    renderUnreadChip();
    syncBadge();
  } catch (err) {
    if (!silent) toast(err.message || "Could not load notifications.", { type: "error" });
  }
}

async function markOne(id) {
  try {
    await apiFetch(`/notifications/${encodeURIComponent(id)}/read`, { method: "PATCH" });
    const item = state.items.find((n) => n.id === id);
    if (item) item.is_read = true;
    renderList();
    renderUnreadChip();
    syncBadge();
  } catch (err) {
    toast(err.message || "Could not mark as read.", { type: "error" });
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const user = requireAuth();
  if (!user) return;
  initResidentShell();

  document.querySelectorAll(".notif-tab").forEach((tab) => {
    tab.addEventListener("click", () => {
      state.filter = String(tab.dataset.filter || "all");
      document.querySelectorAll(".notif-tab").forEach((t) => {
        const active = t === tab;
        t.classList.toggle("is-active", active);
        t.setAttribute("aria-selected", String(active));
      });
      renderList();
    });
  });

  document.getElementById("notif-mark-all")?.addEventListener("click", async () => {
    if (!unreadCount()) {
      toast("No unread notifications.", { type: "info" });
      return;
    }
    try {
      await apiFetch("/notifications/read-all", { method: "POST" });
      state.items.forEach((n) => {
        n.is_read = true;
      });
      renderList();
      renderUnreadChip();
      syncBadge();
      toast("All notifications marked as read.", { type: "success" });
    } catch (err) {
      toast(err.message || "Could not mark all as read.", { type: "error" });
    }
  });

  const list = document.getElementById("notif-list");
  const openRow = (row) => {
    void markOne(String(row.dataset.id));
    const href = row.dataset.href;
    if (href) window.location.href = href;
  };

  list?.addEventListener("click", (e) => {
    const markBtn = e.target.closest("[data-mark]");
    if (markBtn) {
      e.stopPropagation();
      void markOne(String(markBtn.dataset.mark));
      return;
    }
    const row = e.target.closest(".notif-item[data-id]");
    if (row && row.dataset.href) openRow(row);
  });

  list?.addEventListener("keydown", (e) => {
    if (e.key !== "Enter" && e.key !== " ") return;
    const row = e.target.closest(".notif-item[data-id]");
    if (!row || !row.dataset.href || e.target !== row) return;
    e.preventDefault();
    openRow(row);
  });

  void loadNotifications();
});
