import { getAccessToken, getSessionUser, setSession } from "./api.js";

// Pages are session-guarded server-side; the PHP page mints a short-lived
// access token for the signed-in user and passes it via window.__PAGE_STATE__.
// This syncs that token into the localStorage session api.js reads from.
export function bootstrapPageAuth(state = window.__PAGE_STATE__ || {}) {
  if (!state || !state.accessToken) return null;
  const user = state.user || getSessionUser();
  if (!user) return null;
  if (getAccessToken() !== state.accessToken || !getSessionUser()) {
    setSession({ access_token: state.accessToken, refresh_token: "", user });
  }
  return user;
}
