<?php
$act = $_GET['act'] ?? 'list';
$back = "index.php?page=blocks";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'sort') {
  csrf_check();
  foreach (explode(',', $_POST['order'] ?? '') as $i => $id) {
    if ((int)$id) q("UPDATE blocks SET sort=? WHERE id=?", [$i + 1, (int)$id]);
  }
  exit('ok');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'move') {
  // ย้ายบล็อกไปโซนอื่นแบบเร็วจากหน้ารายการ
  csrf_check();
  $zone = isset(BLOCK_ZONES[$_POST['zone'] ?? '']) ? $_POST['zone'] : 'home_top';
  update('blocks', ['zone' => $zone, 'sort' => (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM blocks WHERE zone=?", [$zone])], 'id=?', [(int)$_POST['id']]);
  flash('ย้ายตำแหน่งบล็อกเรียบร้อย'); redirect($back);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'save') {
  csrf_check();
  $id = (int)($_POST['id'] ?? 0);
  $btype = isset(BLOCK_TYPES[$_POST['btype'] ?? '']) ? $_POST['btype'] : 'notice';
  try {
    $title = trim($_POST['title'] ?? '');
    if ($title === '') throw new Exception('กรุณาตั้งชื่อบล็อก');

    // ประกอบ content JSON ตามชนิด
    $old = $id ? json_decode(val("SELECT content FROM blocks WHERE id=?", [$id]) ?: '{}', true) : [];
    switch ($btype) {
      case 'banner':
        $content = [
          'image'   => ($up = upload_file('image', 'images', IMG_EXT)) ? $up : ($old['image'] ?? ''),
          'url'     => trim($_POST['url'] ?? ''),
          'alt'     => trim($_POST['alt'] ?? ''),
          'new_tab' => isset($_POST['new_tab']) ? 1 : 0,
        ];
        if ($content['image'] === '') throw new Exception('กรุณาอัปโหลดรูปแบนเนอร์');
        break;
      case 'notice':
        $content = [
          'heading' => trim($_POST['heading'] ?? ''),
          'text'    => trim($_POST['text'] ?? ''),
          'color'   => in_array($_POST['color'] ?? '', ['accent','theme','red','green']) ? $_POST['color'] : 'accent',
          'url'     => trim($_POST['url'] ?? ''),
          'btn'     => trim($_POST['btn'] ?? ''),
        ];
        break;
      case 'buttons':
      case 'cards':
        $items = [];
        foreach (($_POST['it_title'] ?? []) as $i => $t) {
          $t = trim($t); if ($t === '') continue;
          $items[] = [
            'label' => $t, 'title' => $t,
            'url'   => trim($_POST['it_url'][$i] ?? ''),
            'icon'  => trim($_POST['it_icon'][$i] ?? ''),
            'text'  => trim($_POST['it_text'][$i] ?? ''),
            'new_tab' => 0,
          ];
        }
        if (!$items) throw new Exception('กรุณาเพิ่มรายการอย่างน้อย 1 รายการ');
        $content = ['items' => $items];
        break;
      default: // html
        $content = ['html' => clean_html($_POST['html'] ?? '')];
    }

    $data = [
      'title'   => $title,
      'btype'   => $btype,
      'content' => json_encode($content, JSON_UNESCAPED_UNICODE),
      'zone'    => isset(BLOCK_ZONES[$_POST['zone'] ?? '']) ? $_POST['zone'] : 'home_top',
      'visible' => isset($_POST['visible']) ? 1 : 0,
    ];
    if ($id) { update('blocks', $data, 'id=?', [$id]); flash('บันทึกบล็อกเรียบร้อย'); }
    else     { $data['sort'] = (int)val("SELECT COALESCE(MAX(sort),0)+1 FROM blocks WHERE zone=?", [$data['zone']]); insert('blocks', $data); flash('เพิ่มบล็อกเรียบร้อย'); }
    log_activity('block_save', 'บล็อก: ' . $title);
    redirect($back);
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); redirect($back . '&act=' . ($id ? "edit&id=$id" : 'new&btype=' . $btype)); }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $act === 'delete') {
  csrf_check();
  delete_row('blocks', (int)$_POST['id']);
  log_activity('block_delete', 'ลบบล็อก #' . (int)$_POST['id']);
  flash('ลบบล็อกเรียบร้อย'); redirect($back);
}

// ---------- ฟอร์ม ----------
if ($act === 'new' || $act === 'edit') {
  $item = $act === 'edit' ? row("SELECT * FROM blocks WHERE id=?", [(int)$_GET['id']]) : null;
  $btype = $item['btype'] ?? (isset(BLOCK_TYPES[$_GET['btype'] ?? '']) ? $_GET['btype'] : 'notice');
  $c = $item ? (json_decode($item['content'] ?? '{}', true) ?: []) : [];
?>
<div class="card">
  <h2><?= $item ? 'แก้ไขบล็อก' : 'เพิ่มบล็อก: ' . BLOCK_TYPES[$btype] ?></h2>
  <?php if (!$item): ?>
  <div class="toolbar">
    <?php foreach (BLOCK_TYPES as $k => $v): ?>
      <a class="btn sm <?= $k === $btype ? '' : 'ghost' ?>" href="<?= $back ?>&act=new&btype=<?= $k ?>"><?= $v ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <form method="post" action="<?= $back ?>&act=save" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $item['id'] ?? '' ?>">
    <input type="hidden" name="btype" value="<?= e($btype) ?>">
    <div class="form-grid">
      <div><label>ชื่อบล็อก (ใช้ในระบบจัดการ) <span class="req">*</span></label>
        <input type="text" name="title" required value="<?= e($item['title'] ?? '') ?>"></div>
      <div><label>ตำแหน่งที่แสดง</label>
        <select name="zone">
          <?php foreach (BLOCK_ZONES as $zk => $zv): ?>
            <option value="<?= $zk ?>" <?= ($item['zone'] ?? 'home_top') === $zk ? 'selected' : '' ?>><?= $zv ?></option>
          <?php endforeach; ?>
        </select></div>

      <?php if ($btype === 'banner'): ?>
        <div><label>รูปแบนเนอร์ <?= $item ? '(เลือกใหม่หากต้องการแทนที่)' : '<span class="req">*</span>' ?></label>
          <?php if (!empty($c['image'])): ?><p><img src="../<?= e($c['image']) ?>" style="max-height:80px;border-radius:8px"></p><?php endif; ?>
          <input type="file" name="image" accept="image/*" <?= $item ? '' : 'required' ?>>
          <p class="hint">แนะนำสัดส่วนกว้าง เช่น 1200×300</p></div>
        <div><label>ลิงก์เมื่อคลิก (เว้นว่างได้)</label>
          <input type="text" name="url" value="<?= e($c['url'] ?? '') ?>">
          <label>ข้อความอธิบายรูป (alt)</label>
          <input type="text" name="alt" value="<?= e($c['alt'] ?? '') ?>">
          <div class="check"><input type="checkbox" id="ntb" name="new_tab" <?= !empty($c['new_tab']) ? 'checked' : '' ?>><label for="ntb" style="margin:0">เปิดในแท็บใหม่</label></div></div>

      <?php elseif ($btype === 'notice'): ?>
        <div><label>หัวข้อ</label>
          <input type="text" name="heading" value="<?= e($c['heading'] ?? '') ?>"></div>
        <div><label>สีแถบข้าง</label>
          <select name="color">
            <?php foreach (['accent'=>'ทอง (accent)','theme'=>'สีธีมโรงเรียน','red'=>'แดง (ด่วน/สำคัญ)','green'=>'เขียว'] as $ck => $cv): ?>
              <option value="<?= $ck ?>" <?= ($c['color'] ?? 'accent') === $ck ? 'selected' : '' ?>><?= $cv ?></option>
            <?php endforeach; ?>
          </select></div>
        <div class="full"><label>ข้อความ</label>
          <textarea name="text"><?= e($c['text'] ?? '') ?></textarea></div>
        <div><label>ลิงก์ปุ่ม (เว้นว่างหากไม่ต้องการปุ่ม)</label>
          <input type="text" name="url" value="<?= e($c['url'] ?? '') ?>"></div>
        <div><label>ข้อความบนปุ่ม</label>
          <input type="text" name="btn" value="<?= e($c['btn'] ?? '') ?>" placeholder="ดูรายละเอียด"></div>

      <?php elseif ($btype === 'buttons' || $btype === 'cards'): ?>
        <div class="full">
          <label>รายการ<?= $btype === 'buttons' ? 'ปุ่ม' : 'การ์ด' ?></label>
          <table class="table" style="box-shadow:none" id="items">
            <thead><tr><th>ไอคอน (อีโมจิ)</th><th><?= $btype === 'buttons' ? 'ข้อความปุ่ม' : 'หัวข้อการ์ด' ?> *</th>
              <?php if ($btype === 'cards'): ?><th>คำอธิบายสั้น</th><?php endif; ?><th>URL</th><th></th></tr></thead>
            <tbody>
            <?php $its = $c['items'] ?? [[]]; foreach ($its as $it): ?>
              <tr>
                <td style="width:110px"><input type="text" name="it_icon[]" value="<?= e($it['icon'] ?? '') ?>" placeholder="📄"></td>
                <td><input type="text" name="it_title[]" value="<?= e($it['title'] ?? $it['label'] ?? '') ?>"></td>
                <?php if ($btype === 'cards'): ?><td><input type="text" name="it_text[]" value="<?= e($it['text'] ?? '') ?>"></td><?php endif; ?>
                <td><input type="text" name="it_url[]" value="<?= e($it['url'] ?? '') ?>" placeholder="https:// หรือ index.php?p=..."></td>
                <td><button type="button" class="btn sm danger" onclick="this.closest('tr').remove()">✕</button></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <button type="button" class="btn sm ghost" onclick="addRow()">+ เพิ่มรายการ</button>
        </div>
        <script>
        function addRow() {
          const tb = document.querySelector('#items tbody');
          const tr = tb.rows[0].cloneNode(true);
          tr.querySelectorAll('input').forEach(i => i.value = '');
          tb.appendChild(tr);
        }
        </script>

      <?php else: /* html */ ?>
        <div class="full"><label>โค้ด HTML</label>
          <textarea name="html" style="min-height:200px;font-family:monospace"><?= e($c['html'] ?? '') ?></textarea>
          <p class="hint">รองรับแท็กพื้นฐานและ iframe (เช่นฝังวิดีโอ) — สคริปต์จะถูกตัดออกเพื่อความปลอดภัย</p></div>
      <?php endif; ?>

      <div class="check full"><input type="checkbox" id="vis" name="visible" <?= !isset($item['visible']) || $item['visible'] ? 'checked' : '' ?>>
        <label for="vis" style="margin:0">แสดงบนหน้าเว็บไซต์</label></div>
    </div>
    <div class="form-actions"><button class="btn">บันทึก</button><a class="btn ghost" href="<?= $back ?>">ยกเลิก</a></div>
  </form>
</div>
<?php return; }

// ---------- รายการ (จัดกลุ่มตามโซน ลากจัดลำดับได้ในโซน) ----------
if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
?>
<div class="toolbar">
  <span class="hint">บล็อกจะแสดงบนเว็บไซต์ตามโซนและลำดับ — ลากแถวเพื่อจัดลำดับในโซน หรือเลือกโซนใหม่เพื่อย้าย</span>
  <span class="spacer"></span>
  <a class="btn" href="<?= $back ?>&act=new">+ เพิ่มบล็อก</a>
</div>

<?php foreach (BLOCK_ZONES as $zk => $zv):
  $list = rows("SELECT * FROM blocks WHERE zone=? ORDER BY sort, id", [$zk]); ?>
<div class="card">
  <h2>📍 <?= $zv ?></h2>
  <?php if (!$list): ?><p class="hint">ยังไม่มีบล็อกในตำแหน่งนี้</p>
  <?php else: ?>
  <table class="table" style="box-shadow:none">
    <tbody data-sortable="<?= $back ?>&act=sort" data-csrf="<?= $_SESSION['csrf'] ?>">
    <?php foreach ($list as $b): ?>
      <tr data-id="<?= $b['id'] ?>">
        <td class="drag-handle" style="width:30px">⠿</td>
        <td><a href="<?= $back ?>&act=edit&id=<?= $b['id'] ?>"><?= e($b['title']) ?></a></td>
        <td><span class="badge"><?= BLOCK_TYPES[$b['btype']] ?? '' ?></span></td>
        <td><span class="badge <?= $b['visible'] ? 'on' : 'off' ?>"><?= $b['visible'] ? 'แสดง' : 'ซ่อน' ?></span></td>
        <td style="width:220px">
          <form method="post" action="<?= $back ?>&act=move" style="display:flex;gap:6px">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>">
            <select name="zone" style="font-size:.82rem;padding:5px 8px">
              <?php foreach (BLOCK_ZONES as $zk2 => $zv2): ?>
                <option value="<?= $zk2 ?>" <?= $zk2 === $zk ? 'selected' : '' ?>><?= $zv2 ?></option>
              <?php endforeach; ?>
            </select>
            <button class="btn sm ghost">ย้าย</button>
          </form>
        </td>
        <td class="actions">
          <a class="btn sm ghost" href="<?= $back ?>&act=edit&id=<?= $b['id'] ?>">แก้ไข</a>
          <form method="post" action="<?= $back ?>&act=delete" data-confirm="ยืนยันลบบล็อกนี้?">
            <?= csrf_field() ?><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn sm danger">ลบ</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>
</div>
<?php endforeach; ?>
