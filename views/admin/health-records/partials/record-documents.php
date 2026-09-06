<?php

$docRowsHtml = '';
foreach (array_slice($record['documents'] ?? [], 0, 3) as $i => $d) {
    $docRowsHtml .= '
    <tr class="hr-doc-row" data-action="view-document" data-idx="' . e((string) $i) . '" style="cursor:pointer">
      <td class="table-cell table-cell--strong"><i data-lucide="file-text" class="hr-doc-ic"></i>' . e($d['name']) . '</td>
      <td class="table-cell table-cell--muted">' . e($d['type'] ?? 'Document') . '</td>
      <td class="table-cell table-cell--mono">' . e($d['meta'] ?? '—') . '</td>
      <td class="table-cell table-cell--right">' . ($d['fileUrl'] ? '<span class="hr-doc-open">View</span>' : '<span class="table-cell--muted">—</span>') . '</td>
    </tr>';
}

$documentsPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="file-text"></i><h3 class="panel-title">Health Documents</h3></div>
    </div>
    <div class="panel-body">
      ' . ($docRowsHtml !== '' ? '<div class="table-wrap hr-doc-table-wrap"><table class="table"><thead class="table-head"><tr><th>Document</th><th>Type</th><th>Meta</th><th class="table-cell--right">File</th></tr></thead><tbody>' . $docRowsHtml . '</tbody></table></div>' : $emptyState('No documents uploaded')) . '
    </div>
  </section>';
