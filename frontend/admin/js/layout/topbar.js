// Topbar — sticky command bar (search, date/location, notifications, user).
import { DropdownMenu } from "../../../js/components/ui/dropdown-menu.js";

export function Topbar({ user } = {}) {
  const profileMenu = DropdownMenu({
    id: "profile-menu",
    align: "right",
    trigger: `
    <button type="button" data-dropdown-trigger class="topbar-user" aria-haspopup="menu" aria-expanded="false" aria-label="Admin menu">
      <img src="https://i.pravatar.cc/64?img=33" alt="Admin avatar">
    </button>`,
    items: [
      { type: "label", text: "Insights" },
      { type: "item", icon: "bar-chart-3", label: "Analytics", href: "#" },
      { type: "item", icon: "file-down", label: "Reports & Exports", href: "#" },
      { type: "separator" },
      { type: "label", text: "System" },
      { type: "item", icon: "users", label: "Users", href: "#" },
      { type: "item", icon: "settings", label: "Settings", href: "#" },
      { type: "separator" },
      { type: "item", icon: "log-out", label: "Log Out", href: "#", danger: true },
    ],
  });

  return `
  <header class="topbar">
    <button id="menu-toggle" class="topbar-menu" aria-label="Open menu">
      <i data-lucide="menu"></i>
    </button>

    <div class="topbar-search">
      <i data-lucide="search"></i>
      <input type="text" placeholder="Search case #, name, barangay…">
    </div>

    <div class="topbar-actions">
      <span class="topbar-meta"><i data-lucide="calendar"></i> <span id="admin-date"></span> &middot; City of Mati</span>
      <button class="topbar-bell" aria-label="Notifications"><i data-lucide="bell"></i></button>
      <span class="topbar-divider"></span>
      ${profileMenu}
    </div>
  </header>`;
}