<?php
$back = "index.php?page=categories";
$mods = ['news'=>'ข่าวประชาสัมพันธ์','announcement'=>'ประกาศ','downloads'=>'ดาวน์โหลดเอกสาร','links'=>'ลิงก์'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $act = $_GET['act'] ?? '';
  if ($act === 'save') {
    $name = trim($_POST['name'] ?? '');
    $module = isset($mods[$_POST['module'] ?? '']) ? $_POST['module'] : 'news';
    $id = (int)($_POST['id'] ?? 0);
    if ($name === '') { flash('กรุณากรอกชื่อหมวดหมู่', 'danger'); redirect($back); }
    if ($id) update('categories', ['name' => $name], 'id=?', [$id]);
    else insert('categories', ['module' => $module, 'name' => $name,
                'sort' => (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM categories WHERE module=?", [$module])]);
    log_activity('category_save', "หมวดหมู่ ($module): $name");
    flash('บันทึกเรียบร้อย'); redirect($back);
  }
  if ($act === 'delete') {
    require_role('editor');
    $id = (int)$_POST['id'];
    // ปลดหมวดหมู่ออกจากข้อมูลที่ใช้อยู่ก่อนลบ
    q("UPDATE posts SET category_id=NULL WHERE category_id=?", [$id]);
    q("UPDATE downloads SET category_id=NULL WHERE category_id=?", [$id]);
    q("UPDATE links SET category_id=NULL WHERE category_id=?", [$id]);
    delete_row('categories', $id);
    log_activity('category_delete', "ลบหมวดหมู่ #$id");
    flash('ลบเรียบร้อย'); redirect($back);
  }
}
?>
<div class="two-col">
<?php foreach ($mods as $mkey => $mname):
  $cats = rows("SELECT * FROM categories WHERE module=? ORDER BY sort, id", [$mkey]); ?>
  <div class="card">
    <h2>หมวดหมู่: <?= $mname ?></h2>
    <table class="table" style="box-shadow:none">
      <tbody>
      <?php foreach ($cats as $c): ?>
        <tr>
          <td>
            <form method="post" action="<?= $back ?>&act=save" style="display:flex;gap:8px">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>">
              <input type="text" name="name" value="<?= e($c['name']) ?>">
              <button class="btn sm ghost">บันทึก</button>
            </form>
          </td>
          <td class="actions" style="width:70px">
            <form method="post" action="<?= $back ?>&act=delete" data-confirm="ลบหมวดหมู่นี้? ข้อมูลที่ใช้หมวดนี้จะกลายเป็น 'ไม่ระบุ'">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn sm danger">ลบ</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <tr>
        <td colspan="2">
          <form method="post" action="<?= $back ?>&act=save" style="display:flex;gap:8px">
            <?= csrf_field() ?><input type="hidden" name="module" value="<?= $mkey ?>">
            <input type="text" name="name" placeholder="เพิ่มหมวดหมู่ใหม่...">
            <button class="btn sm">+ เพิ่ม</button>
          </form>
        </td>
      </tr>
      </tbody>
    </table>
  </div>
<?php endforeach; ?>
</div>
