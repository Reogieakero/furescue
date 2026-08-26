import { createIcons, icons } from "lucide";
import { toast } from "../../js/components/ui/toast.js";
import { showLoader, hideLoader } from "../../js/components/ui/loader.js";
import { acceptCase, declineCase } from "./api.js";

function confirmDecline() {
  return new Promise((resolve) => {
    const overlay = document.createElement("div");
    overlay.className = "rmodal-overlay";
    overlay.innerHTML = `
      <div class="rmodal" role="dialog" aria-modal="true" aria-labelledby="decline-title">
        <div class="rmodal-head">
          <i data-lucide="circle-x" class="text-destructive"></i>
          <h2 class="rmodal-title" id="decline-title">Decline this case?</h2>
          <button type="button" class="rmodal-x" aria-label="Close"><i data-lucide="x"></i></button>
        </div>
        <div class="rmodal-body">
          <p class="m-0 text-sm text-muted-foreground">The case will leave your queue so another rescuer can take it.</p>
        </div>
        <div class="rmodal-foot">
          <button type="button" class="rbtn rbtn--ghost" data-act="cancel">Keep case</button>
          <button type="button" class="rbtn rbtn--ghost rbtn--danger-ghost" data-act="ok">
            <i data-lucide="x"></i><span>Decline</span>
          </button>
        </div>
      </div>`;

    const host = document.querySelector(".resident-shell") || document.body;
    host.appendChild(overlay);
    createIcons({ icons });

    const finish = (value) => {
      document.removeEventListener("keydown", onEsc);
      overlay.remove();
      resolve(value);
    };
    const onEsc = (e) => {
      if (e.key === "Escape") finish(false);
    };
    document.addEventListener("keydown", onEsc);
    overlay.addEventListener("click", (e) => {
      if (e.target === overlay) finish(false);
    });
    overlay.querySelector('[data-act="cancel"]').addEventListener("click", () => finish(false));
    overlay.querySelector(".rmodal-x").addEventListener("click", () => finish(false));
    overlay.querySelector('[data-act="ok"]').addEventListener("click", () => finish(true));
  });
}

export async function runAccept(id) {
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
  const ok = await confirmDecline();
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
        if (onAccepted) await onAccepted(id, data);
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
