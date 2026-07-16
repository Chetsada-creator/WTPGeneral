<!doctype html>
<html lang="th" data-theme="light">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>เข้าสู่ระบบจัดการ — <?= e(setting('school_name')) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&family=Anuphan:wght@600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
<style>:root{--theme:<?= e(setting('theme_color','#1F3A6E')) ?>;--accent:<?= e(setting('accent_color','#C9A227')) ?>}</style>
</head>
<body class="login-body">
<div class="login-card">
  <h1><?= e(setting('site_title')) ?></h1>
  <p class="sub">ระบบจัดการเว็บไซต์ · <?= e(setting('school_name')) ?></p>
  <?php if (!empty($err)): ?><div class="alert danger"><?= e($err) ?></div><?php endif; ?>
  <form method="post" action="index.php?a=login">
    <label>ชื่อผู้ใช้</label>
    <input name="username" required autofocus autocomplete="username">
    <label>รหัสผ่าน</label>
    <input type="password" name="password" required autocomplete="current-password">
    <button class="btn wide" type="submit">เข้าสู่ระบบ</button>
  </form>
  <p class="sub" style="text-align:center;margin-top:18px"><a href="../index.php">← กลับหน้าเว็บไซต์</a></p>
</div>
</body>
</html>
