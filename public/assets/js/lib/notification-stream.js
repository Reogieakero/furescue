import { getAccessToken, API_BASE_URL, apiFetch } from "/assets/js/lib/api.js";

const POLL_MS = 30000;

export function subscribeToNotifications(callback) {
  let closed = false;
  let source = null;
  let timer = null;

  const emit = (payload) => {
    if (closed || !payload || typeof callback !== "function") return;
    callback(payload);
  };

  const poll = async () => {
    if (closed) return;
    try {
      const data = await apiFetch("/notifications/unread-count");
      emit({ type: "sync", unread_count: Number(data && data.count) || 0 });
    } catch {
      /* badge is best-effort */
    }
  };

  const stopSource = () => {
    if (!source) return;
    source.close();
    source = null;
  };

  const token = getAccessToken();
  if (token && typeof EventSource !== "undefined") {
    const url = `${API_BASE_URL}/notifications/stream?access_token=${encodeURIComponent(token)}`;
    source = new EventSource(url);
    source.addEventListener("done", stopSource);
    source.onerror = stopSource;
    source.onmessage = (event) => {
      let payload = null;
      try {
        payload = JSON.parse(event.data);
      } catch {
        /* ignore malformed frames */
      }
      emit(payload);
    };
  }

  timer = setInterval(poll, POLL_MS);

  return {
    close() {
      closed = true;
      stopSource();
      if (timer) {
        clearInterval(timer);
        timer = null;
      }
    },
  };
}
