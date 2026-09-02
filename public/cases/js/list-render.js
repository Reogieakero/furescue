import { Button } from "/assets/js/components/ui/button.js";
import { esc, timeAgo } from "/assets/js/lib/format.js";
import { caseHref, excerpt, shortId, statusChip } from "./status.js";

function rowActions(item) {
  const view = Button({
    text: "View",
    variant: "outline",
    size: "sm",
    icon: "eye",
    href: caseHref(item.id),
  });
  if (item.status === "assigned") {
    return `${Button({
      text: "Accept",
      size: "sm",
      icon: "check",
      attrs: `data-case-act="accept" data-id="${esc(item.id)}"`,
    })}${Button({
      text: "Decline",
      variant: "destructive",
      size: "sm",
      icon: "x",
      attrs: `data-case-act="decline" data-id="${esc(item.id)}"`,
    })}${view}`;
  }
  if (item.status === "in_progress") {
    return `${Button({
      text: "File proof",
      size: "sm",
      icon: "upload",
      href: caseHref(item.id),
    })}${view}`;
  }
  return view;
}

export function caseRow(item) {
  const address = String(item.address_text || "").trim();
  const when = timeAgo(item.updated_at || item.created_at);
  const sub = [address || "Location not listed", when].filter(Boolean).join(" · ");
  return `
  <li class="rlist-row min-w-0" data-case-id="${esc(item.id)}">
    <span class="rlist-icon"><i data-lucide="siren"></i></span>
    <div class="rlist-main">
      <p class="rlist-title">${esc(shortId(item.id))} · ${esc(excerpt(item.animal_description, 90))}</p>
      <p class="rlist-sub">${esc(sub)}</p>
    </div>
    <div class="rlist-side">
      ${statusChip(item.status)}
      ${rowActions(item)}
    </div>
  </li>`;
}

export function listErrorHtml(message) {
  return `
  <li class="rempty">
    <i data-lucide="triangle-alert"></i>
    <p class="rempty-title">Could not load your cases</p>
    <p class="rempty-text">${esc(message || "Please try again.")}</p>
    <button type="button" class="rbtn rbtn--ghost" id="cases-retry">
      <i data-lucide="refresh-cw"></i><span>Try again</span>
    </button>
  </li>`;
}

export function listLoadingHtml() {
  return `<li class="rempty"><i data-lucide="loader-circle"></i><p class="rempty-text">Loading cases…</p></li>`;
}

export function countLabel(n) {
  if (n === 1) return "1 case";
  return `${n} cases`;
}
