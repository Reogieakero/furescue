import { apiFetch, apiFetchFull } from "/assets/js/lib/api.js";

function listFields(row) {
  if (!row || typeof row !== "object") return row;
  return {
    id: row.id,
    title: row.title,
    category: row.category,
    published_status: row.published_status,
    created_at: row.created_at,
    created_by: row.created_by,
  };
}

export async function fetchModules({ publishedStatus = "", category = "" } = {}) {
  const params = new URLSearchParams({ per_page: "100" });
  if (publishedStatus) params.set("published_status", publishedStatus);
  if (category) params.set("category", category);
  const payload = await apiFetchFull(`/elearning/modules?${params.toString()}`);
  const items = Array.isArray(payload.data) ? payload.data.map(listFields) : [];
  return { items, total: (payload.meta && payload.meta.total) ?? items.length };
}

export async function fetchModule(id) {
  const data = await apiFetch(`/elearning/modules/${encodeURIComponent(id)}`);
  const mod = data && data.module;
  if (!mod) throw new Error("Module not found");
  return mod;
}

export async function createModule(body) {
  const data = await apiFetch("/elearning/modules", { method: "POST", body });
  return data && data.module;
}

export async function updateModule(id, body) {
  const data = await apiFetch(`/elearning/modules/${encodeURIComponent(id)}`, {
    method: "PATCH",
    body,
  });
  return data && data.module;
}
