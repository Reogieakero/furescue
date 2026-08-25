import { apiFetchFull } from "/js/lib/api.js";

const PER_PAGE = 100;
const MAX_PAGES = 50;

function asList(payload) {
  const items = Array.isArray(payload && payload.data) ? payload.data : [];
  const total = (payload && payload.meta && payload.meta.total) ?? items.length;
  return { items, total: Number(total) || items.length };
}

/** Full adoption queue. Omits `status` so KPIs can count every state client-side. */
export async function listAdoptions() {
  const items = [];
  let page = 1;
  let total = Infinity;
  while (items.length < total && page <= MAX_PAGES) {
    const qs = new URLSearchParams({
      page: String(page),
      per_page: String(PER_PAGE),
    });
    const payload = await apiFetchFull(`/adoptions?${qs}`);
    const chunk = asList(payload);
    total = chunk.total;
    items.push(...chunk.items);
    if (chunk.items.length < PER_PAGE) break;
    page += 1;
  }
  return { items, total: Number.isFinite(total) ? total : items.length };
}

export function approveAdoption(id) {
  return apiFetchFull(`/adoptions/${encodeURIComponent(id)}/approve`, {
    method: "POST",
    body: {},
  });
}

export function rejectAdoption(id, reason) {
  return apiFetchFull(`/adoptions/${encodeURIComponent(id)}/reject`, {
    method: "POST",
    body: { rejection_reason: reason },
  });
}

export function completeAdoption(id) {
  return apiFetchFull(`/adoptions/${encodeURIComponent(id)}/complete`, {
    method: "POST",
    body: {},
  });
}
