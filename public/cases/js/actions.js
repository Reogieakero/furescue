import { toast } from "/shared/components/toast/toast.js";
import { showLoader, hideLoader } from "/shared/components/loader/loader.js";
import { confirmDialog } from "/shared/components/dialog/dialog.js";
import { acceptCase, declineCase } from "./api.js";

export async function runAccept(id) {
  const ok = await confirmDialog({
    title: "Accept this case?",
    message: "This will accept the rescue case and mark it in progress.",
    confirmText: "Accept case",
  });
  if (!ok) return null;
  showLoader("Accepting case…");
  try {
    const data = await acceptCase(id);
    toast("Case accepted. Rescue is now in progress.", { type: "success" });
    return data;
  } catch (err) {
    toast(err.message || "Could not accept this case.", { type: "error" });
    throw err;
  } finally {
    hideLoader();
  }
}

export async function runDecline(id) {
  const ok = await confirmDialog({
    title: "Decline this case?",
    message: "This will decline the rescue case so another rescuer can take it.",
    confirmText: "Decline case",
    danger: true,
  });
  if (!ok) return null;
  showLoader("Declining case…");
  try {
    const data = await declineCase(id);
    toast("Case declined.", { type: "success" });
    return data;
  } catch (err) {
    toast(err.message || "Could not decline this case.", { type: "error" });
    throw err;
  } finally {
    hideLoader();
  }
}

export function bindCaseActions(root, { onAccepted, onDeclined } = {}) {
  if (!root || root.dataset.caseActionsBound) return;
  root.dataset.caseActionsBound = "1";
  root.addEventListener("click", async (e) => {
    const btn = e.target.closest("[data-case-act]");
    if (!btn || !root.contains(btn)) return;
    const id = btn.getAttribute("data-id") || "";
    const act = btn.getAttribute("data-case-act");
    if (!id) return;
    btn.disabled = true;
    try {
      if (act === "accept") {
        const data = await runAccept(id);
        if (data !== null && onAccepted) await onAccepted(id, data);
      } else if (act === "decline") {
        const data = await runDecline(id);
        if (data !== null && onDeclined) await onDeclined(id, data);
      }
    } catch {
      /* toast already shown */
    } finally {
      btn.disabled = false;
    }
  });
}
