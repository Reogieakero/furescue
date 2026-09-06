import { KpiCard } from "/shared/components/kpi-card/kpi-card.js";
import { state } from "../state.js";

export function userCounts() {
  const byRole = (role) => state.users.filter((row) => row.role === role).length;
  const byStatus = (status) => state.users.filter((row) => row.account_status === status).length;
  return {
    all: state.users.length,
    admin: byRole("admin"),
    rescuer: byRole("rescuer"),
    resident: byRole("resident"),
    pending: byStatus("pending"),
    suspended: byStatus("suspended"),
    active: byStatus("active"),
  };
}

export function buildKpis() {
  const c = userCounts();
  return [
    { icon: "users", value: c.all, label: "Total accounts", tone: "jungle" },
    { icon: "shield", value: c.admin, label: "Admins", tone: "coral" },
    { icon: "siren", value: c.rescuer, label: "Rescuers", tone: "sky" },
    { icon: "heart-handshake", value: c.resident, label: "Residents", tone: "ink" },
  ];
}

export function KpiTile(k) {
  return KpiCard(k);
}
