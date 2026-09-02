import { AppShell } from "/assets/js/admin/app-shell.js";
import { Button } from "/assets/js/components/ui/button.js";
import { createIcons, icons } from "lucide";
import { ThreadList, renderList } from "./list.js";
import { ThreadEmpty, ThreadHead, renderPane } from "./pane.js";
import { Composer } from "./composer.js";

function PageHead() {
  return `
  <div class="page-head">
    <div>
      <span class="stamp stamp--coral">Communication</span>
      <h1 class="page-title">Messages</h1>
      <p class="page-sub">Staff inbox for reports, cases, and adoption applications.</p>
    </div>
    <div class="page-head-actions">
      ${Button({ text: "Start conversation", variant: "default", icon: "plus", attrs: 'data-action="compose" id="amsg-compose"' })}
    </div>
  </div>`;
}

function InboxPanel() {
  return `
  <div class="panel amsg-panel">
    <div class="amsg-shell" id="amsg-shell">
      <aside class="amsg-list" aria-label="Conversations">
        <div class="amsg-list-head">
          <i data-lucide="message-square"></i>
          <h2 class="amsg-list-title">Conversations</h2>
        </div>
        <div class="amsg-list-items" id="amsg-threads">${ThreadList()}</div>
      </aside>
      <section class="amsg-thread" aria-live="polite">
        ${ThreadEmpty()}
        ${ThreadHead()}
        <div class="amsg-thread-scroll is-hidden" id="amsg-scroll"></div>
        ${Composer()}
      </section>
    </div>
  </div>`;
}

export function MessagesPage(user) {
  return AppShell({
    user,
    notifications: 0,
    badges: {},
    activeNav: "messages",
    children: [PageHead(), InboxPanel()].join(""),
  });
}

export function rerenderAll() {
  renderList();
  renderPane([], { forceScroll: false });
  createIcons({ icons });
}
