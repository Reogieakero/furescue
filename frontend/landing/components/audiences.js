import { Card, CardHeader, CardTitle, CardDescription, CardContent, CardFooter } from "../../js/components/ui/card.js";
import { Badge } from "../../js/components/ui/badge.js";

const audiences = [
  {
    id: "rescuers",
    icon: "siren",
    title: "Animal Rescuers & Volunteers",
    badge: "Faster response",
    badgeVariant: "default",
    desc:
      "Manage animal-related reports from one place. Locate cases on a map, prioritize the most urgent situations, and respond quicker &mdash; reducing delays across every rescue operation.",
    points: [
      "Live map of reported cases near you",
      "Urgent-first queue & status tracking",
      "Coordinate teams & volunteer shifts",
    ],
  },
  {
    id: "vets",
    icon: "stethoscope",
    title: "City Veterinarian",
    badge: "Data-driven",
    badgeVariant: "secondary",
    desc:
      "Organize and monitor animal welfare data with clear visibility into high-incident areas. Plan actions and allocate resources where they matter most, backed by real reporting.",
    points: [
      "Heatmaps of frequent incident zones",
      "Centralized welfare dashboards",
      "Resource & clinic allocation planning",
    ],
  },
  {
    id: "community",
    icon: "users",
    title: "Community Members",
    badge: "Get involved",
    badgeVariant: "accent",
    desc:
      "A simple, accessible way to report stray, injured, or abandoned animals and to browse pets available for adoption. Strengthen the public&ndash;rescuer collaboration and help animals find permanent homes.",
    points: [
      "Report a stray in under a minute",
      "Browse Puspin & Aspin for adoption",
      "Track the impact of your reports",
    ],
  },
];

function AudienceCard(a) {
  const points = a.points
    .map(
      (p) =>
        `<li class="audience-point"><i class="audience-check" data-lucide="check"></i><span>${p}</span></li>`
    )
    .join("");

  const inner = `
    ${CardHeader({
      className: "audience-header",
      children: `
        <div class="audience-top">
          <div class="audience-icon"><i data-lucide="${a.icon}"></i></div>
          ${Badge({ text: a.badge, variant: a.badgeVariant })}
        </div>
        ${CardTitle({ children: a.title, className: "audience-title" })}
        ${CardDescription({ children: a.desc })}
      `,
    })}
    ${CardContent({
      className: "audience-body",
      children: `<ul class="audience-list">${points}</ul>`,
    })}
    ${CardFooter({
      className: "audience-footer",
      children: `<a class="audience-link" href="#${a.id}">Learn more <i data-lucide="arrow-right"></i></a>`,
    })}`;

  return Card({ className: "audience-card", children: inner });
}

export function Audiences() {
  return `
  <section id="audiences" class="section">
    <div class="container">
      <div class="section-head">
        <span class="section-eyebrow"><i data-lucide="users"></i> Who it helps</span>
        <h2 class="section-title">Built for everyone in the rescue chain</h2>
        <p class="section-subtitle">
          From the neighbor who spots a stray to the city vet planning resources &mdash;
          Fur<span class="font-semibold">escue</span> gives each role the tools they need.
        </p>
      </div>
      <div class="laptop">
        <div class="laptop-screen">
          <div class="laptop-chrome" aria-hidden="true">
            <span></span><span></span><span></span>
          </div>
          <div class="laptop-screen-inner">
            <div class="audiences-grid">
              ${audiences.map(AudienceCard).join("")}
            </div>
          </div>
        </div>
        <div class="laptop-base" aria-hidden="true"></div>
        <div class="laptop-keyboard" aria-hidden="true"></div>
      </div>
    </div>
  </section>`;
}
