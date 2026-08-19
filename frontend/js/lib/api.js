// Shared API client for FurEscue frontend.
// Set window.FURESCUE_API_BASE_URL before this module loads to override the backend URL.
export const API_BASE_URL =
  window.FURESCUE_API_BASE_URL || "http://127.0.0.1:8899/api/v1";

const TOKEN_KEY = "furescue_access_token";
const REFRESH_KEY = "furescue_refresh_token";
const USER_KEY = "furescue_user";

// All entry pages live one folder deep under frontend/ (landing/, auth/, admin/),
// so root-relative redirects are "../<folder>/<page>".
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
  window.location.replace("../auth/login.html");
}

export function redirectForRole(user) {
  if (user && user.role === "admin") {
    window.location.replace("../admin/index.html");
    return;
  }
  window.location.replace("../landing/index.html");
}

export async function apiFetch(path, { method = "GET", body, auth = true } = {}) {
  const payload = await request(path, { method, body, auth });
  return payload && payload.data;
}

// Like apiFetch but returns the full payload { data, meta } so paginated
// reads can access meta.total (item count) alongside the rows.
export async function apiFetchFull(path, opts = {}) {
  return request(path, opts);
}

async function request(path, { method = "GET", body, auth = true } = {}) {
  const headers = { "Content-Type": "application/json" };
  if (auth) {
    const token = getAccessToken();
    if (token) headers["Authorization"] = `Bearer ${token}`;
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
    /* non-JSON response */
  }

  if (!res.ok) {
    const err = new Error(
      (payload && payload.error && payload.error.message) ||
        `Request failed (${res.status})`
    );
    err.code = payload && payload.error && payload.error.code;
    err.status = res.status;
    if (res.status === 401) {
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

// Guards a page: redirects to login when unauthenticated and to the right
// home when the role doesn't match. Returns the session user or null.
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