// Stats / impact band
const stats = [
  { value: "1", label: "Centralized platform", sub: "for reports, maps & adoption" },
  { value: "24/7", label: "Community reporting", sub: "anytime, from anywhere" },
  { value: "3", label: "Connected roles", sub: "rescuers · vets · community" },
  { value: "100%", label: "Puspin & Aspin focus", sub: "native cats & dogs first" },
];

function Stat(s) {
  return `
  <div class="stat">
    <div class="stat-value">${s.value}</div>
    <div class="stat-label">${s.label}</div>
    <div class="stat-sub">${s.sub}</div>
  </div>`;
}

export function Stats() {
  return `
  <section class="section section-band">
    <div class="container">
      <div class="stats-grid">
        ${stats.map(Stat).join("")}
      </div>
    </div>
  </section>`;
}
