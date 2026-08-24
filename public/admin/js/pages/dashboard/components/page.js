import { AppShell } from "../../../layout/app-shell.js";
import { Button } from "../../../../../js/components/ui/button.js";
import { SkeletonDashboard } from "../../../../../js/components/ui/skeleton.js";
import { state } from "../state.js";
import { KpiGrid } from "./kpis.js";
import { AttentionRow, DashboardSections } from "./cards.js";

function Greeting(user) {
  const name = (user && user.full_name) || "Admin";
  return `
  <div class="greeting">
    <div>
      <span class="stamp stamp--coral">Command Center</span>
      <h1 class="greeting-title">Good morning, ${name}</h1>
      <p class="greeting-sub" id="greeting-sub">${state.decisionCount} items need a decision today across reports, rescuers, health records, and adoptions.</p>
    </div>
    <div class="greeting-actions">
      ${Button({ text: "Export Report", variant: "outline", icon: "download", href: "/admin/analytics/" })}
      ${Button({ text: "New Announcement", variant: "default", icon: "megaphone", attrs: 'id="announce-btn"' })}
    </div>
  </div>`;
}

export function DashboardPage(user, { loading = false } = {}) {
  if (loading) {
    return AppShell({
      user,
      notifications: state.notifications.total,
      badges: {
        reports: state.reportsTotal,
        health: state.healthUpdates.total,
        applications: state.adoptionsPending.total,
      },
      children: SkeletonDashboard(),
    });
  }
  return AppShell({
    user,
    notifications: state.notifications.total,
    badges: {
      reports: state.reportsTotal,
      health: state.healthUpdates.total,
      applications: state.adoptionsPending.total,
    },
    children: [Greeting(user), KpiGrid(), AttentionRow(), DashboardSections()].join(""),
  });
}
