import { esc } from "/assets/js/lib/format.js";

export function TableHead(cols = []) {
  const list = Array.isArray(cols) ? cols : cols.cols || [];
  const th = list.map((c) => `<th>${esc(c)}</th>`).join("");
  return `
  <thead>
    <tr class="table-head">
      ${th}
    </tr>
  </thead>`;
}
