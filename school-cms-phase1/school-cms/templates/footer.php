<?php render_blocks('global_bottom'); ?>
</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div>
      <h3 class="f-title"><?= e(setting('site_title')) ?></h3>
      <p><?= e(setting('school_name')) ?><br><?= e(setting('address')) ?></p>
    </div>
    <div>
      <h3 class="f-title">ติดต่อ</h3>
      <p>
        <?php if (setting('phone')): ?>โทร <?= e(setting('phone')) ?><br><?php endif; ?>
        <?php if (setting('email')): ?>อีเมล <?= e(setting('email')) ?><?php endif; ?>
      </p>
      <p class="f-social">
        <?php if (setting('facebook')): ?><a href="<?= e(setting('facebook')) ?>" target="_blank" rel="noopener">Facebook</a><?php endif; ?>
        <?php if (setting('line')): ?><a href="<?= e(setting('line')) ?>" target="_blank" rel="noopener">LINE</a><?php endif; ?>
        <?php if (setting('youtube')): ?><a href="<?= e(setting('youtube')) ?>" target="_blank" rel="noopener">YouTube</a><?php endif; ?>
      </p>
    </div>
    <div>
      <h3 class="f-title">ลิงก์ด่วน</h3>
      <ul class="f-links">
        <?php foreach (rows("SELECT * FROM links WHERE visible=1 ORDER BY sort, id LIMIT 6") as $l): ?>
          <li><a href="<?= e($l['url']) ?>"<?= $l['new_tab'] ? ' target="_blank" rel="noopener"' : '' ?>><?= e($l['title']) ?></a></li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
  <div class="footer-bar">
    <div class="container">
      <span><?= e(setting('footer_text')) ?></span>
      <span>© <?= date('Y') + 543 ?> <?= e(setting('school_name')) ?></span>
    </div>
  </div>
</footer>

<script src="assets/js/site.js"></script>
</body>
</html>
