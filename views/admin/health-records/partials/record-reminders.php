<?php

$reminderItems = '';
foreach ($record['reminders'] ?? [] as $r) {
    $tone = $TONE[$r['tone']] ?? $TONE['blue'];
    $toneClass = explode(' ', $tone)[0];
    $reminderItems .= '
    <li class="hr-reminder">
      <div class="hr-reminder-left">
        <span class="tint-circle ' . e($toneClass) . '"><i data-lucide="' . e($r['icon']) . '"></i></span>
        <div>
          <div class="hr-reminder-title">' . e($r['title']) . '</div>
          <div class="hr-reminder-due">Due ' . e($r['dueDate']) . '</div>
        </div>
      </div>
      <span class="pill pill--' . e($r['tone']) . '">' . e($r['days'] . ' days') . '</span>
    </li>';
}

$remindersPanelHtml = '
  <section class="panel">
    <div class="panel-head">
      <div class="panel-title-wrap"><i data-lucide="bell"></i><h3 class="panel-title">Upcoming Reminders</h3></div>
    </div>
    <div class="panel-body">
      ' . ($reminderItems !== '' ? '<ul class="hr-reminder-list">' . $reminderItems . '</ul>' : $emptyState('No upcoming reminders')) . '
    </div>
  </section>';
