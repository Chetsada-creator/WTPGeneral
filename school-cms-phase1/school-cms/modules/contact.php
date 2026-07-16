<div class="container section">
  <div class="section-head"><h2>ติดต่อเรา</h2></div>
  <div class="contact-grid">
    <div class="card"><div class="card-body" style="gap:14px">
      <h3 style="margin:0"><?= e(setting('site_title')) ?></h3>
      <p style="margin:0"><?= e(setting('school_name')) ?><br><?= e(setting('address')) ?></p>
      <p style="margin:0">
        <?php if (setting('phone')): ?><strong>โทรศัพท์:</strong> <?= e(setting('phone')) ?><br><?php endif; ?>
        <?php if (setting('email')): ?><strong>อีเมล:</strong> <a href="mailto:<?= e(setting('email')) ?>"><?= e(setting('email')) ?></a><?php endif; ?>
      </p>
      <p class="f-social" style="margin:0">
        <?php if (setting('facebook')): ?><a class="btn ghost" href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener">Facebook</a> <?php endif; ?>
        <?php if (setting('line')): ?><a class="btn ghost" href="<?= e(setting('line')) ?>" target="_blank" rel="noopener">LINE</a> <?php endif; ?>
        <?php if (setting('youtube')): ?><a class="btn ghost" href="<?= e(setting('youtube')) ?>" target="_blank" rel="noopener">YouTube</a><?php endif; ?>
      </p>
    </div></div>
    <div class="map-embed">
      <?php if (setting('map_embed')): ?>
        <?= setting('map_embed') /* โค้ด iframe จาก Google Maps ที่ผู้ดูแลวางในระบบตั้งค่า */ ?>
      <?php else: ?>
        <div class="card"><div class="card-body"><p class="empty">ยังไม่ได้ตั้งค่าแผนที่ — เพิ่มโค้ดฝัง Google Maps ได้ที่ระบบตั้งค่า</p></div></div>
      <?php endif; ?>
    </div>
  </div>
</div>
