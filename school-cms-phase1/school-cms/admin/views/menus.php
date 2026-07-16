<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=menus";

// ปลายทางแบบสำเร็จรูป: โมดูลของระบบ
$module_targets = [
  'index.php'                => '🏠 หน้าแรก',
  'index.php?p=news'         => '📰 โมดูล: ข่าวประชาสัมพันธ์',
  'index.php?p=announcement' => '📢 โมดูล: ประกาศ',
  'index.php?p=downloads'    => '📁 โมดูล: ดาวน์โหลดเอกสาร',
  'index.php?p=personnel'    => '👥 โมดูล: บุคลากร',
  'index.php?p=calendar'     => '📅 โมดูล: ปฏิทินกิจกรรม',
  'index.php?p=contact'      => '📞 โมดูล: ติดต่อเรา',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'sort') {
  csrf_check();
  foreach (explode(',', $_POST['order'] ?? '') as $i => $id) {
    if ((int)$id) q("UPDATE menus SET sort=? WHERE id=?", [$i + 1, (int)$id]);
  }
  exit('ok');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $title = trim($_POST['title'] ?? '');
    if ($title === '') throw new Exception('กรุณากรอกชื่อเมนู');

    // สร้าง URL ตามชนิดปลายทางที่เลือก
    $ltype = $_POST['ltype'] ?? 'module';
    $is_external = 0;
    switch ($ltype) {
      case 'module':
        $url = isset($module_targets[$_POST['module_url'] ?? '']) ? $_POST['module_url'] : 'index.php';
        break;
      case 'page':
        $slug = val("SELECT slug FROM pages WHERE id=?", [(int)($_POST['page_id'] ?? 0)]);
        if (!$slug) throw new Exception('กรุณาเลือกหน้าเนื้อหา');
        $url = 'index.php?p=page&slug=' . $slug;
        break;
      case 'external':
        $url = trim($_POST['ext_url'] ?? '');
        if ($url === '') throw new Exception('กรุณากรอก URL');
        $is_external = 1;
        break;
      default: // heading — เมนูหัวข้อ ไม่ลิงก์ไปไหน (ใช้เป็นแม่ของเมนูย่อย)
        $url = '#';
    }

    $parent_id = (int)($_POST['parent_id'] ?? 0) ?: null;
    if ($id && $parent_id === $id) throw new Exception('เมนูเป็นแม่ของตัวเองไม่ได้');

    $data = [
      'title'       => $title,
      'url'         => $url,
      'is_external' => $is_external,
      'parent_id'   => $parent_id,
      'new_tab'     => isset($_POST['new_tab']) ? 1 : 0,
      'visible'     => isset($_POST['visible']) ? 1 : 0,
    ];
    if ($id) { update('menus', $data, 'id=?', [$id]); flash('บันทึกเมนูเรียบร้อย'); }
    else     { $data['sort'] = (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM menus"); insert('menus', $data); flash('เพิ่มเมนูเรียบร้อย'); }
    log_activity('menu_save', 'เมนู: ' . $title);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check();
  $id = (int)$_POST['id'];
  q("UPDATE menus SET parent_id=NULL WHERE parent_id=?", [$id]); // เมนูย่อยเลื่อนขึ้นเป็นเมนูหลัก
  delete_row('menus', $id);
  log_activity('menu_delete', "ลบเมนู #$id");
  flash('ลบเมนูเรียบร้อย (เมนูย่อยถูกเลื่อนขึ้นเป็นเมนูหลัก)'); redirect($back);
}

// ---------- ฟอร์ม ----------
if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM menus WHERE id=?", [(int)$_GET['id']]) : null;
  $parents = rows("SELECT * FROM menus WHERE parent_id IS NULL" . ($item ? " AND id<>" . (int)$item['id'] : '') . " ORDER BY sort");
  $pages_list = rows("SELECT id,title,slug FROM pages WHERE status='published' ORDER BY title");

  // เดาชนิดปลายทางปัจจุบันของเมนูที่แก้ไข
  $cur_type = 'module'; $cur_page = 0;
  if ($item) {
    if ($item['url'] === '#') $cur_type = 'heading';
    elseif ($item['is_external']) $cur_type = 'external';
    elseif (str_contains($item['url'], 'p=page&slug=')) {
      $cur_type = 'page';
      $cur_slug = substr($item['url'], strpos($item['url'], 'slug=') + 5);
      $cur_page = (int)val("SELECT id FROM pages WHERE slug=?", [$cur_slug]);
    }
  }
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>เมนู</h2>
  <form method="post" action="<?= $back ?>&act=save">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div><label>ชื่อเมนู <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>เป็นเมนูย่อยของ</label>
        <select name="parent_id">
          <option value="">— เมนูหลัก (ระดับบนสุด) —</option>
          <?php foreach ($parents as $pm): ?>
            <option value="<?= $pm['id'] ?>" <?= ($item['parent_id'] ?? 0) == $pm['id'] ? 'selected' : '' ?>><?= e($pm['title']) ?></option>
          <?php endforeach; ?>
        </select></div>

      <div class="full"><label>เชื่อมโยงไปที่</label>
        <select name="ltype" id="ltype" onchange="swapTarget()">
          <option value="module" <?= $cur_type === 'module' ? 'selected' : '' ?>>โมดูลของระบบ</option>
          <option value="page" <?= $cur_type === 'page' ? 'selected' : '' ?>>หน้าเนื้อหา</option>
          <option value="external" <?= $cur_type === 'external' ? 'selected' : '' ?>>ลิงก์ภายนอก (URL)</option>
          <option value="heading" <?= $cur_type === 'heading' ? 'selected' : '' ?>>หัวข้อเฉย ๆ (สำหรับรวมเมนูย่อย)</option>
        </select></div>

      <div class="full" data-target="module">
        <label>เลือกโมดูล</label>
        <select name="module_url">
          <?php foreach ($module_targets as $u => $lbl): ?>
            <option value="<?= e($u) ?>" <?= ($item['url'] ?? '') === $u ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select></div>

      <div class="full" data-target="page">
        <label>เลือกหน้าเนื้อหา</label>
        <select name="page_id">
          <?php foreach ($pages_list as $pg): ?>
            <option value="<?= $pg['id'] ?>" <?= $cur_page == $pg['id'] ? 'selected' : '' ?>><?= e($pg['title']) ?> (<?= e($pg['slug']) ?>)</option>
          <?php endforeach; ?>
          <?php if (!$pages_list): ?><option value="">— ยังไม่มีหน้าเนื้อหา สร้างได้ที่เมนู "หน้าเนื้อหา" —</option><?php endif; ?>
        </select></div>

      <div class="full" data-target="external">
        <label>URL ภายนอก</label>
        <input type="text" name="ext_url" value="<?= $cur_type === 'external' ? e($item['url']) : '' ?>" placeholder="https://..."></div>

      <div>
        <div class="check"><input type="checkbox" id="nt" name="new_tab" <?= !empty($item['new_tab']) ? 'checked' : '' ?>><label for="nt" style="margin:0">เปิดในแท็บใหม่</label></div>
        <div class="check"><input type="checkbox" id="vis" name="visible" <?= !isset($item['visible']) || $item['visible'] ? 'checked' : '' ?>><label for="vis" style="margin:0">แสดงเมนูนี้</label></div>
      </div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<script>
function swapTarget() {
  const t = document.getElementById('ltype').value;
  document.querySelectorAll('[data-target]').forEach(el => {
    el.style.display = el.dataset.target === t ? '' : 'none';
  });
}
swapTarget();
</script>
<?php return; }

// ---------- รายการ (เมนูหลัก + ย่อยเยื้องเข้า) ----------
$all = rows("SELECT * FROM menus ORDER BY sort, id");
$tree = [];
foreach ($all as $m) $tree[$m['parent_id'] ?? 0][] = $m;
$flat = [];
foreach ($tree[0] ?? [] as $m) {
  $flat[] = [$m, 0];
  foreach ($tree[$m['id']] ?? [] as $c) $flat[] = [$c, 1];
}
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
?>
<div class="toolbar">
  <span class="hint">ลากแถวเพื่อจัดลำดับภายในระดับเดียวกัน · เมนูย่อยแสดงเยื้องเข้า</span>
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มเมนู</a>
</div>
<table class="table">
  <thead><tr><th></th><th>เมนู</th><th>ปลายทาง</th><th>สถานะ</th><th></th></tr></thead>
  <tbody data-sortable="<?= $back ?>&act=sort" data-csrf="<?= $_SESSION['csrf'] ?>">
  <?php foreach ($flat as [$m, $depth]): ?>
    <tr data-id="<?= $m['id'] ?>">
      <td class="drag-handle">⠿</td>
      <td style="padding-left:<?= 14 + $depth * 28 ?>px">
        <?= $depth ? '↳ ' : '' ?><a href="<?= $back ?>&act=edit&id=<?= $m['id'] ?>"><?= e($m['title']) ?></a>
      </td>
      <td style="max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
        <?= $m['url'] === '#' ? '<span class="badge">หัวข้อ</span>' : ($m['is_external'] ? '🌐 ' : '') . e($m['url']) ?>
      </td>
      <td><span class="badge <?= $m['visible'] ? 'on' : 'off' ?>"><?= $m['visible'] ? 'แสดง' : 'ซ่อน' ?></span></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $m['id'] ?>">แก้ไข</a>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบเมนู &quot;<?= e($m['title']) ?>&quot;?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
