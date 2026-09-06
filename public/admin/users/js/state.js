import { fetchUsers } from "/assets/js/admin/admin-data.js";

export const state = {
  users: [],
  user: null,
  filter: "all",
  query: "",
  page: 1,
  pageSize: 20,
  error: null,
};

export function currentUserId() {
  return (state.user && state.user.id) || "";
}

export async function loadUsers() {
  try {
    const result = await fetchUsers();
    state.users = Array.isArray(result.items) ? result.items : [];
    state.error = null;
  } catch (err) {
    state.error = (err && err.message) || "Could not load users.";
  }
}
