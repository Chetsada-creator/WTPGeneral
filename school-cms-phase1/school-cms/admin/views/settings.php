<?php
$back = "index.php?page=settings";

$text_fields = [
  'school_name','school_name_en','site_title','theme_color','accent_color','font_body','font_display',
  'hero_title','hero_subtitle','address','phone','email','facebook','line','youtube',
  'footer_text','seo_description','seo_keywords','map_embed',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  csrf_check();
  try {
    foreach ($text_fields as $f) {
      if (!isset($_POST[$f])) continue;
      $v = $f === 'map_embed' ? clean_html($_POST[$f]) : trim($_POST[$f]);
      q("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)", [$f, $v]);
    }
    foreach (['logo' => 'logo', 'favicon' => 'favicon'] as $field => $key) {
      if ($up = upload_file($field, 'images', IMG_EXT)) {
        q("INSERT INTO settings (skey,svalue) VALUES (?,?) ON DUPLICATE KEY UPDATE svalue=VALUES(svalue)", [$key, $up]);
      }
    }
    log_activity('settings_save', 'บันทึกการตั้งค่าเว็บไซต์');
    flash('บันทึกการตั้งค่าเรียบร้อย');
  } catch (Exception $ex) { flash($ex->getMessage(), 'danger'); }
  redirect($back);
}
$s = fn($k) => e(setting($k));
$fonts = ['Sarabun','Anuphan','Kanit','Prompt','Noto Sans Thai','IBM Plex Sans Thai','Chakra Petch','Mitr','Bai Jamjuree'];
?>
<form method="post" action="<?= $back ?>" enctype="multipart/form-data">
<?= csrf_field() ?>

<div class="card">
  <h2>ข้อมูลโรงเรียน</h2>
  <div class="form-grid">
    <div><label>ชื่อโรงเรียน (ไทย)</label><input type="text" name="school_name" value="<?= $s('school_name') ?>"></div>
    <div><label>ชื่อโรงเรียน (อังกฤษ)</label><input type="text" name="school_name_en" value="<?= $s('school_name_en') ?>"></div>
    <div><label>ชื่อเว็บไซต์/หน่วยงาน</label><input type="text" name="site_title" value="<?= $s('site_title') ?>"></div>
    <div><label>โลโก้</label>
      <?php if (setting('logo')): ?><p><img src="../<?= $s('logo') ?>" style="height:48px"></p><?php endif; ?>
      <input type="file" name="logo" accept="image/*"></div>
    <div><label>Favicon (ไอคอนแท็บเบราว์เซอร์)</label>
      <?php if (setting('favicon')): ?><p><img src="../<?= $s('favicon') ?>" style="height:24px"></p><?php endif; ?>
      <input type="file" name="favicon" accept="image/*"></div>
  </div>
</div>

<div class="card">
  <h2>ธีมและฟอนต์</h2>
  <div class="form-grid">
    <div><label>สีหลักของโรงเรียน</label><input type="color" name="theme_color" value="<?= $s('theme_color') ?: '#1F3A6E' ?>"></div>
    <div><label>สีรอง (accent)</label><input type="color" name="accent_color" value="<?= $s('accent_color') ?: '#C9A227' ?>"></div>
    <div><label>ฟอนต์เนื้อหา</label>
      <select name="font_body"><?php foreach ($fonts as $f): ?><option <?= setting('font_body') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select></div>
    <div><label>ฟอนต์หัวข้อ</label>
      <select name="font_display"><?php foreach ($fonts as $f): ?><option <?= setting('font_display') === $f ? 'selected' : '' ?>><?= $f ?></option><?php endforeach; ?></select></div>
  </div>
</div>

<div class="card">
  <h2>ส่วนหัวหน้าแรก (Hero)</h2>
  <div class="form-grid">
    <div><label>หัวข้อใหญ่</label><input type="text" name="hero_title" value="<?= $s('hero_title') ?>"></div>
    <div><label>ข้อความรอง</label><input type="text" name="hero_subtitle" value="<?= $s('hero_subtitle') ?>"></div>
  </div>
</div>

<div class="card">
  <h2>ข้อมูลติดต่อ & โซเชียล</h2>
  <div class="form-grid">
    <div class="full"><label>ที่อยู่</label><input type="text" name="address" value="<?= $s('address') ?>"></div>
    <div><label>โทรศัพท์</label><input type="text" name="phone" value="<?= $s('phone') ?>"></div>
    <div><label>อีเมล</label><input type="text" name="email" value="<?= $s('email') ?>"></div>
    <div><label>Facebook (URL)</label><input type="text" name="facebook" value="<?= $s('facebook') ?>"></div>
    <div><label>LINE (URL)</label><input type="text" name="line" value="<?= $s('line') ?>"></div>
    <div><label>YouTube (URL)</label><input type="text" name="youtube" value="<?= $s('youtube') ?>"></div>
    <div class="full"><label>โค้ดฝังแผนที่ Google Maps (iframe)</label>
      <textarea name="map_embed" style="min-height:80px;font-family:monospace"><?= $s('map_embed') ?></textarea>
      <p class="hint">Google Maps → แชร์ → ฝังแผนที่ → คัดลอก HTML มาวาง</p></div>
  </div>
</div>

<div class="card">
  <h2>ท้ายเว็บ & SEO</h2>
  <div class="form-grid">
    <div class="full"><label>ข้อความท้ายเว็บ</label><input type="text" name="footer_text" value="<?= $s('footer_text') ?>"></div>
    <div class="full"><label>คำอธิบายเว็บไซต์ (SEO description)</label><textarea name="seo_description" style="min-height:60px"><?= $s('seo_description') ?></textarea></div>
    <div class="full"><label>คำค้น (SEO keywords)</label><input type="text" name="seo_keywords" value="<?= $s('seo_keywords') ?>"></div>
  </div>
</div>

<div class="form-actions"><button class="btn" style="padding:12px 34px">บันทึกการตั้งค่าทั้งหมด</button></div>
</form>
