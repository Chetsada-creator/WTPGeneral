<?php
// ================= ตัวติดตั้งระบบ =================
mb_internal_encoding('UTF-8');
session_start();
$err = '';

if (file_exists(__DIR__ . '/config.php')) {
  exit('ระบบติดตั้งแล้ว — เพื่อความปลอดภัยกรุณาลบไฟล์ install.php ออกจากเซิร์ฟเวอร์ <a href="index.php">ไปหน้าแรก</a>');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $h = trim($_POST['db_host'] ?? 'localhost');
  $n = trim($_POST['db_name'] ?? '');
  $u = trim($_POST['db_user'] ?? '');
  $p = $_POST['db_pass'] ?? '';
  $admin_user = trim($_POST['admin_user'] ?? '');
  $admin_name = trim($_POST['admin_name'] ?? '');
  $admin_pass = $_POST['admin_pass'] ?? '';

  if ($n === '' || $u === '' || $admin_user === '' || strlen($admin_pass) < 8) {
    $err = 'กรุณากรอกข้อมูลให้ครบ และรหัสผ่านผู้ดูแลต้องยาวอย่างน้อย 8 ตัวอักษร';
  } else {
    try {
      $pdo = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", $u, $p, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      ]);
      // รันสคีมาทีละคำสั่ง (รองรับทุกโฮสต์)
      $sql = file_get_contents(__DIR__ . '/install.sql');
      foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $stmt) {
        if ($stmt !== '' && !str_starts_with($stmt, '--')) $pdo->exec($stmt);
      }
      // สร้างบัญชีผู้ดูแลสูงสุด
      $st = $pdo->prepare("INSERT INTO users (username,password_hash,name,role) VALUES (?,?,?,'super_admin')");
      $st->execute([$admin_user, password_hash($admin_pass, PASSWORD_DEFAULT), $admin_name ?: $admin_user]);
      // เขียน config.php
      $cfg = "<?php\nreturn [\n"
           . "  'db_host' => " . var_export($h, true) . ",\n"
           . "  'db_name' => " . var_export($n, true) . ",\n"
           . "  'db_user' => " . var_export($u, true) . ",\n"
           . "  'db_pass' => " . var_export($p, true) . ",\n"
           . "];\n";
      if (file_put_contents(__DIR__ . '/config.php', $cfg) === false) {
        $err = 'สร้าง config.php ไม่ได้ — กรุณาตรวจสิทธิ์การเขียนไฟล์ของโฟลเดอร์';
      } else {
        header('Location: install.php?done=1'); exit;
      }
    } catch (Exception $ex) {
      $err = 'ติดตั้งไม่สำเร็จ: ' . htmlspecialchars($ex->getMessage());
    }
  }
}
?>
<!doctype html>
<html lang="th">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>ติดตั้งระบบเว็บไซต์ฝ่ายบริหารงานทั่วไป</title>
<link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@400;500;700&family=Anuphan:wght@600;700&display=swap" rel="stylesheet">
<style>
:root{--theme:#1F3A6E;--accent:#C9A227}
*{box-sizing:border-box}
body{font-family:'Sarabun',sans-serif;background:#F2F1EC;margin:0;display:flex;min-height:100vh;align-items:center;justify-content:center;padding:20px}
.card{background:#fff;border-radius:14px;box-shadow:0 10px 40px rgba(31,58,110,.12);max-width:520px;width:100%;padding:36px}
h1{font-family:'Anuphan',sans-serif;color:var(--theme);font-size:1.5rem;margin:0 0 4px}
p.sub{color:#666;margin:0 0 24px;font-size:.95rem}
label{display:block;font-weight:500;margin:14px 0 4px;font-size:.92rem}
input{width:100%;padding:10px 12px;border:1px solid #d4d2ca;border-radius:8px;font-family:inherit;font-size:1rem}
input:focus{outline:2px solid var(--theme);border-color:transparent}
fieldset{border:1px solid #e4e2da;border-radius:10px;padding:6px 16px 16px;margin:20px 0 0}
legend{font-family:'Anuphan',sans-serif;color:var(--theme);font-weight:600;padding:0 8px}
button{margin-top:24px;width:100%;background:var(--theme);color:#fff;border:0;border-radius:8px;padding:13px;font-size:1.05rem;font-family:'Anuphan',sans-serif;font-weight:600;cursor:pointer}
button:hover{filter:brightness(1.1)}
.err{background:#fdecec;color:#b02a2a;border-radius:8px;padding:12px 14px;margin-bottom:8px;font-size:.92rem}
.ok{background:#eaf6ec;color:#1d7a35;border-radius:8px;padding:14px;line-height:1.7}
a{color:var(--theme)}
</style>
</head>
<body>
<div class="card">
<h1>ติดตั้งระบบเว็บไซต์ฝ่ายบริหารงานทั่วไป</h1>
<?php if (isset($_GET['done'])): ?>
  <div class="ok">ติดตั้งสำเร็จ 🎉<br>
  <strong>สำคัญ:</strong> กรุณาลบไฟล์ <code>install.php</code> ออกจากเซิร์ฟเวอร์ทันที<br>
  <a href="index.php">ไปหน้าเว็บไซต์</a> · <a href="admin/">เข้าสู่ระบบจัดการ</a></div>
<?php else: ?>
  <p class="sub">กรอกข้อมูลฐานข้อมูล MySQL และบัญชีผู้ดูแลระบบสูงสุด</p>
  <?php if ($err): ?><div class="err"><?= $err ?></div><?php endif; ?>
  <form method="post">
    <fieldset>
      <legend>ฐานข้อมูล MySQL</legend>
      <label>โฮสต์ฐานข้อมูล</label><input name="db_host" value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>">
      <label>ชื่อฐานข้อมูล</label><input name="db_name" value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" required>
      <label>ชื่อผู้ใช้ฐานข้อมูล</label><input name="db_user" value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" required>
      <label>รหัสผ่านฐานข้อมูล</label><input type="password" name="db_pass">
    </fieldset>
    <fieldset>
      <legend>บัญชีผู้ดูแลระบบสูงสุด</legend>
      <label>ชื่อผู้ใช้ (username)</label><input name="admin_user" required>
      <label>ชื่อ-นามสกุล</label><input name="admin_name">
      <label>รหัสผ่าน (อย่างน้อย 8 ตัวอักษร)</label><input type="password" name="admin_pass" minlength="8" required>
    </fieldset>
    <button type="submit">ติดตั้งระบบ</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
