
import { Sidebar } from "./sidebar.js";
import { Topbar } from "./topbar.js";

export function AppShell({ user, badges = {}, notifications = 3, activeNav, children = "" } = {}) {
  return `
  <div class="admin-shell">
    ${Sidebar({ user, badges, notifications, activeNav })}
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

  const searchInput = document.querySelector(".topbar-search input");
  if (searchInput) {
    searchInput.addEventListener("keydown", (e) => {
      if (e.key !== "Enter") return;
      const q = searchInput.value.trim();
      if (!q) return;
      window.location.href = `/admin/cases/?q=${encodeURIComponent(q)}`;
    });
  }

  const bell = document.querySelector(".topbar-bell");
  if (bell) {
    bell.addEventListener("click", () => {
      window.location.href = "/admin/notifications/";
    });
  }
}
