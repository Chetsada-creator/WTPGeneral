<?php
require_once __DIR__ . '/../includes/bootstrap.php';

$a = $_GET['a'] ?? '';

// ---------- Login / Logout ----------
if ($a === 'logout') { logout(); redirect('index.php?a=login'); }

if ($a === 'login' || !current_user()) {
  $err = '';
  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (attempt_login(trim($_POST['username'] ?? ''), $_POST['password'] ?? '')) {
      redirect('index.php');
    }
    $err = 'ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง';
  }
  require __DIR__ . '/login.php';
  exit;
}

// ---------- Routing + สิทธิ์ขั้นต่ำของแต่ละหน้า ----------
$routes = [
  'dashboard'  => 'viewer',
  'posts'      => 'staff',
  'downloads'  => 'staff',
  'personnel'  => 'staff',
  'events'     => 'staff',
  'pages'      => 'editor',
  'blocks'     => 'editor',
  'links'      => 'editor',
  'categories' => 'editor',
  'menus'      => 'admin',
  'settings'   => 'admin',
  'modules'    => 'admin',
  'users'      => 'admin',
  'logs'       => 'super_admin',
];

$page = $_GET['page'] ?? 'dashboard';
if (!isset($routes[$page])) $page = 'dashboard';
require_role($routes[$page]);

$view = __DIR__ . '/views/' . $page . '.php';
require __DIR__ . '/partials/header.php';
require $view;
require __DIR__ . '/partials/footer.php';
