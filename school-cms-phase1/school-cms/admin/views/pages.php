<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=pages";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $slug = strtolower(trim($_POST['slug'] ?? ''));
    $slug = preg_replace('/[^a-z0-9\-]/', '-', $slug);
    $slug = trim(preg_replace('/-+/', '-', $slug), '-');
    $data = [
      'title'  => trim($_POST['title'] ?? ''),
      'slug'   => $slug,
      'body'   => clean_html($_POST['body'] ?? ''),
      'status' => $_POST['status'] === 'draft' ? 'draft' : 'published',
    ];
    if ($data['title'] === '' || $data['slug'] === '') throw new Exception('กรุณากรอกชื่อหน้าและ slug');
    $dup = val("SELECT id FROM pages WHERE slug=? AND id<>?", [$slug, $id]);
    if ($dup) throw new Exception('slug นี้ถูกใช้แล้ว กรุณาตั้งใหม่');
    if ($id) { update('pages', $data, 'id=?', [$id]); flash('บันทึกเรียบร้อย'); }
    else     { insert('pages', $data); flash('เพิ่มหน้าเรียบร้อย'); }
    log_activity('page_save', 'หน้า: ' . $data['title']);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  delete_row('pages', (int)$_POST['id']);
  log_activity('page_delete', 'ลบหน้า #' . (int)$_POST['id']);
  flash('ลบเรียบร้อย'); redirect($back);
}

if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM pages WHERE id=?", [(int)$_GET['id']]) : null;
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?>หน้าเนื้อหา</h2>
  <form method="post" action="<?= $back ?>&act=save">
    <?= csrf_field() ?><input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div><label>ชื่อหน้า <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>Slug (ชื่อในลิงก์ ภาษาอังกฤษ) <span class="req">*</span></label>
        <input type="text" name="slug" required value="<?= e($item['slug'] ?? '') ?>" placeholder="เช่น about, history">
        <p class="hint">ลิงก์ของหน้า: index.php?p=page&amp;slug=<strong>ชื่อนี้</strong> — นำไปผูกกับเมนูได้</p></div>
      <div class="full"><label>เนื้อหา</label>
        <input type="hidden" name="body" id="body_html" value="<?= e($item['body'] ?? '') ?>">
        <div class="editor-area" data-editor="body_html" contenteditable="true"><?= $item['body'] ?? '' ?></div></div>
      <div><label>สถานะ</label>
        <select name="status">
          <option value="published" <?= ($item['status'] ?? '') === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
          <option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : '' ?>>ฉบับร่าง</option>
        </select></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

$list = rows("SELECT * FROM pages ORDER BY id DESC");
?>
<div class="toolbar"><span class="spacer"></span><a class="btn" href="<?= $back ?>&act=new">+ เพิ่มหน้า</a></div>
<table class="table">
  <thead><tr><th>ชื่อหน้า</th><th>Slug</th><th>สถานะ</th><th>ปรับปรุงล่าสุด</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td><a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a></td>
      <td><a href="../index.php?p=page&slug=<?= e($r['slug']) ?>" target="_blank"><?= e($r['slug']) ?> ↗</a></td>
      <td><span class="badge <?= $r['status'] === 'published' ? 'on' : 'off' ?>"><?= $r['status'] === 'published' ? 'เผยแพร่' : 'ฉบับร่าง' ?></span></td>
      <td><?= thai_date($r['updated_at']) ?></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบหน้านี้? เมนูที่ผูกอยู่จะใช้งานไม่ได้">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="btn sm danger">ลบ</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="5" style="text-align:center;color:var(--muted)">ยังไม่มีหน้าเนื้อหา</td></tr><?php endif; ?>
  </tbody>
</table>
