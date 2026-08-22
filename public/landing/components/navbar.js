import { Button } from "../../js/components/ui/button.js";

export function Navbar() {
  const links = [
    { label: "Home", href: "#home" },
    { label: "Who It Helps", href: "#audiences" },
    { label: "Features", href: "#features" },
    { label: "How It Works", href: "#how" },
  ];

  const linkMarkup = links
    .map(
      (l) =>
        `<a href="${l.href}" class="nav-link">${l.label}</a>`
    )
    .join("");

  return `
  <header id="navbar" class="nav">
    <div class="container nav-inner">
      <a href="#home" class="brand">
        <span class="logo-mark" aria-hidden="true"><i data-lucide="paw-print"></i></span>
        <span>Fur<span class="text-primary">escue</span></span>
      </a>

      <nav class="nav-links">
        ${linkMarkup}
      </nav>

<div class="nav-actions">
        ${Button({ text: "Log in", variant: "ghost", size: "sm", href: "../auth/login.html" })}
        ${Button({ text: "Get Started", variant: "default", size: "sm", href: "../auth/signup.html" })}
      </div>

      <button id="menu-toggle" class="nav-toggle" aria-label="Toggle menu" aria-expanded="false">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
      </button>
    </div>

    <div id="mobile-menu" class="mobile-menu">
      <nav class="container mobile-menu-links">
        ${linkMarkup}
      </nav>
<div class="container mobile-menu-actions">
        ${Button({ text: "Log in", variant: "outline", size: "sm", href: "../auth/login.html" })}
        ${Button({ text: "Get Started", variant: "default", size: "sm", href: "../auth/signup.html" })}
      </div>
    </div>
  </header>`;
}
