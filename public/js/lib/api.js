export const API_BASE_URL =
  window.FURESCUE_API_BASE_URL || "/api/v1";

const TOKEN_KEY = "furescue_access_token";
const REFRESH_KEY = "furescue_refresh_token";
const USER_KEY = "furescue_user";

export function getAccessToken() {
  return localStorage.getItem(TOKEN_KEY) || "";
}

export function getRefreshToken() {
  return localStorage.getItem(REFRESH_KEY) || "";
}

export function getSessionUser() {
  try {
    const raw = localStorage.getItem(USER_KEY);
    return raw ? JSON.parse(raw) : null;
  } catch {
    return null;
  }
}

export function setSession({ access_token, refresh_token, user, tokens }) {
  localStorage.setItem(TOKEN_KEY, access_token || (tokens && tokens.access_token) || "");
  localStorage.setItem(REFRESH_KEY, refresh_token || (tokens && tokens.refresh_token) || "");
  localStorage.setItem(USER_KEY, JSON.stringify(user || null));
}

export function clearSession() {
  localStorage.removeItem(TOKEN_KEY);
  localStorage.removeItem(REFRESH_KEY);
  localStorage.removeItem(USER_KEY);
}

export function redirectToLogin() {
  clearSession();
  window.location.replace("/auth/login.php");
}

export function homePathForRole(user) {
  const role = user && user.role;
  if (role === "admin") return "/admin/";
  if (role === "rescuer") return "/cases/";
  if (role === "resident") return "/reports/";
  return "/index.php";
}

export function redirectForRole(user) {
  window.location.replace(homePathForRole(user));
}

export async function apiFetch(path, { method = "GET", body, auth = true } = {}) {
  const payload = await request(path, { method, body, auth });
  return payload && payload.data;
}

export async function apiFetchFull(path, opts = {}) {
  return request(path, opts);
}

export async function apiUpload(path, formData) {
  const headers = {};
  const token = getAccessToken();
  if (token) headers["Authorization"] = `Bearer ${token}`;
  let res;
  try {
    res = await fetch(`${API_BASE_URL}${path}`, {
      method: "POST",
      headers,
      body: formData,
    });
  } catch {
    throw new Error("Cannot reach the server. Make sure the backend is running.");
  }
  let payload = null;
  try {
    payload = await res.json();
  } catch {
    /* ignore */
  }
  if (!res.ok) {
    const err = new Error(
      (payload && payload.error && payload.error.message) || `Request failed (${res.status})`
    );
    err.code = payload && payload.error && payload.error.code;
    err.status = res.status;
    throw err;
  }
  return payload;
}

async function request(path, { method = "GET", body, auth = true } = {}) {
  const headers = { "Content-Type": "application/json" };
  let sentToken = "";
  if (auth) {
    sentToken = getAccessToken();
    if (sentToken) headers["Authorization"] = `Bearer ${sentToken}`;
  }

  let res;
  try {
    res = await fetch(`${API_BASE_URL}${path}`, {
      method,
      headers,
      body: body !== undefined ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new Error("Cannot reach the server. Make sure the backend is running.");
  }

  let payload = null;
  try {
    payload = await res.json();
  } catch {

  }

  if (!res.ok) {
    const err = new Error(
      (payload && payload.error && payload.error.message) ||
        `Request failed (${res.status})`
    );
    err.code = payload && payload.error && payload.error.code;
    err.status = res.status;
    if (res.status === 401 && sentToken && getAccessToken() === sentToken) {
      clearSession();
    }
    throw err;
  }

  return payload;
}

export function login(email, password) {
  return apiFetch("/auth/login", {
    method: "POST",
    auth: false,
    body: { email, password },
  }).then((data) => {
    setSession(data);
    return data.user;
  });
}

export function requireAuth(roles = []) {
  const user = getSessionUser();
  if (!getAccessToken() || !user) {
    redirectToLogin();
    return null;
  }
  if (roles.length > 0 && !roles.includes(user.role)) {
    redirectForRole(user);
    return null;
  }
  return user;
}
