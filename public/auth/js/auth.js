import { createIcons, icons } from "lucide";

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

document.addEventListener("DOMContentLoaded", () => {
  createIcons({ icons });
  initPasswordToggle();
  initInlineValidation();
  initToastDismiss();
});
