import { Spinner } from "/assets/js/components/ui/spinner.js";

export function Stepper({ steps = [], className = "" } = {}) {
  const items = steps
    .map((s, i) => {
      const status = s.status
        ? `<div class="stepper-status">${Spinner({ size: 14 })}<span>${s.status}</span></div>`
        : "";
      return `
      <li class="stepper-step">
        <div class="stepper-marker-wrap">
          <div class="stepper-marker"><span>${s.n}</span></div>
          ${
            i < steps.length - 1
              ? '<span class="stepper-connector" aria-hidden="true"></span>'
              : ""
          }
        </div>
        <div class="stepper-content">
          <h3 class="stepper-title">${s.title}</h3>
          <p class="stepper-desc">${s.desc}</p>
          ${status}
        </div>
      </li>`;
    })
    .join("");

  return `<ol class="stepper ${className}">${items}</ol>`;
}
