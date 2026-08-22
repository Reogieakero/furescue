
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

const NAV_TARGETS = {
  dashboard: "/admin/index.php",
  reports: "reports.html",
  cases: "cases.html",
  rescuers: "rescuers.html",
  animals: "animals.html",
  "health records": "health-records.html",
};

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

  sidebar.querySelectorAll(".sidebar-link[data-nav]").forEach((link) => {
    link.addEventListener("click", (e) => {
      e.preventDefault();
      const target = NAV_TARGETS[link.dataset.nav];
      if (target) window.location.href = target;
    });
  });
}
