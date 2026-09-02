import { syncPageAuth } from "./api.js";

// Pages are session-guarded server-side; the PHP page mints a short-lived
// access token for the signed-in user and passes it via window.__PAGE_STATE__.
// This syncs that token into the localStorage session api.js reads from.
export function bootstrapPageAuth(state = window.__PAGE_STATE__ || {}) {
  return syncPageAuth(state);
}
