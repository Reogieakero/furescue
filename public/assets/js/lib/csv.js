export function csvEscape(value) {
  const s = value == null ? "" : String(value);
  if (/[",\n\r]/.test(s)) return `"${s.replace(/"/g, '""')}"`;
  return s;
}

export function toCsv(headers, rows) {
  const line = (cells) => cells.map(csvEscape).join(",");
  return [line(headers), ...rows.map(line)].join("\r\n");
}

export function datedCsvName(slug) {
  return `furescue-${slug}-${new Date().toISOString().slice(0, 10)}.csv`;
}

export function downloadCsv(filename, headers, rows) {
  const blob = new Blob(["\uFEFF" + toCsv(headers, rows)], { type: "text/csv;charset=utf-8" });
  const href = URL.createObjectURL(blob);
  const a = document.createElement("a");
  a.href = href;
  a.download = filename;
  document.body.appendChild(a);
  a.click();
  a.remove();
  setTimeout(() => URL.revokeObjectURL(href), 1000);
}
