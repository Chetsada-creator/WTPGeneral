<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=downloads";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $data = [
      'title'       => trim($_POST['title'] ?? ''),
      'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
      'visible'     => isset($_POST['visible']) ? 1 : 0,
    ];
    if ($data['title'] === '') throw new Exception('กรุณากรอกชื่อเอกสาร');
    if ($file = upload_file('file', 'files', FILE_EXT)) {
      $data['file']  = $file;
      $data['ext']   = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      $data['fsize'] = filesize(__DIR__ . '/../../' . $file);
    } elseif (!$id) {
      throw new Exception('กรุณาเลือกไฟล์');
    }
    if ($id) { update('downloads', $data, 'id=?', [$id]); flash('บันทึกเรียบร้อย'); }
    else     { insert('downloads', $data); flash('เพิ่มเอกสารเรียบร้อย'); }
    log_activity('download_save', 'เอกสาร: ' . $data['title']);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  delete_row('downloads', (int)$_POST['id']);
  log_activity('download_delete', 'ลบเอกสาร #' . (int)$_POST['id']);
  flash('ลบเรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM downloads WHERE id=?", [(int)$_GET['id']]) : null;
  $cats = rows("SELECT * FROM categories WHERE module='downloads' ORDER BY sort");
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>เอกสารดาวน์โหลด</h2>
  <form method="post" action="<?= $back ?>&act=save" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div class="full"><label>ชื่อเอกสาร <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>หมวดหมู่</label>
        <select name="category_id"><option value="">— ไม่ระบุ —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($item['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label>ไฟล์ <?= $item ? '(เลือกใหม่หากต้องการแทนที่)' : '<span class="req">*</span>' ?></label>
        <input type="file" name="file" <?= $item ? '' : 'required' ?>>
        <?php if ($item): ?><p class="hint">ไฟล์ปัจจุบัน: <?= strtoupper(e($item['ext'])) ?> · <?= human_size((int)$item['fsize']) ?></p><?php endif; ?>
        <p class="hint">รองรับ PDF, Word, Excel, PowerPoint, ZIP, รูปภาพ (ไม่เกิน 30 MB)</p></div>
      <div class="full check"><input type="checkbox" id="vis" name="visible" <?= !isset($item['visible']) || $item['visible'] ? 'checked' : '' ?>>
        <label for="vis" style="margin:0">แสดงบนหน้าเว็บไซต์</label></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$kw = trim($_GET['q'] ?? '');
$where = "1"; $params = [];
if ($kw !== '') { $where = "d.title LIKE ?"; $params[] = "%$kw%"; }
$list = rows("SELECT d.*, c.name AS cat FROM downloads d LEFT JOIN categories c ON c.id=d.category_id
              WHERE $where ORDER BY d.id DESC LIMIT 100", $params);
?>
<div class="toolbar">
  <form method="get" style="display:flex;gap:8px">
    <input type="hidden" name="page" value="downloads">
    <input type="search" name="q" placeholder="ค้นหาเอกสาร..." value="<?= e($kw) ?>">
  </form>
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มเอกสาร</a>
</div>
<table class="table">
  <thead><tr><th>ชื่อเอกสาร</th><th>หมวดหมู่</th><th>ชนิด/ขนาด</th><th>ดาวน์โหลด</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td><a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
      <td><?= e($r['cat'] ?? '-') ?></td>
      <td><?= strtoupper(e($r['ext'])) ?> · <?= human_size((int)$r['fsize']) ?></td>
      <td><?= number_format($r['hits']) ?> ครั้ง</td>
      <td><span class="badge <?= $r['visible'] ? 'on' : 'off' ?>"><?= $r['visible'] ? 'แสดง' : 'ซ่อน' ?></span></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <?php if (can('editor')): ?>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบเอกสารนี้?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="6" style="text-align:center;color:var(--muted)">ยังไม่มีเอกสาร</td></tr><?php endif; ?>
  </tbody>
</table>
