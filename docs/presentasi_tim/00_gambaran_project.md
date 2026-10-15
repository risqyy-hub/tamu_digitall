# BAB I — Gambaran Umum Project

> Dokumen acuan bersama. Semua anggota wajib memakai istilah, daftar fitur, dan teknologi yang sama dengan dokumen ini.

## 1. Nama Project

**Sistem Informasi Buku Tamu Digital dan Monitoring Kunjungan Berbasis Web**

Nama aplikasi yang tampil di kode: konstanta `APP_NAME = 'Buku Tamu Digital'` di `config/database.php` (baris 55). Judul halaman memakai format `… · <nama instansi dari tabel settings>` (`includes/header.php` baris 18).

## 2. Latar Belakang

Berdasarkan fitur yang benar-benar dibangun di project, aplikasi ini menjawab kebutuhan pencatatan tamu di kantor/instansi yang selama ini masih manual (buku fisik):

- Buku tamu fisik sulit dicari kembali, mudah rusak, dan tidak bisa dianalisis.
- Tidak ada cara cepat mengetahui **siapa yang sedang berada di kantor** dan **berapa lama** ia berkunjung.
- Barang titipan tamu (KTP, dokumen, laptop) sering tidak tercatat, sehingga petugas lupa menagih pengembalian.
- Pelaporan bulanan harus dihitung manual dari buku tamu.
- Akses terhadap data tamu harus dibatasi (hanya petugas/admin), sedangkan buku fisik bisa dibaca siapa saja.

> Catatan PM: paragraf di atas adalah **uraian masalah yang disusun dari fitur aplikasi**. Belum ada dokumen latar belakang/needs assessment terpisah di dalam repo — PM perlu menyesuaikan dengan kondisi nyata instansi yang dipilih dan menambahkan kutipan/observasi lapangan sendiri (lihat `01_pm_project_manager.md` bagian "Yang harus dilengkapi PM").

## 3. Permasalahan

1. Pencatatan tamu manual → rawan hilang, sulit ditelusuri, tidak bisa rekap otomatis.
2. Monitoring kunjungan tidak real-time → tidak diketahui tamu yang sedang berada di dalam.
3. Jaminan/barang titipan tidak tercatat → risiko kehilangan dan check-out dilakukan tanpa pengembalian barang.
4. Identitas tamu bisa dipalsukan bila hanya mengandalkan input browser → perlu validasi di server.
5. Keamanan data (injection, sesi, akses tidak sah) sering terabaikan pada aplikasi buku tamu sederhana.
6. Pelaporan & ekspor data sering merepotkan bila harus disalin manual ke Excel.

## 4. Tujuan

1. Menggantikan buku tamu fisik dengan aplikasi web terpusat.
2. Mencatat kunjungan (check-in & check-out) lengkap dengan durasi secara otomatis.
3. Menyediakan **monitoring kunjungan aktif** secara real-time di dashboard.
4. Mengelola **jaminan/barang titipan** dan memblokir check-out selama jaminan belum dikembalikan.
5. Mempercepat identifikasi tamu lewat **QR Code** dengan token yang divalidasi di server.
6. Menyediakan **laporan** dengan filter, cetak/PDF, dan ekspor CSV.
7. Menjaga keamanan data: login berbasis role, prepared statement, CSRF, hash password, dan audit log.

## 5. Ruang Lingkup

**Masuk dalam lingkup (ada implementasinya):**

