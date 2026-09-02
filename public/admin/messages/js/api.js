import { apiFetch, apiFetchFull } from "/assets/js/lib/api.js";

function listItems(path) {
  const sep = path.includes("?") ? "&" : "?";
  return apiFetchFull(`${path}${sep}per_page=100`).then((payload) =>
    Array.isArray(payload && payload.data) ? payload.data : []
  );
}

export function fetchThreads() {
  return apiFetch("/messages/threads").then((data) =>
    data && Array.isArray(data.threads) ? data.threads : []
  );
}

export function fetchThread(relatedType, relatedId) {
  const type = encodeURIComponent(relatedType);
  const id = encodeURIComponent(relatedId);
  return apiFetch(`/messages?related_type=${type}&related_id=${id}`).then((data) =>
    data && Array.isArray(data.messages) ? data.messages : []
  );
}

export function postMessage(body) {
  return apiFetch("/messages", { method: "POST", body });
}

export function markMessageRead(id) {
  return apiFetch(`/messages/${encodeURIComponent(id)}/read`, { method: "PATCH" });
}

export function fetchPendingAdoptions() {
  return listItems("/adoptions?status=pending");
}

export function fetchReports() {
  return listItems("/reports");
}

export function fetchCases() {
  return listItems("/cases");
}
