// Sidebar — grouped nav rail (Command Center). Mobile off-canvas wired in app-shell.js.

const NAV_GROUPS = [
  {
    label: "Overview",
    items: [{ icon: "layout-dashboard", label: "Dashboard", active: true }],
  },
  {
    label: "Rescue Management",
    items: [
      { icon: "map-pin", label: "Reports", badgeKey: "reports", badge: "14", badgeCls: "stamp--accent" },
      { icon: "clipboard-list", label: "Cases" },
      { icon: "siren", label: "Rescuers" },
    ],
  },
  {
    label: "Animal Management",
    items: [
      { icon: "paw-print", label: "Animals" },
      { icon: "heart-pulse", label: "Health Records", badgeKey: "health", badge: "6", badgeCls: "stamp--muted" },
    ],
  },
  {
    label: "Adoption",
    items: [
      { icon: "home", label: "Listings" },
      { icon: "file-check", label: "Applications", badgeKey: "applications", badge: "9", badgeCls: "stamp--accent" },
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
      { icon: "bell", label: "Notifications", badgeKey: "notifications", badge: "3", badgeCls: "stamp--coral" },
    ],
  },
];

function NavItem(item, map) {
  const override = map && item.badgeKey ? map[item.badgeKey] : undefined;
  const value = override !== undefined ? override : item.badge;
  const badge = value
    ? `<span class="stamp stamp--sm sidebar-badge ${item.badgeCls}">${value}</span>`
    : "";
  const tone = item.active ? " sidebar-link--active" : "";
  return `
    <a href="#" class="sidebar-link${tone}">
      <i data-lucide="${item.icon}"></i> <span>${item.label}</span>
      ${badge}
    </a>`;
}

export function Sidebar({ user, badges = {}, notifications = 3 } = {}) {
  const map = { ...badges, notifications };
  const groups = NAV_GROUPS.map(
    (g) => `
    <div class="sidebar-group">
      <div class="sidebar-label">${g.label}</div>
      <div class="sidebar-links">${g.items.map((i) => NavItem(i, map)).join("")}</div>
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