<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=users";
$me = current_user();

// กติกา: admin จัดการได้ทุก role ยกเว้น super_admin / super_admin จัดการได้ทั้งหมด
$assignable = ['admin','editor','staff','viewer'];
if ($me['role'] === 'super_admin') array_unshift($assignable, 'super_admin');

function can_touch(array $target, array $me): bool {
  if ($me['role'] === 'super_admin') return true;
  return $target['role'] !== 'super_admin';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $username = trim($_POST['username'] ?? '');
    $name     = trim($_POST['name'] ?? '');
    $role     = in_array($_POST['role'] ?? '', $assignable) ? $_POST['role'] : 'staff';
    $pass     = $_POST['password'] ?? '';
    if ($username === '' || $name === '') throw new Exception('กรุณากรอกชื่อผู้ใช้และชื่อ-นามสกุล');
    $dup = val("SELECT id FROM users WHERE username=? AND id<>?", [$username, $id]);
    if ($dup) throw new Exception('ชื่อผู้ใช้นี้ถูกใช้แล้ว');

    $data = ['username' => $username, 'name' => $name, 'role' => $role,
             'active' => isset($_POST['active']) ? 1 : 0];

    if ($id) {
      $target = row("SELECT * FROM users WHERE id=?", [$id]);
      if (!$target || !can_touch($target, $me)) throw new Exception('ไม่มีสิทธิ์แก้ไขบัญชีนี้');
      if ($id == $me['id']) { $data['role'] = $me['role']; $data['active'] = 1; } // กันล็อกตัวเองออก/ลดสิทธิ์ตัวเอง
      if ($pass !== '') {
        if (strlen($pass) < 8) throw new Exception('รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร');
        $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
      }
      update('users', $data, 'id=?', [$id]);
      flash('บันทึกผู้ใช้เรียบร้อย');
    } else {
      if (strlen($pass) < 8) throw new Exception('รหัสผ่านต้องยาวอย่างน้อย 8 ตัวอักษร');
      $data['password_hash'] = password_hash($pass, PASSWORD_DEFAULT);
      insert('users', $data);
      flash('เพิ่มผู้ใช้เรียบร้อย');
    }
    log_activity('user_save', 'ผู้ใช้: ' . $username);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check();
  $id = (int)$_POST['id'];
  $target = row("SELECT * FROM users WHERE id=?", [$id]);
  if (!$target || !can_touch($target, $me) || $id == $me['id']) {
    flash('ไม่สามารถลบบัญชีนี้ได้', 'danger'); redirect($back);
  }
  q("UPDATE posts SET created_by=NULL WHERE created_by=?", [$id]);
  delete_row('users', $id);
  log_activity('user_delete', 'ลบผู้ใช้: ' . $target['username']);
  flash('ลบผู้ใช้เรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM users WHERE id=?", [(int)$_GET['id']]) : null;
  if ($item && !can_touch($item, $me)) { echo '<div class="alert danger">ไม่มีสิทธิ์แก้ไขบัญชีนี้</div>'; return; }
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>ผู้ใช้งาน</h2>
  <form method="post" action="<?= $back ?>&act=save">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div><label>ชื่อผู้ใช้ (username) <span class="req">*</span></label>
        <input type="text" name="username" required value="<?= e($item['username'] ?? '') ?>"></div>
      <div><label>ชื่อ-นามสกุล <span class="req">*</span></label>
        <input type="text" name="name" required value="<?= e($item['name'] ?? '') ?>"></div>
      <div><label>ระดับสิทธิ์</label>
        <select name="role" <?= $item && $item['id'] == $me['id'] ? 'disabled' : '' ?>>
          <?php foreach ($assignable as $r): ?>
            <option value="<?= $r ?>" <?= ($item['role'] ?? 'staff') === $r ? 'selected' : '' ?>><?= ROLE_NAMES[$r] ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">ผู้ชม: ดูแดชบอร์ด · เจ้าหน้าที่: จัดการเนื้อหา (ลบไม่ได้) · ผู้แก้ไข: จัดการ+ลบเนื้อหา · ผู้ดูแล: ตั้งค่า เมนู โมดูล ผู้ใช้</p></div>
      <div><label>รหัสผ่าน <?= $item ? '(เว้นว่างหากไม่เปลี่ยน)' : '<span class="req">*</span>' ?></label>
        <input type="password" name="password" minlength="8" <?= $item ? '' : 'required' ?> autocomplete="new-password"></div>
      <div class="check"><input type="checkbox" id="ac" name="active" <?= !isset($item['active']) || $item['active'] ? 'checked' : '' ?> <?= $item && $item['id'] == $me['id'] ? 'disabled' : '' ?>>
        <label for="ac" style="margin:0">บัญชีใช้งานได้</label></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$list = rows("SELECT * FROM users ORDER BY id");
?>
<div class="toolbar"><span class="spacer"></span><a class="btn" href="<?= $back ?>&act=new">+ เพิ่มผู้ใช้</a></div>
<table class="table">
  <thead><tr><th>ชื่อผู้ใช้</th><th>ชื่อ-นามสกุล</th><th>ระดับสิทธิ์</th><th>สถานะ</th><th>เข้าระบบล่าสุด</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td><?= e($r['username']) ?><?= $r['id'] == $me['id'] ? ' <span class="badge gold">คุณ</span>' : '' ?></td>
      <td><?= e($r['name']) ?></td>
      <td><span class="badge"><?= ROLE_NAMES[$r['role']] ?? $r['role'] ?></span></td>
      <td><span class="badge <?= $r['active'] ? 'on' : 'off' ?>"><?= $r['active'] ? 'ใช้งานได้' : 'ระงับ' ?></span></td>
      <td><?= thai_date($r['last_login'], true) ?></td>
      <td class="actions">
        <?php if (can_touch($r, $me)): ?>
          <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
          <?php if ($r['id'] != $me['id']): ?>
          <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบผู้ใช้ &quot;<?= e($r['username']) ?>&quot;?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
          </form>
          <?php endif; ?>
        <?php else: ?><span class="hint">—</span><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
