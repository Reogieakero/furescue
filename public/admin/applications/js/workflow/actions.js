import { createIcons, icons } from "lucide";
import { toast } from "/assets/js/components/ui/toast.js";
import { confirmDialog } from "/assets/js/components/ui/dialog.js";
import { closeDrawer } from "/assets/js/components/ui/drawer.js";
import { shortId } from "/admin/js/helpers.js";
import * as api from "../api.js";
import { state, reloadData, loadAdoptions } from "../state.js";
import { rerenderAll } from "../components.js";
import { applicantName, animalName } from "../components/util.js";

export function findAdoption(id) {
  return state.items.find((a) => a.id === id) || null;
}

function labels(id) {
  const a = findAdoption(id);
  return {
    applicant: (a && applicantName(a)) || shortId(a ? a.applicant_id : id),
    animal: (a && animalName(a)) || "animal",
  };
}

function info(id) {
  const a = findAdoption(id);
  if (!a) return [];
  return [
    { label: "Applicant", value: applicantName(a) || shortId(a.applicant_id) },
    { label: "Animal", value: animalName(a) || shortId(a.animal_id) },
    { label: "Submitted", value: a.created_at ? new Date(a.created_at).toLocaleString() : "—" },
  ];
}

async function refresh() {
  closeDrawer();
  try {
    await reloadData();
  } catch (err) {
    toast((err && err.message) || "Could not refresh applications.", { type: "error" });
  }
  rerenderAll();
  createIcons({ icons });
}

export async function runApprove(id) {
  const { applicant, animal } = labels(id);
  const ok = await confirmDialog({
    title: "Approve adoption",
    message: `Are you sure you want to approve this application for ${applicant}?`,
    info: info(id),
    confirmText: "Approve",
    cancelText: "Cancel",
    run: () => api.approveAdoption(id),
  });
  if (!ok) return;
  toast(`Adoption approved for ${applicant} · ${animal}.`, { type: "success" });
  await refresh();
}

export async function runDecline(id) {
  const { applicant, animal } = labels(id);
  const ok = await confirmDialog({
    title: "Decline adoption",
    message: `Are you sure you want to decline this application for ${applicant}?`,
    info: info(id),
    confirmText: "Decline",
    cancelText: "Cancel",
    danger: true,
    withReason: true,
    reasonLabel: "Decline reason",
    reasonRequired: true,
    run: ({ reason }) => api.rejectAdoption(id, reason),
  });
  if (!ok) return;
  toast(`Adoption declined for ${applicant} · ${animal}.`, { type: "success" });
  await refresh();
}

export async function runComplete(id) {
  const { applicant, animal } = labels(id);
  const ok = await confirmDialog({
    title: "Complete adoption",
    message: `Mark this adoption as complete for ${applicant} · ${animal}?`,
    info: info(id),
    confirmText: "Complete",
    cancelText: "Cancel",
    run: () => api.completeAdoption(id),
  });
  if (!ok) return;
  toast(`Adoption completed for ${applicant} · ${animal}.`, { type: "success" });
  await refresh();
}

export async function runRetry() {
  try {
    await loadAdoptions();
    rerenderAll();
    createIcons({ icons });
    toast("Applications updated.", { type: "success" });
  } catch (err) {
    rerenderAll();
    toast((err && err.message) || "Could not load applications.", { type: "error" });
  }
}
