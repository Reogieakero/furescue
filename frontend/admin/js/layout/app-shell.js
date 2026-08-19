// App shell — composes sidebar + topbar + main and wires off-canvas behaviour.

import { Sidebar } from "./sidebar.js";
import { Topbar } from "./topbar.js";

export function AppShell({ user, badges = {}, notifications = 3, children = "" } = {}) {
  return `
  <div class="admin-shell">
    ${Sidebar({ user, badges, notifications })}
    <div id="overlay" class="admin-overlay"></div>
    <div class="admin-body">
      ${Topbar({ user })}
      <main class="admin-main">
        ${children}
      </main>
    </div>
  </div>`;
}

export function initShell() {
  const sidebar = document.getElementById("sidebar");
  const overlay = document.getElementById("overlay");
  const menuToggle = document.getElementById("menu-toggle");
  if (!sidebar || !overlay) return;

  const open = () => {
    sidebar.classList.add("is-open");
    overlay.classList.add("is-visible");
  };
  const close = () => {
    sidebar.classList.remove("is-open");
    overlay.classList.remove("is-visible");
  };

  if (menuToggle) menuToggle.addEventListener("click", open);
  if (overlay) overlay.addEventListener("click", close);
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") close();
  });
}