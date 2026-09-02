import { createIcons, icons } from "lucide";
import { openDrawer } from "/assets/js/components/ui/drawer.js";
import { Button } from "/assets/js/components/ui/button.js";
import { shortId, titleCase } from "/admin/js/helpers.js";
import { esc, applicantName, animalName } from "../components/util.js";
import { findAdoption, runApprove, runDecline, runComplete } from "./actions.js";

function row(label, value) {
  return `
    <div class="dialog-info-row">
      <span class="dialog-info-label">${esc(label)}</span>
      <span class="dialog-info-value">${esc(value || "—")}</span>
    </div>`;
}

function when(value) {
  if (!value) return "—";
  const d = new Date(value);
  return Number.isNaN(d.getTime()) ? "—" : d.toLocaleString();
}

export function openDetailsDrawer(id) {
  const a = findAdoption(id);
  if (!a) return;
  const name = applicantName(a) || shortId(a.applicant_id);
  const animal = animalName(a) || shortId(a.animal_id);
  const message = a.message && String(a.message).trim() ? String(a.message).trim() : "—";
  const rejection = a.rejection_reason && String(a.rejection_reason).trim() ? String(a.rejection_reason).trim() : "—";

  const opts = {
    title: "Adoption application details",
    body: `<div class="dialog-info">
      ${row("Application", shortId(a.id))}
      ${row("Applicant", name)}
      ${row("Animal", animal)}
      ${row("Status", titleCase(a.status))}
      ${row("Message", message)}
      ${a.status === "rejected" || rejection !== "—" ? row("Rejection reason", rejection) : ""}
      ${row("Submitted", when(a.created_at))}
      ${row("Reviewed", when(a.reviewed_at))}
      ${row("Completed", when(a.completed_at))}
    </div>`,
  };

  if (a.status === "pending") {
    opts.footer = `${Button({ text: "Reject", variant: "outline", attrs: 'data-drawer-act="decline"' })}
      ${Button({ text: "Approve", variant: "default", attrs: 'data-drawer-act="approve"' })}`;
    opts.onMount = (bodyEl) => {
      const drawer = bodyEl.closest(".drawer");
      const decline = drawer.querySelector('[data-drawer-act="decline"]');
      const approve = drawer.querySelector('[data-drawer-act="approve"]');
      if (decline) decline.addEventListener("click", () => runDecline(id));
      if (approve) approve.addEventListener("click", () => runApprove(id));
    };
  } else if (a.status === "approved") {
    opts.footer = `${Button({ text: "Complete", variant: "default", attrs: 'data-drawer-act="complete"' })}`;
    opts.onMount = (bodyEl) => {
      const drawer = bodyEl.closest(".drawer");
      const complete = drawer.querySelector('[data-drawer-act="complete"]');
      if (complete) complete.addEventListener("click", () => runComplete(id));
    };
  }

  openDrawer(opts);
  createIcons({ icons });
}
