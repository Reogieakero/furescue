import { getAccessToken, API_BASE_URL } from "./api.js";

export function subscribeToNotifications(callback) {
  const token = getAccessToken();
  if (!token || typeof EventSource === "undefined") return null;
  const url = `${API_BASE_URL}/notifications/stream?access_token=${encodeURIComponent(token)}`;
  const source = new EventSource(url);
  source.onerror = (err) => {
    console.error("SSE connection error:", err);
    source.close();
  };
  source.onmessage = (event) => {
    let payload = null;
    try {
      payload = JSON.parse(event.data);
    } catch {}
    if (payload && typeof callback === "function") callback(payload);
  };
  return source;
}
