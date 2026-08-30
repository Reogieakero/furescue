import { createIcons, icons } from "lucide";
import { requireAuth, apiFetch, redirectToLogin } from "../../js/lib/api.js";
import { bootstrapPageAuth } from "../../js/lib/page-auth.js";
import { initResidentShell } from "../../js/components/resident-shell.js";
import { toast } from "../../js/components/ui/toast.js";

const el = (id) => document.getElementById(id);

function showFormError(message) {
  const slot = el("account-error");
  if (!slot) return;
  const span = slot.querySelector("span");
  if (span) span.textContent = message;
  slot.hidden = false;
  createIcons({ icons });
}

function clearFormError() {
  const slot = el("account-error");
  if (slot) slot.hidden = true;
}

async function onSubmit(event, user) {
  event.preventDefault();
  const form = event.currentTarget;
  const btn = el("account-save");
  const fullName = String(form.full_name.value || "").trim();
  clearFormError();
  if (!fullName) {
    showFormError("Full name is required.");
    return;
  }
  if (btn) btn.disabled = true;
  try {
    await apiFetch(`/users/${encodeURIComponent(user.id)}`, {
      method: "PATCH",
      body: {
        full_name: fullName,
        phone_number: String(form.phone_number.value || "").trim(),
        address: String(form.address.value || "").trim(),
      },
    });
    toast("Account updated.", { type: "success" });
  } catch (err) {
    if (err && err.status === 401) {
      redirectToLogin();
      return;
    }
    const message = err.message || "Could not update your account.";
    showFormError(message);
    toast(message, { type: "error" });
  } finally {
    if (btn) btn.disabled = false;
  }
}

function boot() {
  bootstrapPageAuth();
  initResidentShell();
  const user = requireAuth(["resident", "rescuer", "admin"]);
  if (!user) return;
  createIcons({ icons });
  el("account-form")?.addEventListener("submit", (event) => onSubmit(event, user));
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
