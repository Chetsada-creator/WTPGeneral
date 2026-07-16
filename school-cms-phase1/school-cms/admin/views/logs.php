<?php
$total = (int)val("SELECT COUNT(*) FROM activity_log");
[$pages, $offset, $cur] = paginate($total, 30, (int)($_GET['pg'] ?? 1));
$list = rows("SELECT l.*, u.name, u.username FROM activity_log l
              LEFT JOIN users u ON u.id=l.user_id
              ORDER BY l.id DESC LIMIT 30 OFFSET $offset");
?>
<table class="table">
  <thead><tr><th>เวลา</th><th>ผู้ใช้</th><th>การกระทำ</th><th>รายละเอียด</th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td style="white-space:nowrap"><?= thai_date($r['created_at'], true) ?></td>
      <td><?= e($r['name'] ?? 'ระบบ') ?><?= $r['username'] ? ' <span class="badge">' . e($r['username']) . '</span>' : '' ?></td>
      <td><span class="badge"><?= e($r['action']) ?></span></td>
      <td><?= e($r['detail']) ?></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="4" style="text-align:center;color:var(--muted)">ยังไม่มีบันทึก</td></tr><?php endif; ?>
  </tbody>
</table>
<?php if ($pages > 1): ?>
<div class="toolbar" style="margin-top:16px;justify-content:center">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="btn sm <?= $i == $cur ? '' : 'ghost' ?>" href="index.php?page=logs&pg=<?= $i ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
