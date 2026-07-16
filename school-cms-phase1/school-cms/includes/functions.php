<?php
function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function setting(string $key, string $default = ''): string {
  static $cache = null;
  if ($cache === null) {
    $cache = [];
    foreach (rows("SELECT skey, svalue FROM settings") as $r) $cache[$r['skey']] = $r['svalue'];
  }
  return $cache[$key] ?? $default;
}

function module_enabled(string $key): bool {
  static $cache = null;
  if ($cache === null) {
    $cache = [];
    foreach (rows("SELECT mkey, enabled FROM modules") as $r) $cache[$r['mkey']] = (bool)$r['enabled'];
  }
  return $cache[$key] ?? false;
}

function url(string $p = '', array $params = []): string {
  if ($p === '') return 'index.php';
  $params = array_merge(['p' => $p], $params);
  return 'index.php?' . http_build_query($params);
}

function redirect(string $to): void { header("Location: $to"); exit; }

function flash(?string $msg = null, string $type = 'success') {
  if ($msg !== null) { $_SESSION['flash'] = ['msg' => $msg, 'type' => $type]; return null; }
  $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

function csrf_field(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
  return '<input type="hidden" name="csrf" value="' . $_SESSION['csrf'] . '">';
}

function csrf_check(): void {
  if (($_POST['csrf'] ?? '') !== ($_SESSION['csrf'] ?? '-')) {
    http_response_code(419); exit('CSRF token ไม่ถูกต้อง กรุณาลองใหม่');
  }
}

// วันที่ภาษาไทย เช่น 13 กรกฎาคม 2569
function thai_date(?string $dt, bool $with_time = false): string {
  if (!$dt) return '-';
  $t = strtotime($dt);
  if (!$t) return '-';
  $m = ['','มกราคม','กุมภาพันธ์','มีนาคม','เมษายน','พฤษภาคม','มิถุนายน',
        'กรกฎาคม','สิงหาคม','กันยายน','ตุลาคม','พฤศจิกายน','ธันวาคม'];
  $s = date('j', $t) . ' ' . $m[(int)date('n', $t)] . ' ' . (date('Y', $t) + 543);
  if ($with_time) $s .= ' เวลา ' . date('H:i', $t) . ' น.';
  return $s;
}

function human_size(int $bytes): string {
  if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
  if ($bytes >= 1024) return round($bytes / 1024) . ' KB';
  return $bytes . ' B';
}

// อัปโหลดไฟล์อย่างปลอดภัย คืนค่า path หรือ '' ถ้าไม่มีไฟล์ / โยน Exception ถ้าไม่ผ่าน
function upload_file(string $field, string $subdir, array $allowed_ext): string {
  if (empty($_FILES[$field]['name']) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) return '';
  $f = $_FILES[$field];
  if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('อัปโหลดไฟล์ไม่สำเร็จ (รหัส ' . $f['error'] . ')');
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $allowed_ext)) throw new Exception('ไม่รองรับไฟล์นามสกุล .' . $ext);
  if ($f['size'] > 30 * 1048576) throw new Exception('ไฟล์ใหญ่เกิน 30 MB');
  $dir = __DIR__ . '/../uploads/' . $subdir;
  if (!is_dir($dir)) mkdir($dir, 0755, true);
  $name = date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
  if (!move_uploaded_file($f['tmp_name'], "$dir/$name")) throw new Exception('บันทึกไฟล์ไม่สำเร็จ');
  return "uploads/$subdir/$name";
}

const IMG_EXT  = ['jpg','jpeg','png','gif','webp','svg'];
const FILE_EXT = ['pdf','doc','docx','xls','xlsx','ppt','pptx','zip','rar','jpg','jpeg','png'];

function log_activity(string $action, string $detail = ''): void {
  $uid = $_SESSION['user']['id'] ?? null;
  insert('activity_log', ['user_id' => $uid, 'action' => $action, 'detail' => mb_substr($detail, 0, 490)]);
}

