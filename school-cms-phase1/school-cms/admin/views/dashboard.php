<?php
$stats = [
  ['ข่าวประชาสัมพันธ์', (int)val("SELECT COUNT(*) FROM posts WHERE ptype='news'")],
  ['ประกาศ',            (int)val("SELECT COUNT(*) FROM posts WHERE ptype='announcement'")],
  ['หน้าเนื้อหา',        (int)val("SELECT COUNT(*) FROM pages")],
  ['ไฟล์ดาวน์โหลด',      (int)val("SELECT COUNT(*) FROM downloads")],
  ['บุคลากร',           (int)val("SELECT COUNT(*) FROM personnel")],
  ['ผู้ใช้งานระบบ',       (int)val("SELECT COUNT(*) FROM users")],
  ['ผู้เข้าชมทั้งหมด',     (int)val("SELECT COALESCE(SUM(hits),0) FROM visits")],
  ['ผู้เข้าชมวันนี้',      (int)val("SELECT COALESCE(hits,0) FROM visits WHERE vdate=CURDATE()") ?: 0],
];

// ผู้เข้าชม 14 วันล่าสุด
$days = [];
for ($i = 13; $i >= 0; $i--) $days[date('Y-m-d', strtotime("-$i day"))] = 0;
foreach (rows("SELECT vdate, hits FROM visits WHERE vdate >= CURDATE() - INTERVAL 13 DAY") as $r)
  $days[$r['vdate']] = (int)$r['hits'];
$max = max(1, max($days));

$recent_posts = rows("SELECT id,title,ptype,status,created_at FROM posts ORDER BY id DESC LIMIT 6");
$recent_logs  = rows("SELECT l.*, u.name FROM activity_log l LEFT JOIN users u ON u.id=l.user_id ORDER BY l.id DESC LIMIT 8");
?>
<div class="stat-grid">
  <?php foreach ($stats as [$lbl, $num]): ?>
    <div class="stat"><div class="num"><?= number_format($num) ?></div><div class="lbl"><?= e($lbl) ?></div></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <h2>ผู้เข้าชมเว็บไซต์ 14 วันล่าสุด</h2>
  <div class="chart-bars" style="margin-bottom:30px">
    <?php foreach ($days as $d => $h): ?>
      <div class="bar" style="height:<?= round($h / $max * 100) ?>%" title="<?= thai_date($d) ?> — <?= $h ?> ครั้ง">
        <b><?= $h ?: '' ?></b><span><?= date('j/n', strtotime($d)) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="two-col">
  <div class="card">
    <h2>เนื้อหาล่าสุด</h2>
    <ul class="recent">
      <?php foreach ($recent_posts as $r): ?>
      <li>
        <a href="index.php?page=posts&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a><br>
        <small><?= post_type_label($r['ptype']) ?> ·
          <?= $r['status'] === 'published' ? 'เผยแพร่แล้ว' : 'ฉบับร่าง' ?> · <?= thai_date($r['created_at']) ?></small>
      </li>
      <?php endforeach; ?>
      <?php if (!$recent_posts): ?><li><small>ยังไม่มีเนื้อหา</small></li><?php endif; ?>
    </ul>
  </div>
  <div class="card">
    <h2>กิจกรรมในระบบล่าสุด</h2>
    <ul class="recent">
      <?php foreach ($recent_logs as $l): ?>
      <li><?= e($l['name'] ?? 'ระบบ') ?> — <?= e($l['detail'] ?: $l['action']) ?><br>
        <small><?= thai_date($l['created_at'], true) ?></small></li>
      <?php endforeach; ?>
      <?php if (!$recent_logs): ?><li><small>ยังไม่มีบันทึก</small></li><?php endif; ?>
    </ul>
  </div>
</div>
