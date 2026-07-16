<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=events";
$type_labels = ['activity'=>'กิจกรรม','meeting'=>'ประชุม','holiday'=>'วันหยุด','important'=>'งานสำคัญ'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $data = [
      'title'      => trim($_POST['title'] ?? ''),
      'etype'      => isset($type_labels[$_POST['etype'] ?? '']) ? $_POST['etype'] : 'activity',
      'start_date' => $_POST['start_date'] ?? '',
      'end_date'   => $_POST['end_date'] ?: null,
      'location'   => trim($_POST['location'] ?? ''),
      'detail'     => trim($_POST['detail'] ?? ''),
    ];
    if ($data['title'] === '' || $data['start_date'] === '') throw new Exception('กรุณากรอกชื่อกิจกรรมและวันที่เริ่ม');
    if ($data['end_date'] && $data['end_date'] < $data['start_date']) throw new Exception('วันสิ้นสุดต้องไม่ก่อนวันเริ่ม');
    if ($id) { update('events', $data, 'id=?', [$id]); flash('บันทึกเรียบร้อย'); }
    else     { insert('events', $data); flash('เพิ่มกิจกรรมเรียบร้อย'); }
    log_activity('event_save', 'กิจกรรม: ' . $data['title']);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  delete_row('events', (int)$_POST['id']);
  log_activity('event_delete', 'ลบกิจกรรม #' . (int)$_POST['id']);
  flash('ลบเรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM events WHERE id=?", [(int)$_GET['id']]) : null;
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>กิจกรรม</h2>
  <form method="post" action="<?= $back ?>&act=save">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div class="full"><label>ชื่อกิจกรรม <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>ประเภท</label>
        <select name="etype">
          <?php foreach ($type_labels as $k => $v): ?>
            <option value="<?= $k ?>" <?= ($item['etype'] ?? '') === $k ? 'selected' : '' ?>><?= $v ?></option>
          <?php endforeach; ?>
        </select></div>
      <div><label>สถานที่</label>
        <input type="text" name="location" value="<?= e($item['location'] ?? '') ?>"></div>
      <div><label>วันที่เริ่ม <span class="req">*</span></label>
        <input type="date" name="start_date" required value="<?= e($item['start_date'] ?? '') ?>"></div>
      <div><label>วันที่สิ้นสุด (เว้นว่างหากวันเดียว)</label>
        <input type="date" name="end_date" value="<?= e($item['end_date'] ?? '') ?>"></div>
      <div class="full"><label>รายละเอียด</label>
        <textarea name="detail"><?= e($item['detail'] ?? '') ?></textarea></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$list = rows("SELECT * FROM events ORDER BY start_date DESC LIMIT 200");
?>
<div class="toolbar">
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มกิจกรรม</a>
</div>
<table class="table">
  <thead><tr><th>กิจกรรม</th><th>ประเภท</th><th>วันที่</th><th>สถานที่</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td><a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
      <td><span class="badge"><?= $type_labels[$r['etype']] ?? '' ?></span></td>
      <td><?= thai_date($r['start_date']) ?><?= $r['end_date'] && $r['end_date'] !== $r['start_date'] ? ' – ' . thai_date($r['end_date']) : '' ?></td>
      <td><?= e($r['location']) ?></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <?php if (can('editor')): ?>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบกิจกรรมนี้?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="5" style="text-align:center;color:var(--muted)">ยังไม่มีกิจกรรม</td></tr><?php endif; ?>
  </tbody>
</table>
