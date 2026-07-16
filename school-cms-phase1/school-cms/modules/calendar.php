<?php
$ym = $_GET['m'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $ym)) $ym = date('Y-m');
[$year, $mon] = array_map('intval', explode('-', $ym));
$first = strtotime("$ym-01");
$days_in_month = (int)date('t', $first);
$start_dow = (int)date('N', $first); // 1=จันทร์
$prev = date('Y-m', strtotime('-1 month', $first));
$next = date('Y-m', strtotime('+1 month', $first));
$thai_months = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน','กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];

$evs = rows("SELECT * FROM events WHERE start_date <= ? AND COALESCE(end_date,start_date) >= ?
             ORDER BY start_date", ["$ym-31", "$ym-01"]);
$by_day = [];
foreach ($evs as $ev) {
  $s = max(strtotime($ev['start_date']), $first);
  $e2 = min(strtotime($ev['end_date'] ?: $ev['start_date']), strtotime("$ym-$days_in_month"));
  for ($d = $s; $d <= $e2; $d += 86400) $by_day[(int)date('j', $d)][] = $ev;
}
$type_labels = ['activity'=>'กิจกรรม','meeting'=>'ประชุม','holiday'=>'วันหยุด','important'=>'งานสำคัญ'];
?>
<div class="container section">
  <div class="section-head">
    <h2>ปฏิทินกิจกรรม</h2>
    <span>
      <a class="btn ghost" href="<?= url('calendar', ['m' => $prev]) ?>">←</a>
      <strong style="margin:0 10px;font-family:var(--font-display)"><?= $thai_months[$mon] ?> <?= $year + 543 ?></strong>
      <a class="btn ghost" href="<?= url('calendar', ['m' => $next]) ?>">→</a>
    </span>
  </div>

  <div class="card" style="padding:16px;overflow-x:auto">
    <table style="width:100%;border-collapse:collapse;min-width:640px" aria-label="ปฏิทินรายเดือน">
      <thead><tr>
        <?php foreach (['จ.','อ.','พ.','พฤ.','ศ.','ส.','อา.'] as $d): ?>
          <th style="padding:8px;color:var(--muted);font-weight:600"><?= $d ?></th>
        <?php endforeach; ?>
      </tr></thead>
      <tbody>
      <?php
      $cell = 1 - ($start_dow - 1);
      while ($cell <= $days_in_month) {
        echo '<tr>';
        for ($i = 0; $i < 7; $i++, $cell++) {
          if ($cell < 1 || $cell > $days_in_month) { echo '<td></td>'; continue; }
          $today = ($cell == date('j') && $ym === date('Y-m'));
          echo '<td style="vertical-align:top;border:1px solid var(--line);padding:6px;height:72px'
             . ($today ? ';background:color-mix(in srgb,var(--accent) 18%,transparent)' : '') . '">';
          echo '<strong style="font-size:.85rem">' . $cell . '</strong>';
          foreach ($by_day[$cell] ?? [] as $ev) {
            echo '<div class="ev-item ' . e($ev['etype']) . '" style="font-size:.72rem;margin-top:4px;padding-left:6px;border-left-width:3px">'
               . e(mb_substr($ev['title'], 0, 22)) . '</div>';
          }
          echo '</td>';
        }
        echo '</tr>';
      }
      ?>
      </tbody>
    </table>
  </div>

  <div class="section-head" style="margin-top:34px"><h2>รายการกิจกรรมเดือนนี้</h2></div>
  <?php if (!$evs): ?><p class="empty">ไม่มีกิจกรรมในเดือนนี้</p>
  <?php else: ?>
  <ul class="doc-list">
    <?php foreach ($evs as $ev): ?>
    <li>
      <div class="doc-main ev-item <?= e($ev['etype']) ?>">
        <span class="ev-date"><?= thai_date($ev['start_date']) ?><?= $ev['end_date'] && $ev['end_date'] !== $ev['start_date'] ? ' – ' . thai_date($ev['end_date']) : '' ?></span><br>
        <strong><?= e($ev['title']) ?></strong>
        <div class="doc-sub">
          <span class="badge"><?= $type_labels[$ev['etype']] ?? '' ?></span>
          <?php if ($ev['location']): ?><span>📍 <?= e($ev['location']) ?></span><?php endif; ?>
          <?php if ($ev['detail']): ?><span><?= e($ev['detail']) ?></span><?php endif; ?>
        </div>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>
</div>
