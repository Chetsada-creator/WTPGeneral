<?php
$act  = $_GET['act'] ?? 'list';
$type = in_array($_GET['type'] ?? '', ['news','announcement']) ? $_GET['type'] : 'news';
$back = "index.php?page=posts&type=$type";

// ---------- บันทึก ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  try {
    $data = [
      'ptype'       => $type,
      'title'       => trim($_POST['title'] ?? ''),
      'excerpt'     => trim($_POST['excerpt'] ?? ''),
      'body'        => clean_html($_POST['body'] ?? ''),
      'category_id' => (int)($_POST['category_id'] ?? 0) ?: null,
      'featured'    => isset($_POST['featured']) ? 1 : 0,
      'pinned'      => isset($_POST['pinned']) ? 1 : 0,
      'status'      => $_POST['status'] === 'published' ? 'published' : 'draft',
      'publish_at'  => $_POST['publish_at'] ?: date('Y-m-d H:i:s'),
    ];
    if ($data['title'] === '') throw new Exception('กรุณากรอกหัวข้อ');

    $old = $id ? row("SELECT * FROM posts WHERE id=?", [$id]) : null;

    if ($cover = upload_file('cover', 'images', IMG_EXT)) $data['cover'] = $cover;

    // แกลเลอรี (เพิ่มต่อจากเดิม)
    $gallery = $old ? (json_decode($old['gallery'] ?? '[]', true) ?: []) : [];
    if (!empty($_FILES['gallery']['name'][0])) {
      foreach ($_FILES['gallery']['name'] as $i => $nm) {
        $_FILES['_g'] = ['name'=>$nm,'type'=>$_FILES['gallery']['type'][$i],'tmp_name'=>$_FILES['gallery']['tmp_name'][$i],
                         'error'=>$_FILES['gallery']['error'][$i],'size'=>$_FILES['gallery']['size'][$i]];
        if ($g = upload_file('_g', 'images', IMG_EXT)) $gallery[] = $g;
      }
    }
    if (!empty($_POST['remove_gallery'])) {
      $gallery = array_values(array_diff($gallery, (array)$_POST['remove_gallery']));
    }
    $data['gallery'] = json_encode($gallery, JSON_UNESCAPED_UNICODE);

    // เอกสารแนบ
    $files = $old ? (json_decode($old['attachments'] ?? '[]', true) ?: []) : [];
    if (!empty($_FILES['attach']['name'][0])) {
      foreach ($_FILES['attach']['name'] as $i => $nm) {
        $_FILES['_a'] = ['name'=>$nm,'type'=>$_FILES['attach']['type'][$i],'tmp_name'=>$_FILES['attach']['tmp_name'][$i],
                         'error'=>$_FILES['attach']['error'][$i],'size'=>$_FILES['attach']['size'][$i]];
        if ($fp = upload_file('_a', 'files', FILE_EXT)) $files[] = ['name' => $nm, 'path' => $fp];
      }
    }
    if (!empty($_POST['remove_attach'])) {
      $files = array_values(array_filter($files, fn($f) => !in_array($f['path'], (array)$_POST['remove_attach'])));
    }
    $data['attachments'] = json_encode($files, JSON_UNESCAPED_UNICODE);

    if ($id) {
      update('posts', $data, 'id=?', [$id]);
      log_activity('post_update', 'แก้ไข' . post_type_label($type) . ': ' . $data['title']);
      flash('บันทึกการแก้ไขเรียบร้อย');
    } else {
      $data['created_by'] = current_user()['id'];
      insert('posts', $data);
      log_activity('post_create', 'เพิ่ม' . post_type_label($type) . ': ' . $data['title']);
      flash('เพิ่มเนื้อหาเรียบร้อย');
    }
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new')); }
}

// ---------- ลบ ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check(); require_role('editor');
  $t = val("SELECT title FROM posts WHERE id=?", [(int)$_POST['id']]);
  delete_row('posts', (int)$_POST['id']);
  log_activity('post_delete', 'ลบ: ' . $t);
  flash('ลบเรียบร้อย'); redirect($back);
}