| Area | Bukti file |
|---|---|
| Autentikasi & role (admin, petugas) | `actions/login_process.php`, `includes/auth.php`, `logout.php` |
| Data master: tamu, mitra, registrasi tamu terdaftar, user | `actions/tamu_process.php`, `actions/mitra_process.php`, `actions/registrasi_process.php`, `actions/user_process.php` |
| QR Code tamu (terbitkan/cabut/hapus/tampil sekali) | `actions/qr_process.php`, `admin/qr/*.php` |
| Scanner QR kamera + token manual | `petugas/qr/scanner.php`, `assets/js/scanner.js` |
| Check-in (QR, manual, walk-in) & check-out | `actions/kunjungan_process.php`, `actions/checkout_process.php` |
| Jaminan (tambah, kembalikan, hapus admin, upload bukti) | `actions/jaminan_process.php`, `includes/modules/jaminan/*.php` |
| Hasil kunjungan (catatan opsional) | `actions/kunjungan_process.php` aksi `simpan_hasil`, kolom `visits.hasil_kunjungan` |
| Monitoring (daftar kunjungan, kunjungan aktif, durasi berjalan) | `includes/modules/kunjungan/index.php`, `aktif.php`, `assets/js/app.js` |
| Dashboard statistik + grafik | `admin/dashboard.php`, `petugas/dashboard.php` |
| Laporan, cetak, ekspor CSV | `admin/laporan/index.php`, `admin/laporan/ekspor_csv.php`, `includes/laporan_helper.php` |
| Pengaturan instansi, log aktivitas, notifikasi, profil | `admin/pengaturan.php`, `admin/aktivitas.php`, `includes/navbar.php`, `actions/profil_process.php` |

**Di luar lingkup / belum ditemukan dalam kode yang diperiksa:**

- Pendaftaran tamu mandiri oleh tamu (self-service) — pendaftaran dilakukan petugas/admin.
- Pembayaran/QRIS, reservasi jadwal, atau modul cuti.
- Aplikasi mobile native (scanner memakai browser).
- Reset password via email, 2FA, dan antrean nomor antrian.
- Halaman laporan untuk petugas (menu **Laporan** hanya untuk admin — `includes/sidebar.php` baris 30 dan guard `require_role('admin')` di `admin/laporan/index.php` baris 8).

## 6. Teknologi yang Benar-Benar Digunakan

| Lapisan | Teknologi | Bukti di kode |
|---|---|---|
| Bahasa backend | PHP >= 8.1, **tanpa framework (PHP Native)** | `composer.json` (`"php": ">=8.1"`), struktur file procedural |
| Akses database | **PDO** (prepared statement), driver `mysql` atau `pgsql` | `config/database.php` fungsi `db()` baris 80–132 |
| Database | MySQL/MariaDB (XAMPP); skema PostgreSQL/Siap Supabase tersedia | `database.sql`, `database/supabase_schema.sql` |
| Konfigurasi | File `.env` + loader buatan sendiri | `config/database.php` baris 11–39, `.env.example` |
| Session & keamanan | PHP session (`BTDSID`), CSRF token per sesi, `password_hash`/`password_verify` | `includes/auth.php`, `includes/csrf.php`, `actions/login_process.php` |
| UI framework | **Bootstrap 5** (lokal di `assets/vendor/bootstrap`) | `includes/header.php` baris 23 |
| Ikon | Bootstrap Icons (lokal) | `includes/header.php` baris 24 |
| Grafik | **Chart.js** (lokal) | `admin/dashboard.php` baris 224 |
| Dialog | **SweetAlert2** (lokal) | `includes/footer.php` baris 20, `assets/js/app.js` baris 10–18 |
| Pemindai QR | **jsQR** (lokal) | `assets/js/scanner.js` baris 36 |
| Generator QR | `chillerlan/php-qrcode` via Composer | `composer.json`, `includes/functions.php` fungsi `qr_png_datauri()` baris 215–230 |
| Font | Google Fonts (Inter, dimuat non-blocking) | `includes/header.php` baris 19–22 |
| Version control | Git (3 commit, 2 penulis per 15 Okt 2026 — `risqy`, `Bintangpa`) | `git log` |

Semua pustaka frontend sudah di-vendor, jadi aplikasi tetap jalan **tanpa internet** (kecuali font Google — dimuat dengan `media="print" onload` sehingga tidak memperlambat halaman).

## 7. Ringkasan Fitur yang Sudah Terimplementasi

