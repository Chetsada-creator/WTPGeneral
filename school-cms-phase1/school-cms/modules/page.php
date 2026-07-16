<?php
$slug = $_GET['slug'] ?? '';
$page = row("SELECT * FROM pages WHERE slug=? AND status='published'", [$slug]);
?>
<div class="container section">
  <?php if (!$page): ?>
    <p class="empty">ไม่พบหน้าที่ต้องการ</p>
  <?php else: ?>
  <article class="article">
    <h1><?= e($page['title']) ?></h1>
    <div class="meta"><span>ปรับปรุงล่าสุด <?= thai_date($page['updated_at']) ?></span></div>
    <div class="body"><?= $page['body'] ?></div>
  </article>
  <?php endif; ?>
</div>
