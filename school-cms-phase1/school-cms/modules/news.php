<?php
$ptype = ($p === 'announcement') ? 'announcement' : 'news';
$label = post_type_label($ptype);
$id = (int)($_GET['id'] ?? 0);

if ($id): // ----- หน้ารายละเอียด -----
  $post = row("SELECT * FROM posts WHERE id=? AND ptype=? AND status='published'
               AND (publish_at IS NULL OR publish_at<=NOW())", [$id, $ptype]);
  if (!$post) { echo '<div class="container section"><p class="empty">ไม่พบเนื้อหาที่ต้องการ</p></div>'; return; }
  q("UPDATE posts SET views=views+1 WHERE id=?", [$id]);
  $cat = $post['category_id'] ? val("SELECT name FROM categories WHERE id=?", [$post['category_id']]) : null;
  $gallery = json_decode($post['gallery'] ?? '[]', true) ?: [];
  $files   = json_decode($post['attachments'] ?? '[]', true) ?: [];
?>
<div class="container section">
  <article class="article">
    <h1><?= e($post['title']) ?></h1>
    <div class="meta">
      <span><?= e($label) ?><?= $cat ? ' · ' . e($cat) : '' ?></span>
      <span><?= thai_date($post['publish_at'] ?: $post['created_at']) ?></span>
      <span>เข้าชม <?= number_format($post['views'] + 1) ?> ครั้ง</span>
    </div>
    <?php if ($post['cover']): ?><p><img src="<?= e($post['cover']) ?>" alt="" style="border-radius:12px"></p><?php endif; ?>
    <div class="body"><?= $post['body'] /* ผ่าน clean_html ตอนบันทึกแล้ว */ ?></div>

    <?php if ($gallery): ?>
      <h3>ภาพประกอบ</h3>
      <div class="gallery">
        <?php foreach ($gallery as $g): ?>
          <a href="<?= e($g) ?>" target="_blank" rel="noopener"><img src="<?= e($g) ?>" alt=""></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <?php if ($files): ?>
      <h3>เอกสารแนบ</h3>
      <ul class="doc-list">
        <?php foreach ($files as $f): ?>
          <li><span class="doc-ic">📄</span>
            <div class="doc-main"><a href="<?= e($f['path']) ?>" target="_blank" rel="noopener"><?= e($f['name']) ?></a></div>
          </li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
    <p style="margin-top:26px"><a class="btn ghost" href="<?= url($ptype) ?>">← กลับหน้ารวม<?= e($label) ?></a></p>
  </article>
</div>
<?php return; endif;

// ----- หน้ารายการ -----
$kw  = trim($_GET['q'] ?? '');
$cat = (int)($_GET['cat'] ?? 0);
$where = "ptype=? AND status='published' AND (publish_at IS NULL OR publish_at<=NOW())";
$params = [$ptype];
if ($kw !== '')  { $where .= " AND title LIKE ?"; $params[] = "%$kw%"; }
if ($cat)        { $where .= " AND category_id=?"; $params[] = $cat; }

$total = (int)val("SELECT COUNT(*) FROM posts WHERE $where", $params);
[$pages, $offset, $cur] = paginate($total, 12, (int)($_GET['pg'] ?? 1));
$list = rows("SELECT * FROM posts WHERE $where ORDER BY pinned DESC, publish_at DESC, id DESC LIMIT 12 OFFSET $offset", $params);
$cats = rows("SELECT * FROM categories WHERE module=? ORDER BY sort", [$ptype]);
?>
<div class="container section">
  <div class="section-head"><h2><?= e($label) ?></h2></div>

  <form class="filter-bar" method="get">
    <input type="hidden" name="p" value="<?= e($ptype) ?>">
    <input type="search" name="q" placeholder="ค้นหา<?= e($label) ?>..." value="<?= e($kw) ?>">
    <select name="cat" onchange="this.form.submit()">
      <option value="">ทุกหมวดหมู่</option>
      <?php foreach ($cats as $c): ?>
        <option value="<?= $c['id'] ?>" <?= $cat == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">ค้นหา</button>
  </form>

  <?php if (!$list): ?><p class="empty">ไม่พบข้อมูล</p>
  <?php elseif ($ptype === 'news'): ?>
    <div class="grid-3">
      <?php foreach ($list as $n): ?>
      <article class="card">
        <?php if ($n['cover']): ?>
          <a href="<?= url('news', ['id' => $n['id']]) ?>"><img class="card-img" src="<?= e($n['cover']) ?>" alt=""></a>
        <?php endif; ?>
        <div class="card-body">
          <h3><a href="<?= url('news', ['id' => $n['id']]) ?>"><?= e($n['title']) ?></a></h3>
          <div class="card-meta">
            <?php if ($n['pinned']): ?><span class="badge pin">ปักหมุด</span><?php endif; ?>
            <?php if ($n['featured']): ?><span class="badge">ข่าวเด่น</span><?php endif; ?>
            <span><?= thai_date($n['publish_at'] ?: $n['created_at']) ?></span>
          </div>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <ul class="doc-list">
      <?php foreach ($list as $a): ?>
      <li><span class="doc-ic">📢</span>
        <div class="doc-main">
          <a href="<?= url('announcement', ['id' => $a['id']]) ?>"><?= e($a['title']) ?></a>
          <div class="doc-sub">
            <?php if ($a['pinned']): ?><span class="badge pin">ปักหมุด</span><?php endif; ?>
            <span><?= thai_date($a['publish_at'] ?: $a['created_at']) ?></span>
            <span>เข้าชม <?= number_format($a['views']) ?></span>
          </div>
        </div>
      </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <?php if ($pages > 1): ?>
  <nav class="pagination">
    <?php for ($i = 1; $i <= $pages; $i++):
      $qs = array_filter(['p'=>$ptype,'q'=>$kw,'cat'=>$cat ?: null,'pg'=>$i]); ?>
      <?php if ($i == $cur): ?><span class="cur"><?= $i ?></span>
      <?php else: ?><a href="index.php?<?= http_build_query($qs) ?>"><?= $i ?></a><?php endif; ?>
    <?php endfor; ?>
  </nav>
  <?php endif; ?>
</div>