1. **Login/logout** — verifikasi `password_verify`, penolakan akun nonaktif, `session_regenerate_id()`, jeda 0,6 detik saat gagal login, idle timeout 2 jam (`includes/auth.php` baris 11).
2. **Dua role**: `admin` dan `petugas` (dicek di database, `CHECK (role IN ('admin','petugas'))`, `database.sql` baris 46). Guard: `require_role('admin')` → hanya admin; `require_role('petugas')` → admin **atau** petugas (`includes/auth.php` baris 130–151).
3. **Dashboard admin**: 6 kartu statistik, grafik batang 7 hari, grafik donat status 30 hari, panel kunjungan aktif/terbaru/log (`admin/dashboard.php`).
4. **Dashboard petugas**: 4 kartu statistik, kunjungan aktif, kunjungan terakhir, log milik sendiri (`petugas/dashboard.php` baris 33–36 hanya mengambil `user_id` sendiri).
5. **CRUD data tamu, mitra, registrasi, user, pengaturan** dengan validasi server-side.
6. **QR Code**: token acak 256-bit, disimpan sebagai **hash SHA-256**, ditampilkan **sekali** lewat flash token di session, bisa dicabut, tidak bisa dihapus bila sudah dipakai kunjungan (`actions/qr_process.php`).
7. **Scanner QR** kamera (`getUserMedia` + jsQR) dan input token manual (`petugas/qr/scanner.php`, `assets/js/scanner.js`).
8. **Check-in 3 mode**: via QR (validasi ulang token di server), pilih tamu terdaftar, dan walk-in/tamu baru (`actions/kunjungan_process.php` baris 107–193).
9. **Check-out aman**: transaksi DB + `FOR UPDATE`, tolak check-out ganda, tolak bila masih ada jaminan aktif, hitung durasi, catat audit log (`actions/checkout_process.php`).
10. **Jaminan**: penerimaan (opsional upload JPG/PNG/PDF maks 2 MB), pengembalian, hapus hanya oleh admin dan hanya status `dikembalikan` (`actions/jaminan_process.php`).
11. **Hasil kunjungan**: kolom `visits.hasil_kunjungan`, opsional, maksimal 4000 karakter, bisa diisi saat check-in, disimpan lewat panel detail, atau saat check-out (`includes/functions.php` baris 13).
12. **Monitoring & pencarian**: filter status/tanggal/mitra, pencarian, pagination, durasi berjalan yang diperbarui tiap 30 detik oleh JavaScript (`assets/js/app.js` baris 61–71).
13. **Laporan**: filter tanggal/status/mitra/petugas, ringkasan total & durasi, cetak `window.print()`, ekspor CSV dengan BOM UTF-8 dan proteksi CSV injection (`admin/laporan/ekspor_csv.php`, `includes/functions.php` baris 351–379).
14. **Audit log** setiap aksi penting + halaman log dengan filter (`admin/aktivitas.php`, fungsi `log_aktivitas()`).
15. **Notifikasi** lonceng (tanda sudah dibaca per item / semua) (`includes/navbar.php`, `actions/notif_process.php`).
16. **Profil**: ubah data diri & ganti password (verifikasi password lama, min 8 karakter) (`actions/profil_process.php`).

## 8. Struktur Folder (singkat)

```
buku_tamu_digital/
├── index.php, login.php, logout.php      # entry, login, keluar
├── config/database.php                   # loader .env + koneksi PDO
├── includes/                             # auth, csrf, fungsi, layout, modul bersama
│   └── modules/                          # halaman dipakai admin & petugas
├── actions/                              # semua handler POST (proses bisnis)
├── admin/                                # area admin (wrapper tipis -> modules)
├── petugas/                              # area petugas + scanner QR
├── assets/                               # css, js, vendor (bootstrap, chartjs, jsqr, sweetalert2)
├── database.sql, database/               # skema, skema supabase, data demo, migrasi
├── setup/create_admin.php                # CLI buat akun (hanya CLI)
├── tests/                                # e2e_test.php, hasil_kunjungan_test.php, qa_visual.mjs
├── uploads/bukti-jaminan/                # upload bukti jaminan (+ .htaccess)
└── docs/presentasi_tim/                  # dokumen presentasi (folder ini)
```

Pola **wrapper tipis**: `admin/kunjungan/index.php` hanya 8 baris — set `$AREA='admin'` lalu `require` modul bersama di `includes/modules/kunjungan/index.php`. Pola ini membuat admin dan petugas memakai kode halaman yang **sama**, hanya berbeda guard dan prefix URL.
