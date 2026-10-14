-- =============================================================
--  SISTEM INFORMASI BUKU TAMU DIGITAL & MONITORING KUNJUNGAN
--  Skema Database: MySQL / MariaDB (XAMPP)
--  Database: buku_tamu_digital
--
--  Impor via phpMyAdmin (pilih database lalu Import) atau CLI:
--    mysql -u root -p < database.sql
--  (atau tanpa password: mysql -u root < database.sql)
--
--  Catatan: nama tabel & kolom sengaja dibuat sama dengan skema
--  Supabase PostgreSQL (lihat database/supabase_schema.sql) agar
--  migrasi nanti cukup mengganti driver + menjalankan skema pg.
-- =============================================================

CREATE DATABASE IF NOT EXISTS buku_tamu_digital
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE buku_tamu_digital;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS guarantees;
DROP TABLE IF EXISTS visits;
DROP TABLE IF EXISTS visitor_qr_codes;
DROP TABLE IF EXISTS registered_visitors;
DROP TABLE IF EXISTS visitors;
DROP TABLE IF EXISTS partner_companies;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS activity_logs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
--  USERS (akun admin & petugas)
--  Password disimpan sebagai hash bcrypt (password_hash()).
--  Tidak ada akun default di sini: buat lewat setup/create_admin.php
-- -------------------------------------------------------------
CREATE TABLE users (
  id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nama        VARCHAR(100) NOT NULL,
  username    VARCHAR(50)  NOT NULL,
  email       VARCHAR(100) DEFAULT NULL,
  telepon     VARCHAR(20)  DEFAULT NULL,
  password    VARCHAR(255) NOT NULL,
  role        VARCHAR(10)  NOT NULL DEFAULT 'petugas' CHECK (role IN ('admin','petugas')),
  foto        VARCHAR(255) DEFAULT NULL,
  status      VARCHAR(10)  NOT NULL DEFAULT 'aktif'    CHECK (status IN ('aktif','nonaktif')),
  created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  PARTNER_COMPANIES (perusahaan / instansi mitra)
-- -------------------------------------------------------------
CREATE TABLE partner_companies (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_mitra      VARCHAR(30)  NOT NULL,
  nama_perusahaan VARCHAR(150) NOT NULL,
  alamat          TEXT         DEFAULT NULL,
  telepon         VARCHAR(20)  DEFAULT NULL,
  email           VARCHAR(100) DEFAULT NULL,
  pic_nama        VARCHAR(100) DEFAULT NULL,
  pic_telepon     VARCHAR(20)  DEFAULT NULL,
  status          VARCHAR(10)  NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif','nonaktif')),
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mitra_kode (kode_mitra),
  KEY idx_mitra_nama (nama_perusahaan)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  VISITORS (identitas tamu)
-- -------------------------------------------------------------
CREATE TABLE visitors (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_tamu       VARCHAR(30)  NOT NULL,
  nama            VARCHAR(100) NOT NULL,
  jenis_identitas VARCHAR(30)  NOT NULL DEFAULT 'KTP' CHECK (jenis_identitas IN ('KTP','SIM','Paspor','Kartu Pegawai','Lainnya')),
  no_identitas    VARCHAR(50)  DEFAULT NULL,
  telepon         VARCHAR(20)  DEFAULT NULL,
  email           VARCHAR(100) DEFAULT NULL,
  alamat          TEXT         DEFAULT NULL,
  created_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at      TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visitors_kode (kode_tamu),
  KEY idx_visitors_nama (nama),
  KEY idx_visitors_identitas (no_identitas)
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  REGISTERED_VISITORS (tamu terdaftar, terhubung ke mitra opsional)
--  Satu tamu dapat memiliki banyak riwayat registrasi.
-- -------------------------------------------------------------
CREATE TABLE registered_visitors (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  visitor_id     INT UNSIGNED NOT NULL,
  mitra_id       INT UNSIGNED DEFAULT NULL,
  no_kartu       VARCHAR(50)  DEFAULT NULL,
  status         VARCHAR(10)  NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif','nonaktif')),
  registered_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  created_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reg_no_kartu (no_kartu),
  KEY idx_reg_visitor (visitor_id),
  KEY idx_reg_mitra (mitra_id),
  CONSTRAINT fk_reg_visitor FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE,
  CONSTRAINT fk_reg_mitra   FOREIGN KEY (mitra_id)   REFERENCES partner_companies(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  VISITOR_QR_CODES
--  Token mentah TIDAK disimpan - yang disimpan adalah hash SHA-256.
--  Masa berlaku: valid_from .. valid_until (NULL = tanpa batas).
-- -------------------------------------------------------------
CREATE TABLE visitor_qr_codes (
  id                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  registered_visitor_id  INT UNSIGNED NOT NULL,
  token_hash             CHAR(64)     NOT NULL,
  status                 VARCHAR(10)  NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif','dicabut')),
  valid_from             DATETIME     NOT NULL,
  valid_until            DATETIME     DEFAULT NULL,
  issued_by              INT UNSIGNED DEFAULT NULL,
  revoked_at             DATETIME     DEFAULT NULL,
  revoked_by             INT UNSIGNED DEFAULT NULL,
  created_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at             TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_qr_token_hash (token_hash),
  KEY idx_qr_registered (registered_visitor_id),
  KEY idx_qr_status (status),
  CONSTRAINT fk_qr_registered FOREIGN KEY (registered_visitor_id) REFERENCES registered_visitors(id) ON DELETE CASCADE,
  CONSTRAINT fk_qr_issued_by  FOREIGN KEY (issued_by) REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_qr_revoked_by FOREIGN KEY (revoked_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  VISITS (transaksi kunjungan: check-in & check-out)
--  Setiap kedatangan membuat record BARU (riwayat tidak ditimpa).
--  visitor_id ON DELETE RESTRICT -> tamu dengan riwayat tidak bisa dihapus.
-- -------------------------------------------------------------
CREATE TABLE visits (
  id                    INT UNSIGNED NOT NULL AUTO_INCREMENT,
  kode_kunjungan        VARCHAR(30)  NOT NULL,
  visitor_id            INT UNSIGNED NOT NULL,
  registered_visitor_id INT UNSIGNED DEFAULT NULL,
  mitra_id              INT UNSIGNED DEFAULT NULL,
  qr_id                 INT UNSIGNED DEFAULT NULL,
  instansi              VARCHAR(150) DEFAULT NULL,
  tujuan                VARCHAR(200) NOT NULL,
  pegawai_ditemui       VARCHAR(120) NOT NULL,
  keperluan             TEXT         DEFAULT NULL,
  jam_masuk             DATETIME     NOT NULL,
  jam_keluar            DATETIME     DEFAULT NULL,
  durasi_menit          INT UNSIGNED DEFAULT NULL,
  status                VARCHAR(15)  NOT NULL DEFAULT 'berlangsung' CHECK (status IN ('berlangsung','selesai')),
  petugas_masuk_id      INT UNSIGNED DEFAULT NULL,
  petugas_keluar_id     INT UNSIGNED DEFAULT NULL,
  created_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at            TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_visits_kode (kode_kunjungan),
  KEY idx_visits_visitor (visitor_id),
  KEY idx_visits_registered (registered_visitor_id),
  KEY idx_visits_mitra (mitra_id),
  KEY idx_visits_qr (qr_id),
  KEY idx_visits_status (status),
  KEY idx_visits_masuk (jam_masuk),
  CONSTRAINT fk_visits_visitor FOREIGN KEY (visitor_id)            REFERENCES visitors(id) ON DELETE RESTRICT,
  CONSTRAINT fk_visits_reg     FOREIGN KEY (registered_visitor_id) REFERENCES registered_visitors(id) ON DELETE SET NULL,
  CONSTRAINT fk_visits_mitra   FOREIGN KEY (mitra_id)              REFERENCES partner_companies(id) ON DELETE SET NULL,
  CONSTRAINT fk_visits_qr      FOREIGN KEY (qr_id)                 REFERENCES visitor_qr_codes(id) ON DELETE SET NULL,
  CONSTRAINT fk_visits_pm      FOREIGN KEY (petugas_masuk_id)      REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_visits_pk      FOREIGN KEY (petugas_keluar_id)     REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  GUARANTEES (jaminan / barang titipan tamu)
--  Check-out DITOLAK selama masih ada jaminan berstatus 'aktif'.
--  Jaminan 'dikembalikan' tetap disimpan sebagai riwayat.
-- -------------------------------------------------------------
CREATE TABLE guarantees (
  id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
  visit_id          INT UNSIGNED NOT NULL,
  barang            VARCHAR(150) NOT NULL,
  jumlah            INT UNSIGNED NOT NULL DEFAULT 1,
  keterangan        TEXT         DEFAULT NULL,
  bukti_file        VARCHAR(255) DEFAULT NULL,
  status            VARCHAR(15)  NOT NULL DEFAULT 'aktif' CHECK (status IN ('aktif','dikembalikan')),
  diterima_oleh     INT UNSIGNED DEFAULT NULL,
  dikembalikan_oleh INT UNSIGNED DEFAULT NULL,
  diterima_at       DATETIME     NOT NULL,
  dikembalikan_at   DATETIME     DEFAULT NULL,
  created_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at        TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_guar_visit (visit_id),
  KEY idx_guar_status (status),
  CONSTRAINT fk_guar_visit    FOREIGN KEY (visit_id)          REFERENCES visits(id) ON DELETE CASCADE,
  CONSTRAINT fk_guar_diterima FOREIGN KEY (diterima_oleh)     REFERENCES users(id) ON DELETE SET NULL,
  CONSTRAINT fk_guar_kembali  FOREIGN KEY (dikembalikan_oleh) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  NOTIFICATIONS (user_id NULL = untuk semua pengguna)
-- -------------------------------------------------------------
CREATE TABLE notifications (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED DEFAULT NULL,
  judul      VARCHAR(150) NOT NULL,
  pesan      TEXT         DEFAULT NULL,
  tipe       VARCHAR(10)  NOT NULL DEFAULT 'info' CHECK (tipe IN ('info','success','warning','danger')),
  is_read    TINYINT(1)   NOT NULL DEFAULT 0,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_notif_user (user_id, is_read),
  CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  ACTIVITY_LOGS (audit trail)
-- -------------------------------------------------------------
CREATE TABLE activity_logs (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  user_id    INT UNSIGNED DEFAULT NULL,
  username   VARCHAR(50)  DEFAULT NULL,
  role       VARCHAR(10)  DEFAULT NULL,
  aktivitas  VARCHAR(255) NOT NULL,
  ip_address VARCHAR(45)  DEFAULT NULL,
  user_agent VARCHAR(255) DEFAULT NULL,
  created_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_logs_user (user_id),
  KEY idx_logs_created (created_at),
  CONSTRAINT fk_logs_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- -------------------------------------------------------------
--  SETTINGS (konfigurasi aplikasi key-value)
-- -------------------------------------------------------------
CREATE TABLE settings (
  id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  key_name   VARCHAR(50)  NOT NULL,
  value      TEXT         DEFAULT NULL,
  deskripsi  VARCHAR(255) DEFAULT NULL,
  updated_at TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_settings_key (key_name)
) ENGINE=InnoDB;

-- =============================================================
--  DATA AWAL (pengaturan bawaan)
-- =============================================================
INSERT INTO settings (key_name, value, deskripsi) VALUES
  ('nama_instansi',   'Nama Instansi Anda',   'Nama instansi / kantor'),
  ('alamat_instansi', 'Jl. Contoh Alamat No. 1', 'Alamat instansi'),
  ('kontak_instansi', '(021) 555-0123',       'Nomor telepon instansi'),
  ('jam_buka',        '08:00',                'Jam mulai layanan buku tamu'),
  ('jam_tutup',       '16:00',                'Jam selesai layanan buku tamu'),
  ('qr_default_days', '30',                   'Masa berlaku default QR (hari)');

-- Akun awal dibuat melalui skrip CLI agar password tidak berupa teks biasa:
--   php setup/create_admin.php
-- Ikuti instruksi di README.md bagian "Membuat Akun Admin".
