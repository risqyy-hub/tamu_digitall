-- =============================================================
--  DATA DEMO (opsional)
--  Impor setelah database.sql untuk mengisi contoh data presentasi:
--    mysql -u root buku_tamu_digital < database/demo_data.sql
--  atau lewat phpMyAdmin (pilih database buku_tamu_digital -> Import).
--  Akun login TIDAK disertakan - buat melalui setup/create_admin.php.
-- =============================================================

USE buku_tamu_digital;

-- Bersihkan data transaksi lama (riwayat & jaminan), sisakan users/settings/log
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE guarantees;
TRUNCATE TABLE visits;
TRUNCATE TABLE visitor_qr_codes;
TRUNCATE TABLE registered_visitors;
TRUNCATE TABLE visitors;
TRUNCATE TABLE partner_companies;
TRUNCATE TABLE notifications;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------- Perusahaan mitra ----------------
INSERT INTO partner_companies (kode_mitra, nama_perusahaan, alamat, telepon, email, pic_nama, pic_telepon, status) VALUES
  ('MT-250101', 'PT Maju Jaya',   'Jl. Merdeka No. 10, Jakarta',   '021-5551001', 'admin@majujaya.test',  'Bapak Hendra', '08121112221', 'aktif'),
  ('MT-250102', 'PT Sejahtera',   'Jl. Sudirman No. 45, Jakarta',  '021-5551002', 'info@sejahtera.test',  'Ibu Lestari', '08121112222', 'aktif'),
  ('MT-250103', 'Koperasi Rakyat','Jl. Gatot Subroto No. 3, Bandung','022-5551003','koperasi@rakyat.test','Pak Yusuf',   '08121112223', 'nonaktif');

-- ---------------- Tamu ----------------
INSERT INTO visitors (kode_tamu, nama, jenis_identitas, no_identitas, telepon, email, alamat) VALUES
  ('TM-2501001', 'Budi Santoso',  'KTP', '3201234567890001', '081234567890', 'budi@mail.test',  'Jl. Anggrek 1, Jakarta'),
  ('TM-2501002', 'Siti Aminah',   'KTP', '3201234567890002', '081234567891', 'siti@mail.test',  'Jl. Melati 2, Bekasi'),
  ('TM-2501003', 'Andi Wijaya',   'SIM', '3201234567890003', '081234567892', '',                'Jl. Kenanga 3, Depok'),
  ('TM-2501004', 'Rina Kartika',  'Kartu Pegawai', 'PGW-0099', '081234567893', 'rina@mail.test', 'Perum Griya Asri, Bogor'),
  ('TM-2501005', 'Hendra Gunawan','Paspor', 'C1234567', '081234567894', '', 'Jl. Cendana 5, Tangerang');

-- ---------------- Tamu terdaftar (pemilik QR) ----------------
INSERT INTO registered_visitors (visitor_id, mitra_id, no_kartu, status, registered_at) VALUES
  ((SELECT id FROM visitors WHERE kode_tamu = 'TM-2501001'), (SELECT id FROM partner_companies WHERE kode_mitra = 'MT-250101'), 'KRT-0001', 'aktif', NOW() - INTERVAL 30 DAY),
  ((SELECT id FROM visitors WHERE kode_tamu = 'TM-2501002'), (SELECT id FROM partner_companies WHERE kode_mitra = 'MT-250102'), 'KRT-0002', 'aktif', NOW() - INTERVAL 20 DAY),
  ((SELECT id FROM visitors WHERE kode_tamu = 'TM-2501004'), NULL, 'KRT-0003', 'aktif', NOW() - INTERVAL 10 DAY);

-- ---------------- QR Code contoh (token TIDAK disimpan plaintext) ----------------
-- Catatan: token mentah QR demo ini tidak diketahui sehingga tidak dapat dipindai.
-- Untuk demo pemindaian, terbitkan QR baru melalui menu QR Code.
INSERT INTO visitor_qr_codes (registered_visitor_id, token_hash, status, valid_from, valid_until, issued_by) VALUES
  ((SELECT id FROM registered_visitors WHERE no_kartu = 'KRT-0001'),
   SHA2('token-demo-tidak-dipakai-1', 256), 'aktif', NOW() - INTERVAL 30 DAY, NOW() + INTERVAL 60 DAY, 1),
  ((SELECT id FROM registered_visitors WHERE no_kartu = 'KRT-0002'),
   SHA2('token-demo-tidak-dipakai-2', 256), 'dicabut', NOW() - INTERVAL 20 DAY, NULL, 1);