// นับผู้เข้าชมรายวัน (นับ session ละครั้ง)
function count_visit(): void {
  if (!empty($_SESSION['visited'])) return;
  $_SESSION['visited'] = 1;
  q("INSERT INTO visits (vdate, hits) VALUES (CURDATE(), 1)
     ON DUPLICATE KEY UPDATE hits = hits + 1");
}

// ทำความสะอาด HTML จาก editor (อนุญาตแท็กพื้นฐาน)
function clean_html(string $html): string {
  $allowed = '<p><br><b><strong><i><em><u><s><ul><ol><li><a><img><h2><h3><h4>'
           . '<blockquote><table><thead><tbody><tr><th><td><hr><figure><figcaption><iframe><span><div>';
  $html = strip_tags($html, $allowed);
  // ตัด event handler และ javascript: ออก
  $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
  $html = preg_replace('/(href|src)\s*=\s*(["\']?)\s*javascript:[^"\'>\s]*/i', '$1=$2#', $html);
  return $html;
}

function post_type_label(string $t): string {
  return $t === 'announcement' ? 'ประกาศ' : 'ข่าวประชาสัมพันธ์';
}

// ---------- Dynamic Content Blocks ----------
const BLOCK_ZONES = [
  'global_top'    => 'บนสุดทุกหน้า (ใต้แถบประกาศ)',
  'home_top'      => 'หน้าแรก — ส่วนบน (ใต้ลิงก์ด่วน)',
  'home_middle'   => 'หน้าแรก — ส่วนกลาง (หลังข่าว)',
  'home_bottom'   => 'หน้าแรก — ส่วนท้าย',
  'global_bottom' => 'ท้ายทุกหน้า (ก่อน footer)',
];
const BLOCK_TYPES = [
  'banner'  => 'แบนเนอร์รูปภาพ',
  'notice'  => 'กล่องประกาศ/ข้อความ',
  'buttons' => 'ปุ่มลัด',
  'cards'   => 'การ์ดข้อมูล',
  'html'    => 'HTML กำหนดเอง',
];

function render_blocks(string $zone): void {
  $blocks = rows("SELECT * FROM blocks WHERE zone=? AND visible=1 ORDER BY sort, id", [$zone]);
  if (!$blocks) return;
  echo '<div class="container block-zone">';
  foreach ($blocks as $b) {
    $c = json_decode($b['content'] ?? '{}', true) ?: [];
    switch ($b['btype']) {
      case 'banner':
        if (empty($c['image'])) break;
        $img = '<img src="' . e($c['image']) . '" alt="' . e($c['alt'] ?? $b['title']) . '">';
        echo '<div class="blk blk-banner">'
           . (!empty($c['url']) ? '<a href="' . e($c['url']) . '"' . (!empty($c['new_tab']) ? ' target="_blank" rel="noopener"' : '') . '>' . $img . '</a>' : $img)
           . '</div>';
        break;
      case 'notice':
        echo '<div class="blk blk-notice ' . e($c['color'] ?? 'accent') . '">';
        if (!empty($c['heading'])) echo '<strong>' . e($c['heading']) . '</strong>';
        if (!empty($c['text'])) echo '<p>' . nl2br(e($c['text'])) . '</p>';
        if (!empty($c['url'])) echo '<a class="btn" href="' . e($c['url']) . '">' . e($c['btn'] ?: 'ดูรายละเอียด') . '</a>';
        echo '</div>';
        break;
      case 'buttons':
        echo '<div class="blk blk-buttons">';
        foreach (($c['items'] ?? []) as $it) {
          if (empty($it['label']) || empty($it['url'])) continue;
          echo '<a class="blk-btn" href="' . e($it['url']) . '"' . (!empty($it['new_tab']) ? ' target="_blank" rel="noopener"' : '') . '>'
             . '<span>' . e($it['icon'] ?: '🔗') . '</span>' . e($it['label']) . '</a>';
        }
        echo '</div>';
        break;
      case 'cards':
        echo '<div class="blk blk-cards">';
        foreach (($c['items'] ?? []) as $it) {
          if (empty($it['title'])) continue;
          $inner = '<span class="ci">' . e($it['icon'] ?: 'ℹ️') . '</span><strong>' . e($it['title']) . '</strong>'
                 . (!empty($it['text']) ? '<p>' . e($it['text']) . '</p>' : '');
          echo !empty($it['url'])
            ? '<a class="blk-card" href="' . e($it['url']) . '">' . $inner . '</a>'
            : '<div class="blk-card">' . $inner . '</div>';
        }
        echo '</div>';
        break;
      case 'html':
        echo '<div class="blk">' . ($c['html'] ?? '') . '</div>'; // ผ่าน clean_html ตอนบันทึก
        break;
    }
  }
  echo '</div>';
}

function paginate(int $total, int $per_page, int $page): array {
  $pages = max(1, (int)ceil($total / $per_page));
  $page = min(max(1, $page), $pages);
  return [$pages, ($page - 1) * $per_page, $page];
}
