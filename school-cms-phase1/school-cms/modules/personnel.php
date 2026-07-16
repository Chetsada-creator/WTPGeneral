<?php
$kw  = trim($_GET['q'] ?? '');
$dep = trim($_GET['dep'] ?? '');
$where = "visible=1"; $params = [];
if ($kw !== '')  { $where .= " AND (name LIKE ? OR position LIKE ?)"; $params[] = "%$kw%"; $params[] = "%$kw%"; }
if ($dep !== '') { $where .= " AND department=?"; $params[] = $dep; }
$list = rows("SELECT * FROM personnel WHERE $where ORDER BY sort, id", $params);
$deps = rows("SELECT DISTINCT department FROM personnel WHERE visible=1 AND department<>'' ORDER BY department");
?>
<div class="container section">
  <div class="section-head"><h2>บุคลากร</h2></div>

  <form class="filter-bar" method="get">
    <input type="hidden" name="p" value="personnel">
    <input type="search" name="q" placeholder="ค้นหาชื่อหรือตำแหน่ง..." value="<?= e($kw) ?>">
    <select name="dep" onchange="this.form.submit()">
      <option value="">ทุกฝ่ายงาน</option>
      <?php foreach ($deps as $d): ?>
        <option value="<?= e($d['department']) ?>" <?= $dep === $d['department'] ? 'selected' : '' ?>><?= e($d['department']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">ค้นหา</button>
  </form>

  <?php if (!$list): ?><p class="empty">ไม่พบบุคลากร</p>
  <?php else: ?>
  <div class="grid-4">
    <?php foreach ($list as $ps): ?>
    <div class="card person-card">
      <?php if ($ps['photo']): ?><img src="<?= e($ps['photo']) ?>" alt="<?= e($ps['name']) ?>">
      <?php else: ?><span class="person-ph">👤</span><?php endif; ?>
      <h3><?= e($ps['name']) ?></h3>
      <div class="pos"><?= e($ps['position']) ?></div>
      <?php if ($ps['department']): ?><div class="dep"><?= e($ps['department']) ?></div><?php endif; ?>
      <div class="ctc">
        <?php if ($ps['phone']): ?>โทร <?= e($ps['phone']) ?><br><?php endif; ?>
        <?php if ($ps['email']): ?><?= e($ps['email']) ?><br><?php endif; ?>
        <?php if ($ps['link']): ?><a href="<?= e($ps['link']) ?>" target="_blank" rel="noopener">ช่องทางติดต่อ</a><?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
