import { Button } from "../../js/components/ui/button.js";

export function CTA() {
  return `
  <section id="signup" class="section">
    <div class="container">
      <div class="cta-card">
        <div class="cta-glow" aria-hidden="true"></div>
        <div class="cta-inner">
          <h2 class="cta-title">Ready to make rescues faster &amp; smarter?</h2>
          <p class="cta-subtitle">
            Join rescuers, city veterinarians, and community members already working
            together for Puspin and Aspin welfare.
          </p>
          <div class="cta-actions">
            ${Button({
              text: "Get Started",
              variant: "default",
              size: "lg",
              href: "#report",
              icon: "paw-print",
            })}
            ${Button({
              text: "Report an Animal",
              variant: "outline",
              size: "lg",
              href: "#report",
              icon: "map-pin",
            })}
          </div>
        </div>
      </div>
    </div>
  </section>`;
}
