
let activeTip = null;
let activeTrigger = null;

function removeTip() {
  if (!activeTip) return;
  const tip = activeTip;
  activeTip = null;
  activeTrigger = null;
  if (tip.onDestroy) {
    try { tip.onDestroy(tip.el); } catch (e) {  }
  }
  tip.el.remove();
}

export function hideTooltip() {
  removeTip();
}

export function attachTooltip(trigger, opts = {}) {
  const {
    placement = "top",
    offset = 10,
    delay = 120,
    content,
    onMount,
    onDestroy,
    shouldShow,
    className,
  } = opts;

  let showTimer = null;
  let hideTimer = null;

  const show = () => {
    if (activeTip && activeTrigger === trigger) return;
    if (hideTimer) { clearTimeout(hideTimer); hideTimer = null; }
    if (showTimer) return;
    showTimer = setTimeout(() => {
      showTimer = null;
      removeTip();
      const el = document.createElement("div");
      el.className = "tooltip" + (className ? " " + className : "");
      el.setAttribute("role", "tooltip");
      el.innerHTML = typeof content === "function" ? content() : (content || "");
      document.body.appendChild(el);

      const rect = trigger.getBoundingClientRect();
      const tw = el.offsetWidth;
      const th = el.offsetHeight;
                  let place = placement;
      if (place === "right" && rect.right + tw + offset + 16 > window.innerWidth) place = "left";
      else if (place === "left" && rect.left - tw - offset - 16 < 0) place = "right";
      let top = 0;
      let left = 0;
      if (place === "top") {
        top = rect.top - th - offset;
        left = rect.left + rect.width / 2 - tw / 2;
      } else if (place === "bottom") {
        top = rect.bottom + offset;
        left = rect.left + rect.width / 2 - tw / 2;
      } else if (place === "right") {
        top = rect.top + rect.height / 2 - th / 2;
        left = rect.right + offset;
      } else if (place === "top-right") {
        top = offset;
        left = window.innerWidth - tw - offset;
      } else {
        top = rect.top + rect.height / 2 - th / 2;
        left = rect.left - tw - offset;
      }
      left = Math.max(8, Math.min(left, window.innerWidth - tw - 8));
      top = Math.max(8, Math.min(top, window.innerHeight - th - 8));
      el.style.top = `${top}px`;
      el.style.left = `${left}px`;

      activeTip = { el, onDestroy };
      activeTrigger = trigger;
      if (onMount) onMount(el);
    }, delay);
  };

  const hide = () => {
    if (showTimer) { clearTimeout(showTimer); showTimer = null; }
    if (hideTimer) clearTimeout(hideTimer);
    hideTimer = setTimeout(() => {
      if (activeTrigger === trigger) removeTip();
    }, delay);
  };

  const over = (e) =>
    !(typeof shouldShow === "function" && !shouldShow(e));

  const onMove = (e) => {
    if (over(e)) show();
    else hide();
  };

  trigger.addEventListener("mousemove", onMove);
  trigger.addEventListener("mouseleave", hide);
}
