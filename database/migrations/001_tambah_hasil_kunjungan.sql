-- =============================================================
--  MIGRASI: Kolom HASIL KUNJUNGAN pada tabel VISITS
--  Tanggal : 14 Oktober 2026
--  Sifat   : AMAN - hanya MENAMBAH kolom.
--            Tidak menghapus/mengganti kolom, tidak mereset database,
--            tidak menghapus data lama, tidak membuat tabel baru.
--            Kunjungan lama otomatis memiliki hasil_kunjungan = NULL
--            dan tetap tampil (ditampilkan sebagai "-").
--
--  Pemakaian:
--   * Supabase PostgreSQL -> jalankan blok A (Supabase SQL Editor).
--   * MySQL / MariaDB (XAMPP) -> jalankan blok B (phpMyAdmin / CLI).
--   Jalankan hanya SATU blok, sesuai driver database project (lihat .env).
-- =============================================================

-- -------------------------------------------------------------
--  BLOK A - Supabase PostgreSQL
-- -------------------------------------------------------------
ALTER TABLE visits
ADD COLUMN IF NOT EXISTS hasil_kunjungan TEXT;

-- -------------------------------------------------------------
--  BLOK B - MySQL / MariaDB (XAMPP)
--  (dikomentari; aktifkan bila memakai MySQL, lalu jalankan)
-- -------------------------------------------------------------
-- ALTER TABLE visits
-- ADD COLUMN IF NOT EXISTS hasil_kunjungan TEXT;

-- -------------------------------------------------------------
--  VERIFIKASI (opsional)
-- -------------------------------------------------------------
-- PostgreSQL : SELECT column_name, data_type, is_nullable
--              FROM information_schema.columns
--              WHERE table_name = 'visits' AND column_name = 'hasil_kunjungan';
-- MySQL      : SHOW COLUMNS FROM visits LIKE 'hasil_kunjungan';
