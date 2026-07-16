<section class="hero">
  <div class="container">
    <h1><?= e(setting('hero_title')) ?></h1>
    <p><?= e(setting('hero_subtitle')) ?></p>
  </div>
</section>

<?php if (module_enabled('links')):
  $qlinks = rows("SELECT * FROM links WHERE visible=1 ORDER BY sort, id LIMIT 8");
  if ($qlinks): ?>
<section class="quick-links container" aria-label="ลิงก์ด่วน">
  <div class="ql-grid">
    <?php foreach ($qlinks as $l): ?>
      <a class="ql-card" href="<?= e($l['url']) ?>"<?= $l['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>>
        <?php if ($l['image']): ?><img src="<?= e($l['image']) ?>" alt="">
        <?php else: ?><span class="ql-icon"><?= e($l['icon'] ?: '🔗') ?></span><?php endif; ?>
        <strong><?= e($l['title']) ?></strong>
      </a>
    <?php endforeach; ?>
  </div>
</section>
<?php endif; endif; ?>

<?php render_blocks('home_top'); ?>

<?php if (module_enabled('news')):
  $news = rows("SELECT * FROM posts WHERE ptype='news' AND status='published'
                AND (publish_at IS NULL OR publish_at<=NOW())
                ORDER BY pinned DESC, featured DESC, publish_at DESC, id DESC LIMIT 6"); ?>
<section class="section container">
  <div class="section-head">
    <h2>ข่าวประชาสัมพันธ์</h2>
    <a class="more-link" href="<?= url('news') ?>">ดูข่าวทั้งหมด →</a>
  </div>
  <?php if ($news): ?>
  <div class="grid-3">
    <?php foreach ($news as $n): ?>
    <article class="card">
      <?php if ($n['cover']): ?>
        <a href="<?= url('news', ['id' => $n['id']]) ?>"><img class="card-img" src="<?= e($n['cover']) ?>" alt=""></a>
      <?php endif; ?>
      <div class="card-body">
        <h3><a href="<?= url('news', ['id' => $n['id']]) ?>"><?= e($n['title']) ?></a></h3>
        <div class="card-meta">
          <?php if ($n['pinned']): ?><span class="badge pin">ปักหมุด</span><?php endif; ?>
          <span><?= thai_date($n['publish_at'] ?: $n['created_at']) ?></span>
          <span>👁 <?= number_format($n['views']) ?></span>
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="empty">ยังไม่มีข่าวประชาสัมพันธ์</p><?php endif; ?>
</section>
<?php endif; ?>

<?php render_blocks('home_middle'); ?>

<?php if (module_enabled('announcement')):
  $ann = rows("SELECT * FROM posts WHERE ptype='announcement' AND status='published'
               AND (publish_at IS NULL OR publish_at<=NOW())
               ORDER BY pinned DESC, publish_at DESC, id DESC LIMIT 5"); ?>
<section class="section container">
  <div class="section-head">
    <h2>ประกาศล่าสุด</h2>
    <a class="more-link" href="<?= url('announcement') ?>">ดูประกาศทั้งหมด →</a>
  </div>
  <?php if ($ann): ?>
  <ul class="doc-list">
    <?php foreach ($ann as $a): ?>
    <li>
      <span class="doc-ic">📢</span>
      <div class="doc-main">
        <a href="<?= url('announcement', ['id' => $a['id']]) ?>"><?= e($a['title']) ?></a>
        <div class="doc-sub">
          <?php if ($a['pinned']): ?><span class="badge pin">ปักหมุด</span><?php endif; ?>
          <span><?= thai_date($a['publish_at'] ?: $a['created_at']) ?></span>
        </div>
      </div>
    </li>
    <?php endforeach; ?>
  </ul>
  <?php else: ?><p class="empty">ยังไม่มีประกาศ</p><?php endif; ?>
</section>
<?php endif; ?>

<?php if (module_enabled('calendar')):
  $evs = rows("SELECT * FROM events WHERE start_date >= CURDATE() - INTERVAL 1 DAY ORDER BY start_date LIMIT 4"); ?>
<section class="section container">
  <div class="section-head">
    <h2>กิจกรรมที่กำลังจะมาถึง</h2>
    <a class="more-link" href="<?= url('calendar') ?>">ดูปฏิทินทั้งหมด →</a>
  </div>
  <?php if ($evs): ?>
  <div class="grid-4">
    <?php foreach ($evs as $ev): ?>
    <div class="card"><div class="card-body ev-item <?= e($ev['etype']) ?>">
      <span class="ev-date"><?= thai_date($ev['start_date']) ?></span>
      <h3><?= e($ev['title']) ?></h3>
      <?php if ($ev['location']): ?><div class="card-meta"><span>📍 <?= e($ev['location']) ?></span></div><?php endif; ?>
    </div></div>
    <?php endforeach; ?>
  </div>
  <?php else: ?><p class="empty">ยังไม่มีกิจกรรมเร็ว ๆ นี้</p><?php endif; ?>
</section>
<?php endif; ?>

<?php render_blocks('home_bottom'); ?>
