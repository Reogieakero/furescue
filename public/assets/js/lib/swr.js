const NS = "furescue:swr:";
const mem = new Map();

function storageKey(key) {
  return NS + key;
}

export function readCache(key) {
  if (mem.has(key)) return mem.get(key);
  try {
    const raw = sessionStorage.getItem(storageKey(key));
    if (!raw) return undefined;
    const value = JSON.parse(raw);
    mem.set(key, value);
    return value;
  } catch {
    return undefined;
  }
}

export function writeCache(key, data) {
  mem.set(key, data);
  try {
    sessionStorage.setItem(storageKey(key), JSON.stringify(data));
  } catch {
  }
  return data;
}

export function clearCache(key) {
  mem.delete(key);
  try {
    sessionStorage.removeItem(storageKey(key));
  } catch {
  }
}

const NAV_KEYS = ["reports", "cases", "rescuers", "health", "applications", "notifications"];

export function getNavBadges() {
  const out = {};
  for (const key of NAV_KEYS) {
    const value = readCache("nav:" + key);
    if (value !== undefined && value !== null && value !== "") out[key] = value;
  }
  return out;
}

export function setNavBadge(key, value) {
  writeCache("nav:" + key, value);
}
