import { Stepper } from "../../js/components/ui/marker.js";

// "How it works" steps section
const steps = [
  {
    n: "01",
    title: "Report",
    status: "Reporting sighting…",
    desc:
      "Community members spot a stray, injured, or abandoned Puspin or Aspin and file a quick report with a photo and location.",
  },
  {
    n: "02",
    title: "Locate & Prioritize",
    status: "Locating & prioritizing…",
    desc:
      "Reports appear on the shared map. Rescuers and the city vet see urgent cases first and plan the fastest route.",
  },
  {
    n: "03",
    title: "Rescue & Rehome",
    status: "Updating status…",
    desc:
      "Teams respond, vets monitor welfare data, and recovered animals move into the adoption marketplace for a permanent home.",
  },
];

export function HowItWorks() {
  return `
  <section id="how" class="section">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow"><i data-lucide="route"></i> How it works</span>
        <h2 class="section-title">From sighting to safe home in three steps</h2>
        <p class="section-subtitle">
          A simple loop that keeps the whole community moving in the same direction.
        </p>
      </div>
      ${Stepper({ steps })}
    </div>
  </section>`;
}
