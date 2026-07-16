<?php
$u = current_user();
$nav = [
  ['dashboard','แดชบอร์ด','📊','viewer'],
  ['posts','ข่าว & ประกาศ','📰','staff'],
  ['downloads','ดาวน์โหลดเอกสาร','📁','staff'],
  ['personnel','บุคลากร','👥','staff'],
  ['events','ปฏิทินกิจกรรม','📅','staff'],
  ['pages','หน้าเนื้อหา','📄','editor'],
  ['blocks','บล็อกเนื้อหา','🧱','editor'],
  ['links','ลิงก์ (Quick Links)','🔗','editor'],
  ['categories','หมวดหมู่','🏷️','editor'],
  ['menus','เมนูเว็บไซต์','🧭','admin'],
  ['modules','จัดการโมดูล','🧩','admin'],
  ['settings','ตั้งค่าโรงเรียน','⚙️','admin'],
  ['users','ผู้ใช้งานระบบ','🔐','admin'],
  ['logs','บันทึกการใช้งาน','🕘','super_admin'],
];
$titles = array_column($nav, 1, 0);
?>
<!doctype html>
<html lang="th" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($titles[$page] ?? '') ?> — ระบบจัดการ</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;600;700&family=Anuphan:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
<style>:root{--theme:<?= e(setting('theme_color','#1F3A6E')) ?>;--accent:<?= e(setting('accent_color','#C9A227')) ?>}</style>
</head>
<body>
<div class="admin-wrap">
  <aside class="sidebar" id="sidebar">
    <div class="side-brand">
      <strong><?= e(setting('site_title')) ?></strong>
      <small>ระบบจัดการเว็บไซต์</small>
    </div>
    <nav class="side-nav">
      <?php foreach ($nav as [$key, $label, $icon, $min]): if (!can($min)) continue; ?>
        <a href="index.php?page=<?= $key ?>" class="<?= $page === $key ? 'active' : '' ?>">
          <span class="ni"><?= $icon ?></span><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    </nav>
    <div class="side-foot">
      <a href="../index.php" target="_blank">🌐 ดูหน้าเว็บไซต์</a>
      <a href="index.php?a=logout">⏻ ออกจากระบบ</a>
    </div>
  </aside>

  <div class="content">
    <header class="topbar">
      <button class="side-toggle" onclick="document.body.classList.toggle('side-open')" aria-label="เมนู">☰</button>
      <h1><?= e($titles[$page] ?? '') ?></h1>
      <div class="who">
        <strong><?= e($u['name']) ?></strong>
        <small><?= e(ROLE_NAMES[$u['role']] ?? $u['role']) ?></small>
      </div>
    </header>
    <main class="main">
    <?php if ($f = flash()): ?>
      <div class="alert <?= $f['type'] === 'success' ? 'success' : 'danger' ?>"><?= e($f['msg']) ?></div>
    <?php endif; ?>
