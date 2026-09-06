let chartPromise = null;

/** Dynamic-import Chart.js via the page import map. Cached after the first call. */
export function loadChart() {
  if (!chartPromise) {
    chartPromise = import("chart.js")
      .then((mod) => mod.Chart)
      .catch((err) => {
        console.warn("Chart.js failed to load", err);
        chartPromise = null;
        return null;
      });
  }
  return chartPromise;
}