-- ---------------- Riwayat kunjungan (untuk grafik & laporan) ----------------
-- Kunjungan selesai hari ini
INSERT INTO visits (kode_kunjungan, visitor_id, registered_visitor_id, mitra_id, instansi, tujuan, pegawai_ditemui, keperluan, jam_masuk, jam_keluar, durasi_menit, status, petugas_masuk_id, petugas_keluar_id) VALUES
  ('KT-DEMO-0001', (SELECT id FROM visitors WHERE kode_tamu = 'TM-2501002'), 2, 2, NULL, 'Bidang Keuangan', 'Ibu Sari Wahyuni', 'Penyerahan dokumen faktur', DATE_ADD(NOW(), INTERVAL -3 HOUR), DATE_ADD(NOW(), INTERVAL -2 HOUR), 60, 'selesai', 2, 2),
  ('KT-DEMO-0002', (SELECT id FROM visitors WHERE kode_tamu = 'TM-2501003'), NULL, NULL, 'Sendiri', 'Bidang Umum', 'Bapak Andi Pratama', 'Konsultasi perizinan', DATE_ADD(NOW(), INTERVAL -5 HOUR), DATE_SUB(NOW(), INTERVAL 260 MINUTE), 40, 'selesai', 2, 2);

-- Kunjungan kemarin & 3 hari lalu (grafik 7 hari)
INSERT INTO visits (kode_kunjungan, visitor_id, mitra_id, instansi, tujuan, pegawai_ditemui, keperluan, jam_masuk, jam_keluar, durasi_menit, status, petugas_masuk_id, petugas_keluar_id) VALUES
  ('KT-DEMO-0003', (SELECT id FROM visitors WHERE kode_tamu = 'TM-2501005'), 1, NULL, 'Direksi', 'Bapak Direktur', 'Rapat koordinasi tahunan', DATE_ADD(CURDATE(), INTERVAL -1 DAY) + INTERVAL 9 HOUR, DATE_ADD(CURDATE(), INTERVAL -1 DAY) + INTERVAL 11 HOUR, 120, 'selesai', 1, 1),
  ('KT-DEMO-0004', (SELECT id FROM visitors WHERE kode_tamu = 'TM-2501001'), 1, NULL, 'Bidang Teknologi Informasi', 'Ibu Rina Kartika', 'Perbaikan jaringan', DATE_ADD(CURDATE(), INTERVAL -3 DAY) + INTERVAL 10 HOUR, DATE_ADD(CURDATE(), INTERVAL -3 DAY) + INTERVAL 12 HOUR + INTERVAL 30 MINUTE, 150, 'selesai', 2, 2);

-- Kunjungan yang SEDANG BERLANGSUNG + 1 jaminan aktif (demo validasi check-out)
INSERT INTO visits (kode_kunjungan, visitor_id, registered_visitor_id, mitra_id, instansi, tujuan, pegawai_ditemui, keperluan, jam_masuk, status, petugas_masuk_id) VALUES
  ('KT-DEMO-0005', (SELECT id FROM visitors WHERE kode_tamu = 'TM-2501001'), 1, 1, NULL, 'Bidang SDM', 'Ibu Maya Putri', 'Pengajuan berkas karyawan baru', DATE_ADD(NOW(), INTERVAL -45 MINUTE), 'berlangsung', 2);

-- Jaminan terkait kunjungan aktif (masih 'aktif' -> check-out ditolak sampai dikembalikan)
INSERT INTO guarantees (visit_id, barang, jumlah, keterangan, status, diterima_oleh, diterima_at) VALUES
  ((SELECT id FROM visits WHERE kode_kunjungan = 'KT-DEMO-0005'), 'KTP', 1, 'KTP dititipkan saat menunggu proses berkas', 'aktif', 2, DATE_ADD(NOW(), INTERVAL -40 MINUTE));

-- Jaminan pada kunjungan selesai (sudah dikembalikan)
INSERT INTO guarantees (visit_id, barang, jumlah, keterangan, status, diterima_oleh, diterima_at, dikembalikan_oleh, dikembalikan_at) VALUES
  ((SELECT id FROM visits WHERE kode_kunjungan = 'KT-DEMO-0002'), 'SIM A', 1, 'SIM dititipkan', 'dikembalikan', 2, DATE_ADD(NOW(), INTERVAL -5 HOUR), 2, DATE_SUB(NOW(), INTERVAL 265 MINUTE));
