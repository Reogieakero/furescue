import { createIcons, icons } from "lucide";
import { toast } from "/assets/js/components/ui/toast.js";
import { confirmDialog } from "/assets/js/components/ui/dialog.js";
import { shortId } from "/admin/js/helpers.js";
import { approveListing, rejectListing } from "../api.js";
import { state, loadListings } from "../state.js";
import { rerenderAll } from "../components.js";

function listing(id) {
  return state.listings.find((row) => row.id === id) || null;
}

function labelFor(id) {
  const row = listing(id);
  const name = row && row.animal_name && String(row.animal_name).trim();
  return name || shortId(id);
}

async function refresh() {
  await loadListings();
  rerenderAll();
  createIcons({ icons });
}

export async function runApprove(id) {
  const row = listing(id);
  const ok = await confirmDialog({
    title: "Approve listing",
    message: `Approve ${labelFor(id)} for adoption? This sets the animal as available.`,
    info: [
      { label: "Animal", value: labelFor(id) },
      { label: "Poster", value: (row && row.poster_name) || "—" },
    ],
    confirmText: "Approve",
    cancelText: "Cancel",
    run: () => approveListing(id),
  });
  if (!ok) return;
  if (row) row.status = "approved";
  toast(`${labelFor(id)} is now live for adoption.`, { type: "success" });
  await refresh();
}

export async function runReject(id) {
  const row = listing(id);
  const ok = await confirmDialog({
    title: "Reject listing",
    message: `Reject the adoption listing for ${labelFor(id)}? Review notes are required.`,
    info: [
      { label: "Animal", value: labelFor(id) },
      { label: "Poster", value: (row && row.poster_name) || "—" },
    ],
    confirmText: "Reject",
    cancelText: "Cancel",
    danger: true,
    withReason: true,
    reasonRequired: true,
    reasonLabel: "Review notes",
    run: ({ reason }) => rejectListing(id, reason),
  });
  if (!ok) return;
  if (row) row.status = "rejected";
  toast(`${labelFor(id)} listing rejected.`, { type: "success" });
  await refresh();
}
