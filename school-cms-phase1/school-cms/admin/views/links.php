<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=links";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'sort') {
  csrf_check();
  foreach (explode(',', $_POST['order'] ?? '') as $i => $id) {
    if ((int)$id) q("UPDATE links SET sort=? WHERE id=?", [$i + 1, (int)$id]);
  }
  exit('ok');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $data = [
      'title'       => trim($_POST['title'] ?? ''),
      'url'         => trim($_POST['url'] ?? ''),
      'icon'        => trim($_POST['icon'] ?? ''),
      'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
      'new_tab'     => isset($_POST['new_tab']) ? 1 : 0,
      'visible'     => isset($_POST['visible']) ? 1 : 0,
    ];
    if ($data['title'] === '' || $data['url'] === '') throw new Exception('กรุณากรอกชื่อและ URL');
    if ($img = upload_file('image', 'images', IMG_EXT)) $data['image'] = $img;
    if ($id) { update('links', $data, 'id=?', [$id]); flash('บันทึกเรียบร้อย'); }
    else     { $data['sort'] = (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM links"); insert('links', $data); flash('เพิ่มลิงก์เรียบร้อย'); }
    log_activity('link_save', 'ลิงก์: ' . $data['title']);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  delete_row('links', (int)$_POST['id']);
  log_activity('link_delete', 'ลบลิงก์ #' . (int)$_POST['id']);
  flash('ลบเรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM links WHERE id=?", [(int)$_GET['id']]) : null;
  $cats = rows("SELECT * FROM categories WHERE module='links' ORDER BY sort");
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>ลิงก์</h2>
  <p class="hint">ลิงก์ 8 อันดับแรกจะแสดงเป็น "ลิงก์ด่วน" การ์ดแฟ้มบนหน้าแรก และ 6 อันดับแรกแสดงใน footer</p>
  <form method="post" action="<?= $back ?>&act=save" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div><label>ชื่อลิงก์ <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>URL <span class="req">*</span></label>
        <input type="text" name="url" required value="<?= e($item['url'] ?? '') ?>" placeholder="https://... หรือ index.php?p=..."></div>
      <div><label>ไอคอน (อีโมจิ)</label>
        <input type="text" name="icon" value="<?= e($item['icon'] ?? '') ?>" placeholder="เช่น 📄 🏫 📆"></div>
      <div><label>หรือรูปไอคอน (แทนอีโมจิ)</label>
        <?php if (!empty($item['image'])): ?><p><img src="../<?= e($item['image']) ?>" style="height:36px"></p><?php endif; ?>
        <input type="file" name="image" accept="image/*"></div>
      <div><label>หมวดหมู่</label>
        <select name="category_id"><option value="">— ไม่ระบุ —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($item['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select></div>
      <div>
        <div class="check"><input type="checkbox" id="nt" name="new_tab" <?= !isset($item['new_tab']) || !empty($item['new_tab']) ? 'checked' : '' ?>><label for="nt" style="margin:0">เปิดในแท็บใหม่</label></div>
        <div class="check"><input type="checkbox" id="vis" name="visible" <?= !isset($item['visible']) || $item['visible'] ? 'checked' : '' ?>><label for="vis" style="margin:0">แสดงบนหน้าเว็บไซต์</label></div>
      </div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$list = rows("SELECT l.*, c.name AS cat FROM links l LEFT JOIN categories c ON c.id=l.category_id ORDER BY l.sort, l.id");
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
?>
<div class="toolbar">
  <span class="hint">ลากแถวเพื่อจัดลำดับ (8 อันดับแรกขึ้นหน้าแรก)</span>
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มลิงก์</a>
</div>
<table class="table">
  <thead><tr><th></th><th>ลิงก์</th><th>URL</th><th>หมวดหมู่</th><th>สถานะ</th><th></th></tr></thead>
  <tbody data-sortable="<?= $back ?>&act=sort" data-csrf="<?= $_SESSION['csrf'] ?>">
  <?php foreach ($list as $r): ?>
    <tr data-id="<?= $r['id'] ?>">
      <td class="drag-handle">⠿</td>
      <td><?= e($r['icon']) ?> <a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
      <td style="max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?= e($r['url']) ?></td>
      <td><?= e($r['cat'] ?? '-') ?></td>
      <td><span class="badge <?= $r['visible'] ? 'on' : 'off' ?>"><?= $r['visible'] ? 'แสดง' : 'ซ่อน' ?></span></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบลิงก์นี้?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="6" style="text-align:center;color:var(--muted)">ยังไม่มีลิงก์</td></tr><?php endif; ?>
  </tbody>
</table>
