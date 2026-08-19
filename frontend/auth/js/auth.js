import { createIcons, icons } from "lucide";
import { cn } from "../../js/lib/utils.js";
import { login, redirectForRole } from "../../js/lib/api.js";
import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "../../js/components/ui/card.js";
import { Button, buttonVariants } from "../../js/components/ui/button.js";
import { Input } from "../../js/components/ui/input.js";
import { Label } from "../../js/components/ui/label.js";
import { Checkbox } from "../../js/components/ui/checkbox.js";
import { Separator } from "../../js/components/ui/separator.js";
import { toast } from "../../js/components/ui/toast.js";
import { showLoader, hideLoader } from "../../js/components/ui/loader.js";

// Inline Google "G" so we don't depend on a brand icon pack
function GoogleIcon() {
  return `<svg class="google-icon" viewBox="0 0 48 48" width="18" height="18" aria-hidden="true">
    <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.4 29.3 35 24 35c-6.1 0-11-4.9-11-11s4.9-11 11-11c2.8 0 5.4 1.1 7.3 2.8l5.7-5.7C33.5 6.5 29 5 24 5 13.5 5 5 13.5 5 24s8.5 19 19 19 19-8.5 19-19c0-1.3-.1-2.3-.4-3.5z"/>
    <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 16 19 13 24 13c2.8 0 5.4 1.1 7.3 2.8l5.7-5.7C33.5 6.5 29 5 24 5 16.3 5 9.7 9.3 6.3 14.7z"/>
    <path fill="#4CAF50" d="M24 43c5 0 9.5-1.9 12.9-5.1l-6-5.2C29.2 34.3 26.7 35 24 35c-5.3 0-9.7-3.6-11.3-8.4l-6.5 5C9.6 39 16.2 43 24 43z"/>
    <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.1-4 5.6l6 5.2C41.4 35.8 44 30.4 44 24c0-1.3-.1-2.3-.4-3.5z"/>
  </svg>`;
}

function BrandPanel() {
  return `
  <div class="auth-panel">
    <div class="auth-panel-inner">
      <a href="../landing/index.html" class="brand auth-brand">
        <span class="logo-mark"><i data-lucide="paw-print"></i></span>
        <span>Fur<span class="text-primary">escue</span></span>
      </a>
      <h1 class="auth-panel-title">Every stray deserves a safe home.</h1>
      <p class="auth-panel-sub">
        Sign in to coordinate rescues, locate cases on the map, and help Puspin &amp; Aspin find permanent families.
      </p>
      <ul class="auth-points">
        <li><i data-lucide="map-pin"></i> Live map of reported cases</li>
        <li><i data-lucide="bell-ring"></i> Urgent-first prioritization</li>
        <li><i data-lucide="heart"></i> Browse animals for adoption</li>
      </ul>
    </div>
  </div>`;
}

function LoginCard() {
  const inner = `
    ${CardHeader({
      className: "auth-card-head",
      children: `
        ${CardTitle({ children: "Welcome back", className: "auth-card-title" })}
        ${CardDescription({ children: "Sign in to your FurEscue account to manage rescues." })}
      `,
    })}
    ${CardContent({
      className: "auth-card-body",
      children: `
        <form id="login-form" class="auth-form" novalidate>
          <div class="field">
            ${Label({ htmlFor: "email", children: "Email" })}
            ${Input({ id: "email", name: "email", type: "email", placeholder: "you@example.com" })}
          </div>
          <div class="field">
            ${Label({ htmlFor: "password", children: "Password" })}
            <div class="input-wrap">
              ${Input({ id: "password", name: "password", type: "password", placeholder: "••••••••" })}
              <button type="button" id="toggle-pw" class="input-affix" aria-label="Show password">
                <i data-lucide="eye"></i>
              </button>
            </div>
          </div>
          <div class="auth-row">
            <label class="auth-remember">
              ${Checkbox({ id: "remember", name: "remember", checked: true })}
              <span>Remember me</span>
            </label>
<a href="#forgot" class="auth-link">Forgot password?</a>
          </div>
          ${Button({
            text: "Sign in",
            variant: "default",
            size: "lg",
            type: "submit",
            href: null,
            icon: "lock",
            className: "auth-submit",
          })}
        </form>
      `,
    })}
    ${CardFooter({
      className: "auth-card-foot",
      children: `
        ${Separator({ label: "or" })}
        <a href="#google" class="${cn(buttonVariants({ variant: "outline", size: "lg" }), "auth-submit")}">
          ${GoogleIcon()}<span>Continue with Google</span>
        </a>
        <p class="auth-alt">Don't have an account? <a href="signup.html" class="auth-link">Sign up</a></p>
      `,
    })}
  `;

  return Card({ className: "auth-card", children: inner });
}

export function LoginPage() {
  return `
  <main class="auth-page">
    <div class="auth-split">
      ${BrandPanel()}
      <div class="auth-form-side">
        ${LoginCard()}
      </div>
    </div>
  </main>`;
}

function initLogin() {
  const form = document.getElementById("login-form");
  const toggle = document.getElementById("toggle-pw");
  const pw = document.getElementById("password");

  if (toggle && pw) {
    toggle.addEventListener("click", () => {
      const show = pw.type === "password";
      pw.type = show ? "text" : "password";
      toggle.innerHTML = `<i data-lucide="${show ? "eye-off" : "eye"}"></i>`;
      createIcons({ icons });
    });
  }

  if (form) {
    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const email = form.email.value.trim();
      const password = form.password.value;
      if (!email || !password) {
        toast("Please enter your email and password.", { type: "error" });
        return;
      }
      showLoader("Signing in…");
      try {
        const user = await login(email, password);
        hideLoader();
        toast("Welcome back, " + (user.full_name || "there") + "! Redirecting…", { type: "success" });
        setTimeout(() => redirectForRole(user), 900);
      } catch (err) {
        hideLoader();
        toast(err.message, { type: "error" });
      }
    });
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const app = document.getElementById("app");
  if (!app) return;
  app.innerHTML = LoginPage();
  createIcons({ icons });
  initLogin();
});


