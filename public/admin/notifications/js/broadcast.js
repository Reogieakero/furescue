import { createIcons, icons } from "lucide";
import { requireAuth, apiFetchFull } from "../../../js/lib/api.js";
import { fetchRecentBroadcasts } from "../../js/lib/admin-data.js";
import { initSelect } from "../../../js/components/ui/select.js";
import { toast } from "../../../js/components/ui/toast.js";

const MAX_LENGTH = 1000;

const TARGET_MAP = {
  all: ["all"],
  "role:resident": ["role:resident"],
  "role:rescuer": ["role:rescuer"],
  staff: ["role:admin", "role:rescuer"],
};

let selectedTarget = "all";

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
  const date = new Date(String(value).replace(" ", "T"));
  if (Number.isNaN(date.getTime())) return "—";
  const now = new Date();
  const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
  const day = new Date(date.getFullYear(), date.getMonth(), date.getDate());
  const diff = Math.round((today - day) / 86400000);
  if (diff === 0) return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  if (diff === 1) return "Yesterday";
  if (diff < 7) return `${diff} days ago`;
  return date.toLocaleDateString("en-US", { month: "short", day: "numeric" });
}

function rowHtml(broadcast) {
  const recipients = parseInt(broadcast.recipients, 10) || 0;
  return `
    <tr>
      <td class="table-cell table-cell--strong">${esc(broadcast.message)}</td>
      <td class="table-cell"><span class="stamp stamp--sm stamp--accent">${recipients} sent</span></td>
      <td class="table-cell table-cell--mono table-cell--muted">${esc(timeAgo(broadcast.created_at))}</td>
    </tr>`;
}

function renderBroadcasts(items) {
  const rows = document.getElementById("broadcast-rows");
  const totalEl = document.getElementById("broadcast-total");
  if (!rows) return;
  rows.innerHTML = items.length
    ? items.map(rowHtml).join("")
    : '<tr><td class="table-cell table-cell--muted" colspan="3">No broadcasts yet.</td></tr>';
  if (totalEl) totalEl.textContent = String(items.length);
  createIcons({ icons });
}

async function refreshBroadcasts() {
  try {
    renderBroadcasts(await fetchRecentBroadcasts());
  } catch {
    /* keep server-rendered rows on failure */
  }
}

function initCounter() {
  const message = document.getElementById("broadcast-message");
  const counter = document.getElementById("broadcast-count");
  if (!message || !counter) return;
  const update = () => {
    counter.textContent = String(message.value.length);
  };
  message.addEventListener("input", update);
  update();
}

function initForm() {
  const form = document.getElementById("broadcast-form");
  const sendBtn = document.getElementById("broadcast-send");
  if (!form || !sendBtn) return;
  form.addEventListener("submit", (e) => {
    e.preventDefault();
    send();
  });

  async function send() {
    const messageEl = document.getElementById("broadcast-message");
    const message = messageEl ? messageEl.value.trim() : "";
    if (!message) {
      toast("Please enter a message", { type: "error" });
      messageEl?.focus();
      return;
    }
    const targets = TARGET_MAP[selectedTarget] || TARGET_MAP.all;
    sendBtn.disabled = true;
    try {
      const payload = await apiFetchFull("/admin/notifications/broadcast", {
        method: "POST",
        body: { type: "admin_announcement", targets, message },
      });
      const sent = payload && payload.data && payload.data.sent;
      toast(`Announcement sent to ${sent} ${sent === 1 ? "user" : "users"}`, { type: "success" });
      if (messageEl) messageEl.value = "";
      messageEl?.dispatchEvent(new Event("input"));
      await refreshBroadcasts();
    } catch (err) {
      toast(err.message || "Failed to send broadcast", { type: "error" });
    } finally {
      sendBtn.disabled = false;
    }
  }
}

function init() {
  const user = requireAuth(["admin"]);
  if (!user) return;
  initSelect(document, {
    "broadcast-target": (value) => {
      selectedTarget = value;
    },
  });
  initCounter();
  initForm();
  refreshBroadcasts();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", init);
} else {
  init();
}
