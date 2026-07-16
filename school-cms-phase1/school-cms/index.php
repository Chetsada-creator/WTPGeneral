<?php
require_once __DIR__ . '/includes/bootstrap.php';
count_visit();

$p = $_GET['p'] ?? 'home';
$allowed = ['home','news','announcement','downloads','personnel','calendar','page','contact','dl'];
if (!in_array($p, $allowed)) $p = 'home';

// ตรวจว่าโมดูลเปิดใช้งานหรือไม่
$module_map = ['news'=>'news','announcement'=>'announcement','downloads'=>'downloads',
               'personnel'=>'personnel','calendar'=>'calendar','contact'=>'contact','dl'=>'downloads'];
if (isset($module_map[$p]) && !module_enabled($module_map[$p])) $p = 'home';

// ดาวน์โหลดไฟล์ (นับจำนวน)
if ($p === 'dl') {
  $f = row("SELECT * FROM downloads WHERE id=? AND visible=1", [(int)($_GET['id'] ?? 0)]);
  if (!$f || !file_exists(__DIR__ . '/' . $f['file'])) { http_response_code(404); exit('ไม่พบไฟล์'); }
  q("UPDATE downloads SET hits=hits+1 WHERE id=?", [$f['id']]);
  $path = __DIR__ . '/' . $f['file'];
  header('Content-Type: application/octet-stream');
  header('Content-Disposition: attachment; filename="' . rawurlencode($f['title']) . '.' . $f['ext'] . '"');
  header('Content-Length: ' . filesize($path));
  readfile($path);
  exit;
}

$view = __DIR__ . '/modules/' . ($p === 'announcement' ? 'news' : $p) . '.php';
require __DIR__ . '/templates/header.php';
require $view;
require __DIR__ . '/templates/footer.php';
