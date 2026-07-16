-- =====================================================
-- School General Administration CMS - Phase 1 Schema
-- MySQL 5.7+ / MariaDB 10+  (utf8mb4)
-- =====================================================

CREATE TABLE IF NOT EXISTS settings (
  skey VARCHAR(80) PRIMARY KEY,
  svalue TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(60) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  name VARCHAR(120) NOT NULL,
  role ENUM('super_admin','admin','editor','staff','viewer') NOT NULL DEFAULT 'staff',
  active TINYINT(1) NOT NULL DEFAULT 1,
  last_login DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS modules (
  id INT AUTO_INCREMENT PRIMARY KEY,
  mkey VARCHAR(40) NOT NULL UNIQUE,
  name VARCHAR(120) NOT NULL,
  enabled TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS menus (
  id INT AUTO_INCREMENT PRIMARY KEY,
  parent_id INT NULL,
  title VARCHAR(120) NOT NULL,
  url VARCHAR(255) NOT NULL DEFAULT '#',
  is_external TINYINT(1) NOT NULL DEFAULT 0,
  new_tab TINYINT(1) NOT NULL DEFAULT 0,
  icon VARCHAR(40) DEFAULT '',
  sort INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  module VARCHAR(40) NOT NULL,
  name VARCHAR(120) NOT NULL,
  sort INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS posts (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ptype ENUM('news','announcement') NOT NULL DEFAULT 'news',
  title VARCHAR(255) NOT NULL,
  excerpt TEXT,
  body MEDIUMTEXT,
  cover VARCHAR(255) DEFAULT '',
  gallery TEXT,              -- JSON array of image paths
  attachments TEXT,          -- JSON array of {name,path}
  category_id INT NULL,
  featured TINYINT(1) NOT NULL DEFAULT 0,
  pinned TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('draft','published') NOT NULL DEFAULT 'draft',
  publish_at DATETIME NULL,
  views INT NOT NULL DEFAULT 0,
  created_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_type_status (ptype, status, publish_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS downloads (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  category_id INT NULL,
  file VARCHAR(255) NOT NULL,
  ext VARCHAR(10) DEFAULT '',
  fsize INT NOT NULL DEFAULT 0,
  hits INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS personnel (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(150) NOT NULL,
  position VARCHAR(150) DEFAULT '',
  department VARCHAR(150) DEFAULT '',
  phone VARCHAR(40) DEFAULT '',
  email VARCHAR(120) DEFAULT '',
  link VARCHAR(255) DEFAULT '',
  photo VARCHAR(255) DEFAULT '',
  sort INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS links (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  url VARCHAR(500) NOT NULL,
  icon VARCHAR(40) DEFAULT '',
  image VARCHAR(255) DEFAULT '',
  category_id INT NULL,
  new_tab TINYINT(1) NOT NULL DEFAULT 1,
  sort INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  slug VARCHAR(150) NOT NULL UNIQUE,
  body MEDIUMTEXT,
  status ENUM('draft','published') NOT NULL DEFAULT 'published',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS events (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL,
  etype ENUM('activity','meeting','holiday','important') NOT NULL DEFAULT 'activity',
  start_date DATE NOT NULL,
  end_date DATE NULL,
  location VARCHAR(255) DEFAULT '',
  detail TEXT,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS blocks (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  btype ENUM('banner','notice','buttons','cards','html') NOT NULL DEFAULT 'notice',
  content TEXT,              -- JSON ตามชนิดบล็อก
  zone ENUM('global_top','home_top','home_middle','home_bottom','global_bottom') NOT NULL DEFAULT 'home_top',
  sort INT NOT NULL DEFAULT 0,
  visible TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS activity_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  action VARCHAR(80) NOT NULL,
  detail VARCHAR(500) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS visits (
  vdate DATE PRIMARY KEY,
  hits INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------- Seed: modules ----------
INSERT INTO modules (mkey, name, enabled, sort) VALUES
('news','ข่าวประชาสัมพันธ์',1,1),
('announcement','ประกาศ',1,2),
('downloads','ดาวน์โหลดเอกสาร',1,3),
('personnel','บุคลากร',1,4),
('calendar','ปฏิทินกิจกรรม',1,5),
('links','ลิงก์ที่เกี่ยวข้อง',1,6),
('contact','ติดต่อเรา',1,7);

-- ---------- Seed: menus ----------
INSERT INTO menus (parent_id,title,url,icon,sort,visible) VALUES
(NULL,'หน้าแรก','index.php','home',1,1),
(NULL,'เกี่ยวกับฝ่าย','index.php?p=page&slug=about','info',2,1),
(NULL,'ข่าวประชาสัมพันธ์','index.php?p=news','news',3,1),
(NULL,'ประกาศ','index.php?p=announcement','megaphone',4,1),
(NULL,'ดาวน์โหลดเอกสาร','index.php?p=downloads','download',5,1),
(NULL,'บุคลากร','index.php?p=personnel','users',6,1),
(NULL,'ปฏิทินกิจกรรม','index.php?p=calendar','calendar',7,1),
(NULL,'ติดต่อเรา','index.php?p=contact','phone',8,1);

-- ---------- Seed: categories ----------
INSERT INTO categories (module,name,sort) VALUES
('news','กิจกรรมโรงเรียน',1),
('news','ผลงาน/รางวัล',2),
('announcement','คำสั่ง/หนังสือราชการ',1),
('announcement','ประกาศทั่วไป',2),
('downloads','แบบฟอร์ม',1),
('downloads','คู่มือ/ระเบียบ',2),
('links','หน่วยงานราชการ',1),
('links','ระบบสารสนเทศ',2);

-- ---------- Seed: default page ----------
INSERT INTO pages (title,slug,body,status) VALUES
('เกี่ยวกับฝ่ายบริหารงานทั่วไป','about',
'<p>ฝ่ายบริหารงานทั่วไปมีหน้าที่สนับสนุนการบริหารจัดการของโรงเรียนในด้านงานสารบรรณ งานอาคารสถานที่ งานประชาสัมพันธ์ และงานบริการทั่วไป</p><p>ท่านสามารถแก้ไขเนื้อหาหน้านี้ได้ที่เมนู <strong>หน้าเนื้อหา</strong> ในระบบจัดการ</p>',
'published');

-- ---------- Seed: sample block ----------
INSERT INTO blocks (title,btype,content,zone,sort,visible) VALUES
('กล่องแนะนำการใช้งาน','notice',
'{"heading":"ยินดีต้อนรับสู่เว็บไซต์ใหม่","text":"บล็อกนี้สร้างจากระบบ Content Blocks — แก้ไขหรือลบได้ที่เมนู บล็อกเนื้อหา ในระบบจัดการ","color":"accent","url":"","btn":""}',
'home_top',1,1);

-- ---------- Seed: settings ----------
INSERT INTO settings (skey, svalue) VALUES
('school_name','โรงเรียนตัวอย่างวิทยา'),
('school_name_en','Example Wittaya School'),
('site_title','ฝ่ายบริหารงานทั่วไป'),
('logo',''),('favicon',''),
('theme_color','#1F3A6E'),
('accent_color','#C9A227'),
('font_body','Sarabun'),
('font_display','Anuphan'),
('hero_title','ฝ่ายบริหารงานทั่วไป'),
('hero_subtitle','ศูนย์กลางข้อมูล ข่าวสาร เอกสาร และงานบริการของโรงเรียน'),
('hero_image',''),
('address','เลขที่ 1 ถนนตัวอย่าง ตำบลในเมือง อำเภอเมือง จังหวัดพิษณุโลก 65000'),
('phone','0-5525-xxxx'),
('email','contact@school.ac.th'),
('map_embed',''),
('facebook',''),('line',''),('youtube',''),
('footer_text','ฝ่ายบริหารงานทั่วไป — พัฒนาเพื่อการบริการที่เป็นเลิศ'),
('seo_description','เว็บไซต์ฝ่ายบริหารงานทั่วไปของโรงเรียน ข่าวประชาสัมพันธ์ ประกาศ เอกสารดาวน์โหลด และข้อมูลบุคลากร'),
('seo_keywords','ฝ่ายบริหารงานทั่วไป, โรงเรียน, ข่าวประชาสัมพันธ์');
