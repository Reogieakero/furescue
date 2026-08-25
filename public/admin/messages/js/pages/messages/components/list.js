import { createIcons, icons } from "lucide";
import { timeAgo } from "/js/lib/format.js";
import { state, threadKey } from "../state.js";
import { esc, initialOf } from "../util.js";

const EMPTY_COPY =
  "Conversations appear when someone messages this admin, or after you start a conversation.";

export function ThreadList() {
  if (state.loadError) {
    return `
      <div class="amsg-empty">
        <i data-lucide="wifi-off"></i>
        <p class="amsg-empty-title">Could not load conversations</p>
        <p class="amsg-empty-text">${esc(state.loadError)}</p>
      </div>`;
  }

  if (!state.threads.length) {
    return `
      <div class="amsg-empty">
        <i data-lucide="inbox"></i>
        <p class="amsg-empty-title">No conversations yet</p>
        <p class="amsg-empty-text">${esc(EMPTY_COPY)}</p>
      </div>`;
  }

  return state.threads
    .map((t) => {
      const key = threadKey(t);
      const unread = Number(t.unread_count) > 0;
      return `
    <button type="button" class="amsg-thread-item${key === state.currentKey ? " is-active" : ""}" data-key="${esc(key)}">
      <span class="amsg-avatar">${esc(initialOf(t.other_user_name))}</span>
      <span class="amsg-item-main">
        <span class="amsg-item-name">${esc(t.other_user_name || "Unknown user")}</span>
        <span class="amsg-item-preview">${esc(String(t.last_message || "").replace(/\s+/g, " "))}</span>
      </span>
      <span class="amsg-item-side">
        <span class="amsg-item-time">${esc(timeAgo(t.last_sent_at))}</span>
        ${unread ? `<span class="amsg-item-unread">${Number(t.unread_count)}</span>` : ""}
      </span>
    </button>`;
    })
    .join("");
}

export function renderList() {
  const wrap = document.getElementById("amsg-threads");
  if (!wrap) return;
  wrap.innerHTML = ThreadList();
  createIcons({ icons });
}
