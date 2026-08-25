import { createIcons, icons } from "lucide";
import { apiFetchFull, homePathForRole, setSession } from "../../js/lib/api.js";

function initPasswordToggle() {
  const toggle = document.getElementById("toggle-pw");
  const pw = document.getElementById("password");
  if (!toggle || !pw) return;

  toggle.addEventListener("click", () => {
    const show = pw.type === "password";
    pw.type = show ? "text" : "password";
    toggle.innerHTML = `<i data-lucide="${show ? "eye-off" : "eye"}"></i>`;
    createIcons({ icons });
  });
}

function initInlineValidation() {
  const form = document.getElementById("login-form");
  if (!form) return;

  form.addEventListener("submit", (e) => {
    const email = form.email.value.trim();
    const password = form.password.value;
    if (!email || !password) {
      e.preventDefault();
      showToast("Please enter your email and password.");
    }
  });
}

function destinationFor(user) {
  return homePathForRole(user);
}

let gsiPromise = null;

function loadGsiScript() {
  if (window.google && window.google.accounts && window.google.accounts.id) {
    return Promise.resolve();
  }
  if (!gsiPromise) {
    gsiPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = "https://accounts.google.com/gsi/client";
      script.async = true;
      script.defer = true;
      script.onload = resolve;
      script.onerror = () => {
        gsiPromise = null;
        reject(new Error("Could not load Google Sign-In."));
      };
      document.head.appendChild(script);
    });
  }
  return gsiPromise;
}

async function onGoogleCredential(response) {
  try {
    const payload = await apiFetchFull("/auth/google", {
      method: "POST",
      auth: false,
      body: { id_token: response.credential },
    });
    setSession(payload.data);
    window.location.href = destinationFor(payload.data && payload.data.user);
  } catch (err) {
    showToast(err.message || "Google sign-in failed.");
  }
}

async function handleGoogleClick(e) {
  e.preventDefault();
  const clientId = window.FURESCUE_GOOGLE_CLIENT_ID;
  if (!clientId) {
    showToast("Google sign-in is not configured.");
    return;
  }
  try {
    await loadGsiScript();
    window.google.accounts.id.initialize({
      client_id: clientId,
      callback: onGoogleCredential,
    });
    window.google.accounts.id.prompt();
  } catch (err) {
    showToast(err.message || "Google sign-in failed.");
  }
}

function initGoogleSignIn() {
  document.querySelectorAll("[data-google-signin]").forEach((btn) => {
    btn.addEventListener("click", handleGoogleClick);
  });
}

function showFormError(message) {
  const el = document.getElementById("form-error");
  if (!el) {
    showToast(message);
    return;
  }
  el.textContent = message;
  el.classList.remove("hidden");
}

function clearFormError() {
  const el = document.getElementById("form-error");
  if (el) el.classList.add("hidden");
}

function initRescuerNote() {
  const role = document.getElementById("role");
  const note = document.getElementById("rescuer-note");
  if (!role || !note) return;
  const sync = () => note.classList.toggle("hidden", role.value !== "rescuer");
  role.addEventListener("change", sync);
  sync();
}

function initSignupForm() {
  const form = document.getElementById("signup-form");
  if (!form) return;

  initRescuerNote();

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fullName = form.full_name.value.trim();
    const email = form.email.value.trim();
    const password = form.password.value;
    const phoneNumber = form.phone_number.value.trim();
    const address = form.address.value.trim();
    const role = form.role.value;

    clearFormError();
    if (!fullName) return showFormError("Please enter your full name.");
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      return showFormError("Please enter a valid email address.");
    }
    if (password.length < 8) {
      return showFormError("Password must be at least 8 characters.");
    }

    const btn = document.getElementById("signup-submit");
    if (btn) btn.disabled = true;
    try {
      const payload = await apiFetchFull("/auth/register", {
        method: "POST",
        auth: false,
        body: {
          full_name: fullName,
          email,
          password,
          role,
          ...(phoneNumber ? { phone_number: phoneNumber } : {}),
          ...(address ? { address } : {}),
        },
      });
      setSession(payload.data);
      window.location.href = destinationFor(payload.data && payload.data.user);
    } catch (err) {
      if (btn) btn.disabled = false;
      showFormError(
        err.code === "EMAIL_TAKEN"
          ? "That email is already registered. Try signing in instead."
          : err.message || "Sign up failed. Please try again."
      );
    }
  });
}

function showToast(message) {
  let viewport = document.querySelector(".toast-viewport");
  if (!viewport) {
    viewport = document.createElement("div");
    viewport.className = "toast-viewport";
    viewport.setAttribute("aria-live", "polite");
    document.body.appendChild(viewport);
  }
  const el = document.createElement("div");
  el.className = "toast toast--error";
  el.setAttribute("role", "status");
  el.innerHTML = `
    <i data-lucide="alert-circle" class="toast-icon"></i>
    <p class="toast-message"></p>
    <button class="toast-close" aria-label="Dismiss"><i data-lucide="x"></i></button>
  `;
  el.querySelector(".toast-message").textContent = message;
  viewport.appendChild(el);
  createIcons({ icons });
  requestAnimationFrame(() => el.classList.add("is-visible"));
  const dismiss = () => {
    el.classList.remove("is-visible");
    setTimeout(() => el.remove(), 200);
  };
  el.querySelector(".toast-close").addEventListener("click", dismiss);
  setTimeout(dismiss, 3500);
}

function initToastDismiss() {
  document.querySelectorAll(".toast .toast-close").forEach((btn) => {
    btn.addEventListener("click", () => {
      const el = btn.closest(".toast");
      if (!el) return;
      el.classList.remove("is-visible");
      setTimeout(() => el.remove(), 200);
    });
  });
}

function boot() {
  createIcons({ icons });
  initPasswordToggle();
  initInlineValidation();
  initSignupForm();
  initGoogleSignIn();
  initToastDismiss();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", boot);
} else {
  boot();
}
