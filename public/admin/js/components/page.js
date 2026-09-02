import { AppShell } from "/assets/js/admin/app-shell.js";
import { Button } from "/assets/js/components/ui/button.js";
import { SkeletonDashboard } from "/assets/js/components/ui/skeleton.js";
import { state } from "../state.js";
import { KpiGrid } from "./kpis.js";
import { DashboardSections } from "./cards.js";

function Greeting(user) {
  const name = (user && user.full_name) || "Admin";
  return `
  <div class="greeting">
    <div>
      <p class="dash-kicker">Command Center</p>
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
    children: `<div class="dash">${[Greeting(user), KpiGrid(), DashboardSections()].join("")}</div>`,
  });
}
