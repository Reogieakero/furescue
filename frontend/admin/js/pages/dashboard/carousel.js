// Auto-advancing, infinite right-to-left carousel.
// Uses the clone-first-slide technique: a duplicate of slide 0 is appended to
// the track so the loop never jumps back to the beginning — it glides onto the
// clone, then snaps (transition disabled) to the real first slide.

const INTERVAL_MS = 4000;
const TRANSITION_MS = 500;

export function createCarousel(root) {
  if (!root) return null;
  const track = root.querySelector(".carousel-track");
  const dots = Array.from(root.querySelectorAll(".carousel-dot"));
  if (!track || dots.length === 0) return null;

  const hasClone = track.children.length > dots.length;
  let index = 0;
  let timer = null;

  const setPos = (i, animate) => {
    track.style.transition = animate ? `transform ${TRANSITION_MS}ms ease` : "none";
    track.style.transform = `translateX(-${i * 100}%)`;
  };
  const syncDots = () => {
    dots.forEach((d, j) => d.classList.toggle("is-active", j === index % dots.length));
  };

  const go = (i) => {
    index = i;
    setPos(index, true);
    syncDots();
  };

  const advance = () => {
    if (index === dots.length - 1) {
      // Move onto the clone (which shows the first slide), then snap home.
      setPos(index + 1, true);
      syncDots();
      window.setTimeout(() => {
        index = 0;
        setPos(0, false);
        syncDots();
      }, TRANSITION_MS);
    } else {
      go(index + 1);
    }
  };

  const start = () => {
    if (dots.length <= 1) return;
    stop();
    timer = window.setInterval(advance, INTERVAL_MS);
  };
  const stop = () => {
    if (timer) window.clearInterval(timer);
    timer = null;
  };

  dots.forEach((d, j) => {
    d.addEventListener("click", () => {
      go(j);
      start();
    });
  });
  root.addEventListener("mouseenter", stop);
  root.addEventListener("mouseleave", start);

  setPos(0, false);
  syncDots();
  start();

  return { advance, start, stop };
}