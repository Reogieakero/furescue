const STATUS_RANK = { pending_review: 0, approved: 1, rejected: 2 };

export function uniqueListingsByAnimal(rows) {
  const best = new Map();
  for (const row of rows || []) {
    const key = String((row && row.animal_id) || "");
    if (!key) continue;
    const prev = best.get(key);
    if (!prev) {
      best.set(key, row);
      continue;
    }
    const rNew = STATUS_RANK[row.status] ?? 9;
    const rOld = STATUS_RANK[prev.status] ?? 9;
    if (rNew < rOld) {
      best.set(key, row);
      continue;
    }
    if (rNew === rOld) {
      const tNew = new Date(row.created_at || 0).getTime();
      const tOld = new Date(prev.created_at || 0).getTime();
      if (tNew > tOld) best.set(key, row);
    }
  }
  return [...best.values()];
}
