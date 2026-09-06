/**
 * Resolve when `el` intersects the viewport (or immediately if it already does).
 * Used to defer Leaflet / Chart.js until the widget can actually be seen.
 */
export function whenVisible(el, { rootMargin = "160px" } = {}) {
  if (!el) return Promise.resolve(false);
  if (typeof IntersectionObserver !== "function") return Promise.resolve(true);

  const rect = el.getBoundingClientRect();
  if (rect.width > 0 && rect.height > 0 && rect.top < window.innerHeight && rect.bottom > 0) {
    return Promise.resolve(true);
  }

  return new Promise((resolve) => {
    const io = new IntersectionObserver(
      (entries) => {
        if (entries.some((entry) => entry.isIntersecting)) {
          io.disconnect();
          resolve(true);
        }
      },
      { rootMargin, threshold: 0 }
    );
    io.observe(el);
  });
}
