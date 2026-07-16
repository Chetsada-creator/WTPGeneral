<?php
$menus = rows("SELECT * FROM menus WHERE visible=1 ORDER BY sort, id");
$menu_tree = [];
foreach ($menus as $m) $menu_tree[$m['parent_id'] ?? 0][] = $m;

$page_title = $page_title ?? setting('site_title');
$theme  = setting('theme_color', '#1F3A6E');
$accent = setting('accent_color', '#C9A227');
$fbody  = setting('font_body', 'Sarabun');
$fdisp  = setting('font_display', 'Anuphan');

function render_menu(array $tree, int $parent = 0): void {
  if (empty($tree[$parent])) return;
  echo $parent === 0 ? '<ul class="nav-list">' : '<ul class="sub-menu">';
  foreach ($tree[$parent] as $m) {
    $has_child = !empty($tree[$m['id']]);
    echo '<li class="' . ($has_child ? 'has-child' : '') . '">';
    echo '<a href="' . e($m['url']) . '"' . ($m['new_tab'] ? ' target="_blank" rel="noopener"' : '') . '>'
       . e($m['title']) . ($has_child ? ' <span class="caret">▾</span>' : '') . '</a>';
    if ($has_child) render_menu($tree, $m['id']);
    echo '</li>';
  }
  echo '</ul>';
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> — <?= e(setting('school_name')) ?></title>
<meta name="description" content="<?= e(setting('seo_description')) ?>">
<meta name="keywords" content="<?= e(setting('seo_keywords')) ?>">
<?php if (setting('favicon')): ?><link rel="icon" href="<?= e(setting('favicon')) ?>"><?php endif; ?>
<link href="https://fonts.googleapis.com/css2?family=<?= rawurlencode($fbody) ?>:wght@400;500;600;700&family=<?= rawurlencode($fdisp) ?>:wght@500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/site.css">
<style>
:root{
  --theme: <?= e($theme) ?>;
  --accent: <?= e($accent) ?>;
  --font-body: '<?= e($fbody) ?>', sans-serif;
  --font-display: '<?= e($fdisp) ?>', sans-serif;
}
</style>
<script>
// ตั้งธีมก่อนวาดหน้าเพื่อไม่ให้กะพริบ
(function(){
  const t = localStorage.getItem('theme') ||
    (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
  document.documentElement.dataset.theme = t;
})();
</script>
</head>
<body>
<header class="site-header">
  <div class="container header-inner">
    <a class="brand" href="index.php">
      <?php if (setting('logo')): ?>
        <img src="<?= e(setting('logo')) ?>" alt="โลโก้" class="brand-logo">
      <?php else: ?>
        <span class="brand-mark" aria-hidden="true"><?= e(mb_substr(setting('school_name'), 0, 1)) ?></span>
      <?php endif; ?>
      <span class="brand-text">
        <strong><?= e(setting('site_title')) ?></strong>
        <small><?= e(setting('school_name')) ?></small>
      </span>
    </a>
    <button class="nav-toggle" aria-label="เปิดเมนู" onclick="document.body.classList.toggle('nav-open')">☰</button>
    <nav class="site-nav" aria-label="เมนูหลัก">
      <?php render_menu($menu_tree); ?>
    </nav>
    <button class="theme-toggle" aria-label="สลับโหมดสว่าง/มืด" onclick="toggleTheme()">
      <span class="ic-sun">☀</span><span class="ic-moon">☾</span>
    </button>
  </div>
</header>

<?php
// แถบประกาศล่าสุด (ticker) — เอกลักษณ์ของงานสารบรรณ
$ticker = module_enabled('announcement')
  ? rows("SELECT id,title FROM posts WHERE ptype='announcement' AND status='published'
          AND (publish_at IS NULL OR publish_at<=NOW()) ORDER BY pinned DESC, publish_at DESC, id DESC LIMIT 5")
  : [];
if ($ticker): ?>
<div class="ticker" role="region" aria-label="ประกาศล่าสุด">
  <div class="container ticker-inner">
    <span class="ticker-label">ประกาศ</span>
    <div class="ticker-track">
      <?php foreach ($ticker as $t): ?>
        <a href="<?= url('announcement', ['id' => $t['id']]) ?>"><?= e($t['title']) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</div>
<?php endif; ?>

<main class="site-main">
<?php render_blocks('global_top'); ?>
