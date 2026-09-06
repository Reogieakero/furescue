import { openThread, sendCurrent, closeThread } from "./actions.js";
import { openComposeDialog } from "../components/compose.js";

let bound = false;

export function initMessagesEvents() {
  if (bound) return;
  bound = true;
  const app = document.getElementById("app");
  if (!app) return;

  app.addEventListener("click", (e) => {
    const compose = e.target.closest("[data-action='compose']");
    if (compose) {
      void openComposeDialog();
      return;
    }
    const item = e.target.closest("#amsg-threads [data-key]");
    if (item) {
      void openThread(String(item.dataset.key));
      return;
    }
    if (e.target.closest("#amsg-back")) {
      closeThread();
    }
  });

  app.addEventListener("submit", (e) => {
    if (e.target.id !== "amsg-form") return;
    e.preventDefault();
    void sendCurrent();
  });
}
