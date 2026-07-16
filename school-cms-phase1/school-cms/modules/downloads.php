<?php
$kw  = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$where = "visible=1"; $params = [];
if ($kw !== '') { $where .= " AND title LIKE ?"; $params[] = "%$kw%"; }
if ($cat)       { $where .= " AND category_id=?"; $params[] = $cat; }
$total = (int)val("SELECT COUNT(*) FROM downloads WHERE $where", $params);
[$pages, $offset, $cur] = paginate($total, 20, (int)($_GET['pg'] ?? 1));
$list = rows("SELECT d.*, c.name AS cat_name FROM downloads d
              LEFT JOIN categories c ON c.id=d.category_id
              WHERE $where ORDER BY d.created_at DESC LIMIT 20 OFFSET $offset", $params);
$cats = rows("SELECT * FROM categories WHERE module='downloads' ORDER BY sort");
$icons = ['pdf'=>'📕','doc'=>'📘','docx'=>'📘','xls'=>'📗','xlsx'=>'📗','ppt'=>'📙','pptx'=>'📙','zip'=>'🗜️','rar'=>'🗜️'];
?>
<div class="container section">
  <div class="section-head"><h2>ดาวน์โหลดเอกสาร</h2></div>

  <form class="filter-bar" method="get">
    <input type="hidden" name="p" value="downloads">
    <input type="search" name="q" placeholder="ค้นหาเอกสาร..." value="<?= e($kw) ?>">
    <select name="cat" onchange="this.form.submit()">
      <option value="">ทุกหมวดหมู่</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">ค้นหา</button>
  </form>

  <?php if (!$list): ?><p class="empty">ไม่พบเอกสาร</p>
  <?php else: ?>
  <ul class="doc-list">
    <?php foreach ($list as $d): ?>
    <li>
      <span class="doc-ic"><?= $icons[$d['ext']] ?? '📄' ?></span>
      <div class="doc-main">
        <a href="<?= url('dl', ['id' => $d['id']]) ?>"><?= e($d['title']) ?></a>
        <div class="doc-sub">
          <?php if ($d['cat_name']): ?><span class="badge"><?= e($d['cat_name']) ?></span><?php endif; ?>
          <span><?= strtoupper(e($d['ext'])) ?> · <?= human_size((int)$d['fsize']) ?></span>
          <span><?= thai_date($d['created_at']) ?></span>
          <span>ดาวน์โหลด <?= number_format($d['hits']) ?> ครั้ง</span>
        </div>
      </div>
      <span class="doc-act"><a class="btn" href="<?= url('dl', ['id' => $d['id']]) ?>">ดาวน์โหลด</a></span>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <nav class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++):
      $qs = array_filter(['p'=>'downloads','q'=>$kw,'cat'=>$cat ?: null,'pg'=>$i]); ?>
      <?php if ($i == $cur): ?><span class="cur"><?= $i ?></span>
      <?php else: ?><a href="index.php?<?= http_build_query($qs) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
