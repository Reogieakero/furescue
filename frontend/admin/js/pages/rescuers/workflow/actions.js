import { createIcons, icons } from "lucide";
import { toast } from "../../../../../js/components/ui/toast.js";
import { confirmDialog, detailsDialog } from "../../../../../js/components/ui/dialog.js";
import * as api from "../../../lib/admin-data.js";
import { state, loadRescuers } from "../state.js";
import { rerenderAll } from "../components.js";
import { shortId, titleCase } from "../../dashboard/helpers.js";

async function refresh() {
  await loadRescuers();
  rerenderAll();
  createIcons({ icons });
}

async function runApprove(id) {
  const u = state.pending.find((x) => x.id === id);
  const ok = await confirmDialog({
    title: "Approve rescuer",
    message: `Approve ${u ? u.full_name : shortId(id)} as a rescuer?`,
    confirmText: "Approve",
    run: () => api.approveRescuer(id),
  });
  if (!ok) return;
  toast(`${shortId(id)} approved as rescuer.`, { type: "success" });
  await refresh();
}

async function runReject(id) {
  const u = state.pending.find((x) => x.id === id);
  const ok = await confirmDialog({
    title: "Reject rescuer",
    message: `Reject ${u ? u.full_name : shortId(id)}'s application?`,
    danger: true,
    withReason: true,
    reasonRequired: true,
    reasonLabel: "Rejection reason",
    confirmText: "Reject",
    run: ({ reason }) => api.rejectRescuer(id, reason),
  });
  if (!ok) return;
  toast(`${shortId(id)} application rejected.`, { type: "success" });
  await refresh();
}

async function runSuspend(id) {
  const ok = await confirmDialog({
    title: "Suspend rescuer",
    message: `Suspend ${shortId(id)}? They will lose rescue access until reactivated.`,
    danger: true,
    withReason: true,
    reasonRequired: true,
    reasonLabel: "Suspension reason",
    confirmText: "Suspend",
    run: () => api.setUserStatus(id, "suspended"),
  });
  if (!ok) return;
  toast(`${shortId(id)} suspended.`, { type: "success" });
  await refresh();
}

async function runActivate(id) {
  const ok = await confirmDialog({
    title: "Activate rescuer",
    message: `Reactivate ${shortId(id)}?`,
    confirmText: "Activate",
    run: () => api.setUserStatus(id, "active"),
  });
  if (!ok) return;
  toast(`${shortId(id)} activated.`, { type: "success" });
  await refresh();
}

async function openRescuer(id) {
  const base = [...state.rescuers, ...state.pending].find((x) => x.id === id) || {};
  let detail = {};
  try {
    detail = await api.fetchUser(id);
  } catch {
    detail = {};
  }
  const u = detail.user || base;
  await detailsDialog({
    title: "Rescuer details",
    info: [
      { label: "ID", value: shortId(u.id) },
      { label: "Name", value: u.full_name || "—" },
      { label: "Email", value: u.email || "—" },
      { label: "Phone", value: u.phone_number || "—" },
      { label: "Status", value: titleCase(u.account_status) },
      { label: "Duty", value: titleCase(u.duty_status) },
      { label: "Address", value: u.address || "—" },
      {
        label: "Joined",
        value: u.created_at ? new Date(u.created_at).toLocaleDateString() : "—",
      },
    ],
  });
}

export { runApprove, runReject, runSuspend, runActivate, openRescuer };
