
import { getNavBadges } from "../../../js/lib/swr.js";

const NAV_GROUPS = [
  {
    label: "Overview",
    items: [{ icon: "layout-dashboard", label: "Dashboard", active: true }],
  },
  {
    label: "Rescue Management",
    items: [
      { icon: "map-pin", label: "Reports", badgeKey: "reports", badgeCls: "stamp--accent" },
      { icon: "clipboard-list", label: "Cases", badgeKey: "cases" },
      { icon: "siren", label: "Rescuers", badgeKey: "rescuers" },
    ],
  },
  {
    label: "Animal Management",
    items: [
      { icon: "paw-print", label: "Animals" },
      { icon: "heart-pulse", label: "Health Records", badgeKey: "health", badgeCls: "stamp--muted" },
    ],
  },
  {
    label: "Adoption",
    items: [
      { icon: "home", label: "Listings" },
      { icon: "file-check", label: "Applications", badgeKey: "applications", badgeCls: "stamp--accent" },
    ],
  },
  {
    label: "Content",
    items: [{ icon: "book-open", label: "E-Learning" }],
  },
  {
    label: "Communication",
    items: [
      { icon: "message-square", label: "Messages" },
      { icon: "bell", label: "Notifications", badgeKey: "notifications", badgeCls: "stamp--coral" },
    ],
  },
];

function NavItem(item, map, activeNav) {
  const override = map && item.badgeKey ? map[item.badgeKey] : undefined;
  const value = override !== undefined ? override : item.badge;
  const badge = value
    ? `<span class="stamp stamp--sm sidebar-badge ${item.badgeCls}">${value}</span>`
    : "";
  const isActive = (activeNav || "dashboard") === item.label.toLowerCase();
  const tone = isActive ? " sidebar-link--active" : "";
  return `
    <a href="#" data-nav="${item.label.toLowerCase()}" class="sidebar-link${tone}">
      <i data-lucide="${item.icon}"></i> <span>${item.label}</span>
      ${badge}
    </a>`;
}

export function Sidebar({ user, badges = {}, notifications = 3, activeNav } = {}) {
  const map = { notifications, ...getNavBadges(), ...badges };
  const groups = NAV_GROUPS.map(
    (g) => `
    <div class="sidebar-group">
      <div class="sidebar-label">${g.label}</div>
      <div class="sidebar-links">${g.items.map((i) => NavItem(i, map, activeNav)).join("")}</div>
    </div>`
  ).join("");

  return `
  <aside id="sidebar" class="sidebar">
    <div class="sidebar-head">
      <div class="sidebar-logo"><i data-lucide="paw-print"></i></div>
      <div>
        <div class="sidebar-brand">FurEscue</div>
        <div class="sidebar-tag">Admin Console</div>
      </div>
    </div>

    <nav class="sidebar-nav">
      ${groups}
    </nav>
  </aside>`;
}
