<?php
mb_internal_encoding('UTF-8');
date_default_timezone_set('Asia/Bangkok');
session_start();

if (!file_exists(__DIR__ . '/../config.php')) {
  // ยังไม่ได้ติดตั้ง — ส่งไปหน้า install
  $self = basename($_SERVER['SCRIPT_NAME'] ?? '');
  if ($self !== 'install.php') { header('Location: install.php'); exit; }
  return;
}

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';
