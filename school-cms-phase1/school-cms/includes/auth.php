<?php
const ROLE_LEVELS = ['viewer' => 1, 'staff' => 2, 'editor' => 3, 'admin' => 4, 'super_admin' => 5];
const ROLE_NAMES  = [
  'super_admin' => 'ผู้ดูแลระบบสูงสุด',
  'admin'       => 'ผู้ดูแลระบบ',
  'editor'      => 'ผู้แก้ไขเนื้อหา',
  'staff'       => 'เจ้าหน้าที่',
  'viewer'      => 'ผู้ชม',
];

function current_user(): ?array { return $_SESSION['user'] ?? null; }

function role_level(): int {
  $u = current_user();
  return $u ? (ROLE_LEVELS[$u['role']] ?? 0) : 0;
}

// ตรวจสิทธิ์ขั้นต่ำ เช่น can('editor')
function can(string $min_role): bool {
  return role_level() >= (ROLE_LEVELS[$min_role] ?? 99);
}

function require_login(): void {
  if (!current_user()) redirect('index.php?a=login');
}

function require_role(string $min_role): void {
  require_login();
  if (!can($min_role)) { http_response_code(403); exit('คุณไม่มีสิทธิ์เข้าถึงส่วนนี้'); }
}

function attempt_login(string $username, string $password): bool {
  $u = row("SELECT * FROM users WHERE username=? AND active=1", [$username]);
  if (!$u || !password_verify($password, $u['password_hash'])) return false;
  session_regenerate_id(true);
  unset($u['password_hash']);
  $_SESSION['user'] = $u;
  q("UPDATE users SET last_login=NOW() WHERE id=?", [$u['id']]);
  log_activity('login', 'เข้าสู่ระบบ');
  return true;
}

function logout(): void {
  log_activity('logout', 'ออกจากระบบ');
  $_SESSION = [];
  session_destroy();
}