// ---------- ฟอร์ม ----------
if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM posts WHERE id=?", [(int)$_GET['id']]) : null;
  $cats = rows("SELECT * FROM categories WHERE module=? ORDER BY sort", [$type]);
  $gallery = $item ? (json_decode($item['gallery'] ?? '[]', true) ?: []) : [];
  $files   = $item ? (json_decode($item['attachments'] ?? '[]', true) ?: []) : [];
?>
<div class="card">
  <h2><?= $item ? 'แก้ไข' : 'เพิ่ม' ?><?= post_type_label($type) ?></h2>
  <form method="post" action="<?= $back ?>&act=save" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <div class="form-grid">
      <div class="full">
        <label>หัวข้อ <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>">
      </div>
      <div class="full">
        <label>สรุปย่อ (แสดงในหน้ารวม)</label>
        <textarea name="excerpt" style="min-height:60px"><?= e($item['excerpt'] ?? '') ?></textarea>
      </div>
      <div class="full">
        <label>เนื้อหา</label>
        <input type="hidden" name="body" id="body_html" value="<?= e($item['body'] ?? '') ?>">
        <div class="editor-area" data-editor="body_html" contenteditable="true"><?= $item['body'] ?? '' ?></div>
      </div>
      <div>
        <label>หมวดหมู่</label>
        <select name="category_id">
          <option value="">— ไม่ระบุ —</option>
          <?php foreach ($cats as $c): ?>
            <option value="<?= $c['id'] ?>" <?= ($item['category_id'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <p class="hint">เพิ่มหมวดหมู่ได้ที่เมนู "หมวดหมู่"</p>
      </div>
      <div>
        <label>สถานะ / กำหนดเวลาเผยแพร่</label>
        <select name="status">
          <option value="draft" <?= ($item['status'] ?? '') === 'draft' ? 'selected' : '' ?>>ฉบับร่าง (ยังไม่แสดง)</option>
          <option value="published" <?= ($item['status'] ?? 'published') === 'published' ? 'selected' : '' ?>>เผยแพร่</option>
        </select>
        <input type="datetime-local" name="publish_at" style="margin-top:8px"
               value="<?= $item && $item['publish_at'] ? date('Y-m-d\TH:i', strtotime($item['publish_at'])) : '' ?>">
        <p class="hint">ตั้งเวลาในอนาคตเพื่อเผยแพร่ภายหลังอัตโนมัติ (เว้นว่าง = ทันที)</p>
      </div>
      <div>
        <label>รูปภาพปก</label>
        <?php if (!empty($item['cover'])): ?><p><img src="../<?= e($item['cover']) ?>" style="max-height:90px;border-radius:8px"></p><?php endif; ?>
        <input type="file" name="cover" accept="image/*">
      </div>
      <div>
        <div class="check"><input type="checkbox" id="fea" name="featured" <?= !empty($item['featured']) ? 'checked' : '' ?>><label for="fea" style="margin:0">⭐ ข่าวเด่น</label></div>
        <div class="check"><input type="checkbox" id="pin" name="pinned" <?= !empty($item['pinned']) ? 'checked' : '' ?>><label for="pin" style="margin:0">📌 ปักหมุดไว้บนสุด</label></div>
      </div>
      <div>
        <label>แกลเลอรีรูปภาพ (เลือกได้หลายไฟล์)</label>
        <input type="file" name="gallery[]" accept="image/*" multiple>
        <?php if ($gallery): ?><p class="hint">ติ๊กเพื่อลบรูปเดิม:</p>
          <?php foreach ($gallery as $g): ?>
            <div class="check"><input type="checkbox" name="remove_gallery[]" value="<?= e($g) ?>">
              <img src="../<?= e($g) ?>" style="height:44px;border-radius:6px"></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
      <div>
        <label>เอกสารแนบ (PDF, Word, Excel ฯลฯ)</label>
        <input type="file" name="attach[]" multiple>
        <?php if ($files): ?><p class="hint">ติ๊กเพื่อลบไฟล์เดิม:</p>
          <?php foreach ($files as $f): ?>
            <div class="check"><input type="checkbox" name="remove_attach[]" value="<?= e($f['path']) ?>"><?= e($f['name']) ?></div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="form-actions">
      <button class="btn" type="submit">บันทึก</button>
      <a class="btn ghost" href="<?= $back ?>">ยกเลิก</a>
    </div>
  </form>
</div>
<?php return; }

// ---------- รายการ ----------
$kw = trim($_GET['q'] ?? '');
$where = "ptype=?"; $params = [$type];
if ($kw !== '') { $where .= " AND title LIKE ?"; $params[] = "%$kw%"; }
$total = (int)val("SELECT COUNT(*) FROM posts WHERE $where", $params);
[$pages, $offset, $cur] = paginate($total, 15, (int)($_GET['pg'] ?? 1));
$list = rows("SELECT p.*, c.name AS cat FROM posts p LEFT JOIN categories c ON c.id=p.category_id
              WHERE $where ORDER BY p.id DESC LIMIT 15 OFFSET $offset", $params);
?>
<div class="toolbar">
  <a class="btn <?= $type === 'news' ? '' : 'ghost' ?>" href="index.php?page=posts&type=news">📰 ข่าวประชาสัมพันธ์</a>
  <a class="btn <?= $type === 'announcement' ? '' : 'ghost' ?>" href="index.php?page=posts&type=announcement">📢 ประกาศ</a>
  <span class="spacer"></span>
  <form method="get" style="display:flex;gap:8px">
    <input type="hidden" name="page" value="posts"><input type="hidden" name="type" value="<?= $type ?>">
    <input type="search" name="q" placeholder="ค้นหา..." value="<?= e($kw) ?>">
  </form>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่ม<?= post_type_label($type) ?></a>
</div>

<table class="table">
  <thead><tr><th>หัวข้อ</th><th>หมวดหมู่</th><th>สถานะ</th><th>เผยแพร่เมื่อ</th><th>เข้าชม</th><th></th></tr></thead>
  <tbody>
  <?php foreach ($list as $r): ?>
    <tr>
      <td>
        <?php if ($r['pinned']): ?><span class="badge gold">ปักหมุด</span> <?php endif; ?>
        <?php if ($r['featured']): ?><span class="badge">เด่น</span> <?php endif; ?>
        <a href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>"><?= e($r['title']) ?></a>
      </td>
      <td><?= e($r['cat'] ?? '-') ?></td>
      <td><span class="badge <?= $r['status'] === 'published' ? 'on' : 'off' ?>">
        <?= $r['status'] === 'published' ? 'เผยแพร่' : 'ฉบับร่าง' ?></span></td>
      <td><?= thai_date($r['publish_at'] ?: $r['created_at']) ?></td>
      <td><?= number_format($r['views']) ?></td>
      <td class="actions">
        <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $r['id'] ?>">แก้ไข</a>
        <?php if (can('editor')): ?>
        <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบ &quot;<?= e($r['title']) ?>&quot;?">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= $r['id'] ?>">
          <button class="btn sm danger">ลบ</button>
        </form>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$list): ?><tr><td colspan="6" style="text-align:center;color:var(--muted)">ยังไม่มีข้อมูล</td></tr><?php endif; ?>
  </tbody>
</table>

<?php if ($pages > 1): ?>
<div class="toolbar" style="margin-top:16px;justify-content:center">
  <?php for ($i = 1; $i <= $pages; $i++): ?>
    <a class="btn sm <?= $i == $cur ? '' : 'ghost' ?>" href="<?= $back ?>&q=<?= urlencode($kw) ?>&pg=<?= $i ?>"><?= $i ?></a>
  <?php endfor; ?>
</div>
<?php endif; ?>
