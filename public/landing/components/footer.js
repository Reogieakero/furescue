export function Footer() {
  const year = new Date().getFullYear();
  const cols = [
    {
      title: "Platform",
      links: ["Report a stray", "Browse adoption", "Find rescuers", "Map view"],
    },
    {
      title: "For",
      links: ["Rescuers", "City Veterinarian", "Community", "Volunteers"],
    },
    {
      title: "Resources",
      links: ["How it works", "Safety guide", "Contact", "FAQ"],
    },
  ];

  const colMarkup = cols
    .map(
      (c) => `
      <div class="footer-col">
        <h4 class="footer-col-title">${c.title}</h4>
        <ul class="footer-col-links">
          ${c.links.map((l) => `<li><a href="#" class="footer-link">${l}</a></li>`).join("")}
        </ul>
      </div>`
    )
    .join("");

  return `
  <footer class="footer">
    <div class="container">
      <div class="footer-top">
        <div class="footer-brand">
          <a href="#home" class="brand">
            <span class="logo-mark" aria-hidden="true"><i data-lucide="paw-print"></i></span>
            <span>Fur<span class="text-primary">escue</span></span>
          </a>
          <p class="footer-tagline">
            A centralized rescue platform for Puspin &amp; Aspin welfare &mdash;
            built for rescuers, city vets, and the community.
          </p>
        </div>
        <div class="footer-cols">
          ${colMarkup}
        </div>
      </div>
      <div class="footer-bottom">
        <p>&copy; ${year} Fur<span class="font-semibold">escue</span>. All rights reserved.</p>
        <p class="footer-muted">Made with care for every stray that needs a home.</p>
      </div>
    </div>
  </footer>`;
}
