import { createIcons, icons } from "lucide";
import { toast } from "/shared/components/toast/toast.js";
import { confirmDialog, detailsDialog } from "/shared/components/dialog/dialog.js";
import { deleteUser, setUserStatus } from "/assets/js/admin/admin-data.js";
import { state, loadUsers, currentUserId } from "../state.js";
import { rerenderAll } from "../components.js";
import { openUserForm } from "../components/form.js";
import { roleLabel, statusLabel } from "../components/util.js";
import { timeAgo } from "/admin/js/helpers.js";

function findUser(id) {
  return state.users.find((row) => row.id === id) || null;
}

function labelFor(id) {
  const row = findUser(id);
  return (row && row.full_name && String(row.full_name).trim()) || "this user";
}

async function refresh() {
  await loadUsers();
  rerenderAll();
  createIcons({ icons });
}

export async function runCreate() {
  const ok = await openUserForm({ mode: "create" });
  if (!ok) return;
  toast("User created.", { type: "success" });
  await refresh();
}

export async function runEdit(id) {
  const row = findUser(id);
  if (!row) return;
  const ok = await openUserForm({ mode: "edit", user: row });
  if (!ok) return;
  toast("User saved.", { type: "success" });
  await refresh();
}

export async function runView(id) {
  const row = findUser(id);
  if (!row) return;
  await detailsDialog({
    title: row.full_name || "User",
    info: [
      { label: "Email", value: row.email || "—" },
      { label: "Phone", value: row.phone_number || "—" },
      { label: "Address", value: row.address || "—" },
      { label: "Role", value: roleLabel(row.role) },
      { label: "Status", value: statusLabel(row.account_status) },
      { label: "Signed in with", value: row.auth_provider === "google" ? "Google" : "Email" },
      { label: "Joined", value: timeAgo(row.created_at) },
    ],
  });
}

export async function runSuspend(id) {
  if (id === currentUserId()) return;
  const row = findUser(id);
  const ok = await confirmDialog({
    title: "Suspend this user?",
    message: `Suspend ${labelFor(id)}? They will lose access until activated.`,
    info: [
      { label: "Email", value: (row && row.email) || "—" },
      { label: "Role", value: roleLabel(row && row.role) },
    ],
    confirmText: "Suspend",
    danger: true,
    run: () => setUserStatus(id, "suspended"),
  });
  if (!ok) return;
  toast(`${labelFor(id)} is suspended.`, { type: "success" });
  await refresh();
}

export async function runActivate(id) {
  if (id === currentUserId()) return;
  const row = findUser(id);
  const ok = await confirmDialog({
    title: "Activate this user?",
    message: `Activate ${labelFor(id)}? They will be able to sign in again.`,
    info: [
      { label: "Email", value: (row && row.email) || "—" },
      { label: "Role", value: roleLabel(row && row.role) },
    ],
    confirmText: "Activate",
    run: () => setUserStatus(id, "active"),
  });
  if (!ok) return;
  toast(`${labelFor(id)} is active.`, { type: "success" });
  await refresh();
}

export async function runDelete(id) {
  if (id === currentUserId()) return;
  const row = findUser(id);
  const ok = await confirmDialog({
    title: "Delete this user?",
    message: `Delete ${labelFor(id)}? Accounts with reports, cases, or messages cannot be removed — suspend them instead.`,
    info: [
      { label: "Email", value: (row && row.email) || "—" },
      { label: "Role", value: roleLabel(row && row.role) },
    ],
    confirmText: "Delete user",
    danger: true,
    run: () => deleteUser(id),
  });
  if (!ok) return;
  toast(`${labelFor(id)} was deleted.`, { type: "success" });
  await refresh();
}
