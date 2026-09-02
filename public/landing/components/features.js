import { Card, CardHeader, CardTitle, CardDescription } from "/assets/js/components/ui/card.js";

const features = [
  {
    icon: "map",
    title: "Map-based case locating",
    desc:
      "Every report is pinned to a map so rescuers and vets can see exactly where help is needed and route efficiently.",
  },
  {
    icon: "bell-ring",
    title: "Urgency prioritization",
    desc:
      "Injured and at-risk animals are surfaced first, helping teams act on the most critical cases without delay.",
  },
  {
    icon: "zap",
    title: "Faster response times",
    desc:
      "A centralized inbox of reports removes the back-and-forth and shortens the path from sighting to rescue.",
  },
  {
    icon: "bar-chart-3",
    title: "Welfare analytics",
    desc:
      "City vets get visibility into incident hotspots and trends to plan data-driven, resource-smart action.",
  },
  {
    icon: "home",
    title: "Adoption marketplace",
    desc:
      "Community members browse Puspin and Aspin available for adoption, making the process simple and efficient.",
  },
  {
    icon: "users",
    title: "Community collaboration",
    desc:
      "Public reports, rescuer coordination, and vet oversight in one platform that strengthens the whole network.",
  },
];

function FeatureCard(f) {
  const inner = `
    ${CardHeader({
      className: "feature-header",
      children: `
        <div class="feature-icon"><i data-lucide="${f.icon}"></i></div>
        ${CardTitle({ children: f.title, className: "feature-title" })}
        ${CardDescription({ children: f.desc })}
      `,
    })}`;

  return Card({ className: "feature-card", children: inner });
}

export function Features() {
  return `
  <section id="features" class="section section-muted">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow"><i data-lucide="sparkles"></i> Features</span>
        <h2 class="section-title">Everything a modern rescue operation needs</h2>
        <p class="section-subtitle">
          Practical tools that turn scattered reports into coordinated, measurable action.
        </p>
      </div>
      <div class="features-grid">
        ${features.map(FeatureCard).join("")}
      </div>
    </div>
  </section>`;
}
