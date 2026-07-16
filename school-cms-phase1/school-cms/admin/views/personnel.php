<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=personnel";

// รับลำดับใหม่จากการลากจัดเรียง
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'sort') {
  csrf_check();
  foreach (explode(',', $_POST['order'] ?? '') as $i => $id) {
    if ((int)$id) q("UPDATE personnel SET sort=? WHERE id=?", [$i + 1, (int)$id]);
  }
  exit('ok');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $data = [
      'name'       => trim($_POST['name'] ?? ''),
      'position'   => trim($_POST['position'] ?? ''),
      'department' => trim($_POST['department'] ?? ''),
      'phone'      => trim($_POST['phone'] ?? ''),
      'email'      => trim($_POST['email'] ?? ''),
      'link'       => trim($_POST['link'] ?? ''),
      'visible'    => isset($_POST['visible']) ? 1 : 0,
    ];
    if ($data['name'] === '') throw new Exception('กรุณากรอกชื่อ-นามสกุล');
    if ($photo = upload_file('photo', 'personnel', IMG_EXT)) $data['photo'] = $photo;
    if ($id) { update('personnel', $data, 'id=?', [$id]); flash('บันทึกเรียบร้อย'); }
    else     { $data['sort'] = (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM personnel"); insert('personnel', $data); flash('เพิ่มบุคลากรเรียบร้อย'); }
    log_activity('personnel_save', 'บุคลากร: ' . $data['name']);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  delete_row('personnel', (int)$_POST['id']);
  log_activity('personnel_delete', 'ลบบุคลากร #' . (int)$_POST['id']);
  flash('ลบเรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM personnel WHERE id=?", [(int)$_GET['id']]) : null;
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>บุคลากร</h2>
  <form method="post" action="<?= $back ?>&act=save" enctype="multipart/form-data">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div><label>ชื่อ-นามสกุล <span class="req">*</span></label>
        <input type="text" name="name" required value="<?= e($item['name'] ?? '') ?>"></div>
      <div><label>ตำแหน่ง</label>
        <input type="text" name="position" value="<?= e($item['position'] ?? '') ?>" placeholder="เช่น หัวหน้าฝ่ายบริหารงานทั่วไป"></div>
      <div><label>ฝ่ายงาน/กลุ่มงาน</label>
        <input type="text" name="department" value="<?= e($item['department'] ?? '') ?>" placeholder="เช่น งานสารบรรณ"></div>
      <div><label>โทรศัพท์</label>
        <input type="text" name="phone" value="<?= e($item['phone'] ?? '') ?>"></div>
      <div><label>อีเมล</label>
        <input type="email" name="email" value="<?= e($item['email'] ?? '') ?>"></div>
      <div><label>ลิงก์ช่องทางติดต่ออื่น</label>
        <input type="url" name="link" value="<?= e($item['link'] ?? '') ?>" placeholder="https://..."></div>
      <div><label>รูปถ่าย</label>
        <?php if (!empty($item['photo'])): ?><p><img src="../<?= e($item['photo']) ?>" style="height:80px;border-radius:50%"></p><?php endif; ?>
        <input type="file" name="photo" accept="image/*"></div>
      <div class="check" style="align-self:end"><input type="checkbox" id="vis" name="visible" <?= !isset($item['visible']) || $item['visible'] ? 'checked' : '' ?>>
        <label for="vis" style="margin:0">แสดงบนหน้าเว็บไซต์</label></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$list = rows("SELECT * FROM personnel ORDER BY sort, id");
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
?>
<div class="toolbar">
  <span class="hint">ลากแถวเพื่อจัดลำดับการแสดงผล (บันทึกอัตโนมัติ)</span>
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มบุคลากร</a>
</div>
<table class="table">
  <thead><tr><th></th><th>รูป</th><th>ชื่อ-นามสกุล</th><th>ตำแหน่ง</th><th>ฝ่ายงาน</th><th>สถานะ</th><th></th></tr></thead>
  <tbody data-sortable="<?= $back ?>&act=sort" data-csrf="<?= $_SESSION['csrf'] ?>">
  <?php foreach ($list as $r): ?>
    <tr data-id="<?= $r['id'] ?>">
      <td class="drag-handle">⠿</td>
      <td><?php if ($r['photo']): ?><img class="thumb round" src="../<?= e($r['photo']) ?>"><?php else: ?><span class="badge">ไม่มีรูป</span><?php endif; ?></td>
      <td><a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['name']) ?></a></td>
      <td><?= e($r['position']) ?></td>
      <td><?= e($r['department']) ?></td>
      <td><span class="badge <?= $r['visible'] ? 'on' : 'off' ?>"><?= $r['visible'] ? 'แสดง' : 'ซ่อน' ?></span></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <?php if (can('editor')): ?>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบ &quot;<?= e($r['name']) ?>&quot;?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="7" style="text-align:center;color:var(--muted)">ยังไม่มีบุคลากร</td></tr><?php endif; ?>
  </tbody>
</table>
