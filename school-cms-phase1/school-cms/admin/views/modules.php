<?php
$back = "index.php?page=modules";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $m = row("SELECT * FROM modules WHERE id=?", [$id]);
  if ($m) {
    update('modules', ['enabled' => $m['enabled'] ? 0 : 1], 'id=?', [$id]);
    log_activity('module_toggle', ($m['enabled'] ? 'ปิด' : 'เปิด') . 'โมดูล: ' . $m['name']);
    flash(($m['enabled'] ? 'ปิด' : 'เปิด') . 'ใช้งานโมดูล "' . $m['name'] . '" แล้ว');
  }
  redirect($back);
}

$list = rows("SELECT * FROM modules ORDER BY sort, id");
?>
<div class="card">
  <h2>เปิด/ปิดโมดูลของเว็บไซต์</h2>
  <p class="hint">โมดูลที่ปิดจะไม่แสดงบนหน้าเว็บ (ทั้งหน้าโมดูลและส่วนที่เกี่ยวข้องบนหน้าแรก) — ข้อมูลไม่ถูกลบ เปิดกลับมาได้ทุกเมื่อ · โมดูลใหม่ในอนาคต (สารบรรณอิเล็กทรอนิกส์ แจ้งซ่อม จองห้อง ฯลฯ) จะมาปรากฏที่หน้านี้</p>
</div>
<table class="table">
  <thead><tr><th>โมดูล</th><th>สถานะ</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $m): ?>
    <tr>
      <td><?= e($m['name']) ?> <span class="badge"><?= e($m['mkey']) ?></span></td>
      <td><span class="badge <?= $m['enabled'] ? 'on' : 'off' ?>"><?= $m['enabled'] ? 'เปิดใช้งาน' : 'ปิดอยู่' ?></span></td>
      <td class="actions">
        <form method="post" action="<?= $back ?>">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $m['id'] ?>">
          <button class="btn sm <?= $m['enabled'] ? 'danger' : '' ?>"><?= $m['enabled'] ? 'ปิดใช้งาน' : 'เปิดใช้งาน' ?></button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
