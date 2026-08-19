import { Button } from "../../js/components/ui/button.js";
import { Badge } from "../../js/components/ui/badge.js";

// Hero section — split layout: copy on the left, illustrative visual on the right.
export function Hero() {
  return `
  <section id="home" class="hero">
    <div class="hero-glow" aria-hidden="true"></div>
    <div class="container hero-split">
      <div class="hero-copy">
        ${Badge({
          text: "For Puspin & Aspin welfare",
          variant: "secondary",
          className: "hero-badge",
          icon: "paw-print",
        })}

        <h1 class="hero-title">
          The centralized rescue platform for every <span class="text-primary">stray, injured &amp; abandoned</span> animal
        </h1>

        <p class="hero-subtitle">
          Fur<span class="font-semibold text-foreground">escue</span> connects rescuers, city veterinarians, and the
          community on one map-driven platform &mdash; so urgent cases get found faster and more animals find safe homes.
        </p>

        <div class="hero-actions">
          ${Button({
            text: "Report an Animal",
            variant: "default",
            size: "lg",
            href: "#report",
            icon: "map-pin",
          })}
          ${Button({
            text: "Browse for Adoption",
            variant: "outline",
            size: "lg",
            href: "#adopt",
            icon: "home",
          })}
        </div>

        <div class="hero-meta">
          <div class="hero-meta-item"><i data-lucide="map-pin"></i><span>Live map of cases</span></div>
          <div class="hero-meta-item"><i data-lucide="heart-handshake"></i><span>Community-driven</span></div>
        </div>
      </div>

      <div class="hero-visual">
        <div class="hero-visual-card">
          <svg viewBox="0 0 440 380" class="hero-art" role="img" aria-label="Map locating rescued Puspin and Aspin">
            <defs>
              <linearGradient id="pinGrad" x1="0" y1="0" x2="1" y2="1">
                <stop offset="0" stop-color="hsl(221 83% 53%)" />
                <stop offset="1" stop-color="hsl(217 91% 60%)" />
              </linearGradient>
              <pattern id="dots" width="28" height="28" patternUnits="userSpaceOnUse">
                <circle cx="3" cy="3" r="2.2" fill="hsl(214 50% 82%)" />
              </pattern>
            </defs>

            <rect x="0" y="0" width="440" height="380" fill="url(#dots)" opacity="0.55" />

            <path d="M80 300 C 150 230 190 250 225 160 S 340 130 380 90"
                  fill="none" stroke="hsl(221 83% 53% / 0.45)" stroke-width="3"
                  stroke-dasharray="5 9" stroke-linecap="round" />

            <circle cx="80" cy="300" r="10" fill="hsl(217 91% 60%)" stroke="#fff" stroke-width="3" />
            <circle cx="380" cy="90" r="10" fill="hsl(217 91% 60%)" stroke="#fff" stroke-width="3" />

            <circle cx="120" cy="110" r="40" fill="hsl(217 91% 60% / 0.10)" />
            <circle cx="330" cy="270" r="30" fill="hsl(221 83% 53% / 0.10)" />

            <g transform="translate(225 175)">
              <ellipse cx="0" cy="22" rx="26" ry="8" fill="hsl(221 83% 30% / 0.18)" />
              <path d="M0 14 C -34 -36 -34 -82 0 -82 C 34 -82 34 -36 0 14 Z" fill="url(#pinGrad)" />
              <g fill="#fff" transform="translate(0 -52)">
                <ellipse cx="0" cy="8" rx="14" ry="11" />
                <circle cx="-14" cy="-6" r="5.5" />
                <circle cx="14" cy="-6" r="5.5" />
                <circle cx="-6" cy="-14" r="5" />
                <circle cx="6" cy="-14" r="5" />
              </g>
            </g>

            <g fill="hsl(217 91% 60% / 0.30)">
              <path transform="translate(300 150) scale(1.1)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
              <path transform="translate(150 250) scale(0.9)" d="M0 6 C -7 -3 -18 3 0 16 C 18 3 7 -3 0 6 Z" />
            </g>
          </svg>

          <div class="float-card float-card--tl">
            <span class="float-icon"><i data-lucide="map-pin"></i></span>
            <div>
              <strong>128</strong>
              <small>active cases</small>
            </div>
          </div>

          <div class="float-card float-card--br">
            <span class="float-icon"><i data-lucide="heart"></i></span>
            <div>
              <strong>64</strong>
              <small>adopted</small>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>`;
}

