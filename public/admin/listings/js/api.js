import { apiFetch, apiFetchFull } from "/js/lib/api.js";

export async function fetchListings(status) {
  const q = new URLSearchParams({ per_page: "100" });
  if (status) q.set("status", status);
  const payload = await apiFetchFull(`/adoption-listings?${q}`);
  const items = Array.isArray(payload.data) ? payload.data : [];
  return { items, total: (payload.meta && payload.meta.total) ?? items.length };
}

export function approveListing(id) {
  return apiFetch(`/adoption-listings/${encodeURIComponent(id)}/approve`, {
    method: "POST",
    body: {},
  });
}

export function rejectListing(id, reviewNotes) {
  return apiFetch(`/adoption-listings/${encodeURIComponent(id)}/reject`, {
    method: "POST",
    body: { review_notes: reviewNotes },
  });
}
