import { createIcons, icons } from "lucide";
import { clearSession, apiFetch } from "../lib/api.js";
import { bootstrapPageAuth } from "../lib/page-auth.js";
import { setNavBadge } from "../lib/swr.js";
import { initDropdownMenu } from "./ui/dropdown-menu.js";

function initMenuToggle() {
  const sidebar = document.getElementById("rside");
  const overlay = document.getElementById("roverlay");
  const toggle = document.getElementById("rmenu-toggle");
  if (!sidebar || !overlay || !toggle) return;

  const setOpen = (open) => {
    sidebar.classList.toggle("is-open", open);
    overlay.classList.toggle("is-visible", open);
    toggle.setAttribute("aria-expanded", String(open));
  };

  toggle.addEventListener("click", () => setOpen(!sidebar.classList.contains("is-open")));
  overlay.addEventListener("click", () => setOpen(false));
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") setOpen(false);
  });
}

function setBadgeEls(key, value) {
  const els = document.querySelectorAll(`[data-nav-badge="${key}"]`);
  els.forEach((el) => {
    const show = Number(value) > 0;
    el.hidden = !show;
    el.textContent = show ? String(value) : "";
    el.classList.toggle("is-empty", !show);
  });
}

async function refreshNotificationBadge() {
  try {
    const data = await apiFetch("/notifications/unread-count");
    const count = Number(data && data.count) || 0;
    setNavBadge("notifications", count);
    setBadgeEls("notifications", count);
  } catch {
    /* badge is best-effort */
  }
}

function initDate() {
  document.querySelectorAll("[data-resident-date]").forEach((el) => {
    el.textContent = new Date().toLocaleDateString("en-US", {
      weekday: "short",
      month: "short",
      day: "numeric",
    });
  });
}

function initLogout() {
  document.querySelectorAll('[data-action="logout"]').forEach((el) => {
    el.addEventListener("click", (e) => {
      e.preventDefault();
      clearSession();
      window.location.replace("/auth/logout.php");
    });
  });
}

let booted = false;

export function initResidentShell() {
  if (booted) return;
  booted = true;
  bootstrapPageAuth();
  createIcons({ icons });
  initMenuToggle();
  initDate();
  initLogout();
  initDropdownMenu(document);
  refreshNotificationBadge();
}

if (document.readyState === "loading") {
  document.addEventListener("DOMContentLoaded", initResidentShell);
} else {
  initResidentShell();
}

export { setBadgeEls as setResidentNavBadge };
