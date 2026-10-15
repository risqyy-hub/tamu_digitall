# 06 — Alur Demo Aplikasi

| | |
|---|---|
| **Nama materi** | Alur Demo Aplikasi (urutan praktik) |
| **Role** | Semua anggota (pemimpin demo: PM; dukungan teknis: BE) |
| **File rujukan** | Semua file yang disebut di tiap langkah |
| **Durasi demo** | 12–15 menit |

> **Aturan demo**
> 1. Hanya tampilkan fitur yang **benar-benar berfungsi** sesuai hasil audit. Fitur yang belum ada **tidak usah didemokan** (lihat bagian 5).
> 2. Demo memakai aplikasi yang sudah jalan di `http://localhost/buku_tamu_digital` (Apache + MySQL aktif).
> 3. Jangan menampilkan isi `.env`, password, maupun hash bcrypt di layar.
> 4. Setiap langkah mencantumkan **file & bagian kode** supaya bisa langsung dibuka saat penguji bertanya.

---

## 1. Persiapan sebelum demo (di luar panggung)

| Cek | Cara |
|---|---|
| Apache & MySQL menyala | XAMPP Control Panel |
| Database terisi | Buka phpMyAdmin → database sesuai `config/database.php` |
| URL aplikasi | `http://localhost/buku_tamu_digital` |
| Akun login | Ikuti `README.md` root project (jangan ditulis ulang di sini) |
| Browser mode normal | Jangan Incognito (agar sesi & CSRF bekerja normal) |

---

## 2. Urutan demo (12–15 menit)

### Langkah 1 — Halaman login (±1 menit)

| | |
|---|---|
| **Aksi** | Buka `http://localhost/buku_tamu_digital` → muncul `login.php`. |
| **File** | `login.php` |
| **Yang ditunjukkan** | Form login admin & petugas; nama instansi diambil dari tabel `settings` via `setting('nama_instansi')` (baris 20). |
| **Kata pengantar** | "Ini halaman awal. Nama instansinya bukan hard-coded, tapi diambil dari database lewat fungsi `setting()`." |

### Langkah 2 — Login admin & guard role (±1,5 menit)

| | |
|---|---|
| **Aksi** | Login memakai akun admin (per `README.md` root). |
| **File** | `actions/login_process.php` (verifikasi password + `password_verify`), `includes/auth.php` (guard, `AUTH_IDLE_TIMEOUT = 7200` baris 11), `index.php` (redirect per role). |
| **Yang ditunjukkan** | Berhasil login → langsung ke dashboard admin. Coba buka `admin/...` tanpa login → **302 redirect** ke login (bukti guard). |
| **Kata pengantar** | "Setelah login, setiap halaman admin dicek dulu di `includes/auth.php`. Tanpa sesi, user tidak bisa masuk — ini sudah kami uji (dokumen QA, bagian pengujian hanya-baca)." |

### Langkah 3 — Dashboard admin (±1,5 menit)

| | |
|---|---|
| **Aksi** | Amati kartu statistik & grafik di `admin/dashboard.php`. |
| **File** | `admin/dashboard.php`, `assets/js/app.js` (inialisasi Chart.js). |
| **Yang ditunjukkan** | Ringkasan jumlah kunjungan/tamu/jaminan; grafik kunjungan. |
| **Kata pengantar** | "Dashboard menampilkan ringkasan dari database, bukan data statis." |

### Langkah 4 — Data master: tamu & pengguna (±2 menit)

| | |
|---|---|
| **Aksi** | Buka menu **Tamu** (tambah data tamu baru), lalu menu **Pengguna** (tampilkan daftar user & role). |
| **File** | `admin/tamu/index.php`, `admin/tamu/tambah.php`, `actions/tamu_process.php`, `admin/users/index.php`, `actions/user_process.php`. |
| **Yang ditunjukkan** | Form tambah tamu tersimpan → muncul di tabel. Daftar pengguna memperlihatkan peran admin/petugas. |
| **Kata pengantar** | "CRUD data master memakai pola sama: halaman di folder admin, proses di folder `actions/`, proteksi CSRF dari `includes/csrf.php`." |

### Langkah 5 — QR code kunjungan (±2 menit)

| | |
|---|---|
| **Aksi** | Buka menu **QR** → tambah QR kunjungan → tampilkan/tampilkan QR-nya. |
| **File** | `admin/qr/index.php`, `admin/qr/tambah.php`, `admin/qr/tampil.php`, `actions/qr_process.php`. |
| **Yang ditunjukkan** | QR baru terbentuk dan bisa ditampilkan. |
| **Kata pengantar** | "Setiap QR terhubung ke data kunjungan. Scanner petugas akan memakai QR ini — kita lihat di langkah berikutnya." |

### Langkah 6 — Login petugas di tab/browser kedua (±1 menit)

| | |
|---|---|
| **Aksi** | Buka tab baru → login memakai akun **petugas**. |
| **File** | `petugas/dashboard.php`, `includes/sidebar.php` (menu menyesuaikan role). |
| **Yang ditunjukkan** | Menu petugas berbeda dari menu admin (hak akses terbatas). |
| **Kata pengantar** | "Perhatikan menunya lebih sedikit — ini pembatasan hak akses di `includes/sidebar.php` dan guard di `includes/auth.php`." |

### Langkah 7 — Check-in tamu (scan QR / input manual) (±2,5 menit)

| | |
|---|---|
| **Aksi** | Buka menu kunjungan petugas → tambah kunjungan. Jika memungkinkan, tunjukkan halaman **scanner** (`petugas/qr/scanner.php`, memakai `assets/js/scanner.js`). Jika kamera tidak tersedia di ruang demo, pakai **input manual** sebagai fallback. |
| **File** | `petugas/kunjungan/tambah.php`, `includes/modules/kunjungan/tambah.php` (form dipakai bersama), `actions/kunjungan_process.php`, `petugas/qr/scanner.php`, `assets/js/scanner.js`. |
| **Yang ditunjukkan** | Kunjungan baru tercatat dengan status **check-in** dan waktu kunjungan. |
| **Kata pengantar** | "Proses check-in ada di `actions/kunjungan_process.php`. Sama persis dipakai admin lewat `admin/kunjungan/tambah.php` — modulnya dibagi di `includes/modules/kunjungan/` supaya tidak duplikasi." |

### Langkah 8 — Monitoring kunjungan aktif (±1 menit)

| | |
|---|---|
| **Aksi** | Buka **Kunjungan aktif** dari akun petugas atau admin. |
| **File** | `admin/kunjungan/aktif.php`, `petugas/kunjungan/aktif.php`, `includes/modules/kunjungan/aktif.php`. |
| **Yang ditunjukkan** | Daftar tamu yang sedang berkunjung (belum check-out). |
| **Kata pengantar** | "Ini halaman monitoring real-time daftar kunjungan yang sedang berlangsung." |

### Langkah 9 — Jaminan (optional, ±1,5 menit)

| | |
|---|---|
| **Aksi** | Tambah data jaminan untuk tamu (misalnya KTP/SKTM yang dititipkan), lalu buka detail kunjungan untuk melihat bukti. |
| **File** | `admin/jaminan/tambah.php`, `actions/jaminan_process.php`, `includes/functions.php` → `upload_bukti()` (deklarasi baris 306, penentuan folder baris 333), `includes/modules/kunjungan/detail.php` (tautan bukti: baris 170 saat audit, kini **baris 186** setelah revisi tim). |
| **Yang ditunjukkan** | Bukti jaminan ter-upload dan muncul di detail kunjungan. |
| **Kata pengantar** | "Upload bukti ditangani `upload_bukti()` di `includes/functions.php`. **Catatan jujur:** di dokumen QA kami menemukan bug tautan bukti memakai helper `asset()` sehingga path-nya 404 (BUG-03). Kalau sempat ditunjukkan, sampaikan sebagai contoh bug yang kami temukan sendiri." |

### Langkah 10 — Check-out (±1,5 menit)

| | |
|---|---|
| **Aksi** | Dari daftar kunjungan aktif, lakukan check-out pada kunjungan dari langkah 7. |
| **File** | `actions/checkout_process.php`, `includes/modules/kunjungan/detail.php` / `aktif.php` (tombol check-out). |
| **Yang ditunjukkan** | Status berubah menjadi selesai, keluar dari daftar kunjungan aktif, waktu keluar tercatat. |
| **Kata pengantar** | "Check-out ditangani file terpisah, `actions/checkout_process.php`, supaya alurnya jelas: check-in dan check-out adalah dua proses berbeda." |

### Langkah 11 — Laporan, cetak, dan ekspor (±2 menit)

| | |
|---|---|
| **Aksi** | Buka **Laporan** → pilih rentang tanggal → lihat hasil; tunjukkan tombol **cetak** dan **ekspor CSV**. |
| **File** | `admin/laporan/index.php`, `includes/laporan_helper.php`, `admin/laporan/ekspor_csv.php`, `admin/laporan/ekspor_excel.php`. |
| **Yang ditunjukkan** | Rekap kunjungan sesuai filter; unduhan CSV (jangan lakukan unduhan berulang saat demo). |
| **Kata pengantar** | "Filter laporan dan rekap dibantu `includes/laporan_helper.php`. Ekspor memakai `send_csv()` dari `includes/functions.php`." |

### Langkah 12 — Riwayat aktivitas (±1 menit)

| | |
|---|---|
| **Aksi** | Buka **Aktivitas** (admin). |
| **File** | `admin/aktivitas.php`, `includes/functions.php` → `log_aktivitas()`. |
| **Yang ditunjukkan** | Log tindakan pengguna (login, tambah kunjungan, dll). |
| **Kata pengantar** | "Setiap aksi penting dicatat lewat `log_aktivitas()` — ini yang membuat sistem mudah diaudit." |

### Langkah 13 — Tutup dengan kode & database (±2 menit)

| | |
|---|---|
| **Aksi** | Buka kode berikut di editor, tunjukkan singkat: |
| **File 1** | `config/database.php` → koneksi PDO (singleton) + loader `.env`. |
| **File 2** | `includes/auth.php` → `require_login()`, `require_role()`. |
| **File 3** | `includes/csrf.php` → `verify_csrf()` / `require_post()`. |
| **File 4** | `database.sql` → tabel, primary key, foreign key, CHECK constraint. |
| **Kata penutup** | "Itu alur lengkapnya: dari login, proses bisnis check-in/out, sampai laporan — semuanya terhubung ke 10 tabel dengan relasi foreign key." |

---

## 3. Peta cepat: aksi → file

| Aksi di demo | File pemroses |
|---|---|
| Login | `actions/login_process.php` |
| Logout | `logout.php` |
| Tambah/edit tamu | `actions/tamu_process.php` |
| Tambah pengguna | `actions/user_process.php` |
| Tambah QR kunjungan | `actions/qr_process.php` |
| Check-in kunjungan | `actions/kunjungan_process.php` |
| Check-out | `actions/checkout_process.php` |
| Jaminan | `actions/jaminan_process.php` |
| Laporan & ekspor | `admin/laporan/index.php`, `admin/laporan/ekspor_csv.php` |

---

## 4. Jika ditanya "buktinya di mana?" saat demo

| Klaim | Buka file ini |
|---|---|
| "Tanpa login tidak bisa masuk" | `includes/auth.php` (guard) — dan bukti pengujian di `docs/presentasi_tim/05_qa_quality_assurance.md` |
| "Ada proteksi CSRF" | `includes/csrf.php` — pengujian POST tanpa token → 403 tercatat di dokumen QA |
| "Data tersimpan di database" | `config/database.php`, `database.sql` |
| "Sudah ada pengujian" | `tests/e2e_test.php`, `tests/hasil_kunjungan_test.php` (dijelaskan di dokumen QA) |

---

## 5. Fitur yang TIDAK didemokan (belum ada / bermasalah)

| Kondisi | Keterangan |
|---|---|
| Diagram (ERD, use case, activity) | **Belum ditemukan dalam kode yang diperiksa.** Jangan didemokan; sebutkan sebagai rekomendasi pengembangan dokumen. |
| Tautan bukti jaminan di detail kunjungan | Ada **BUG-03** (path `assets/uploads` 404). Boleh disebut sebagai contoh temuan QA, jangan dijanjikan berfungsi. |
| Menampilkan isi `.env` / file SQL publik | **BUG-01** (`.env` publik) kini **sementara ditangani** oleh file `.htaccess` baru (15 Okt, `GET /.env` → 403) — boleh disinggung sebagai contoh temuan yang sedang diperbaiki. **BUG-02** (`database.sql` & backup SQL) **masih 200** — jangan didemokan sebagai fitur, cukup diakui sebagai temuan terbuka. |
| Pengujian otomatis suite penuh | `tests/e2e_test.php` menulis ke database (lihat **BUG-06**). Jangan dijalankan saat demo; cukup sebutkan keberadaannya. Ada juga suite baru **`tests/revisi_test.php`** (78 asersi, menguji revisi sidebar/jaminan/proteksi `.env`) — cukup disebut, jangan dijalankan di depan penguji tanpa persiapan data. |

---

## 6. Latihan demo

1. Jalankan urutan langkah 1–13 sekali penuh, catat waktu tiap langkah agar total tetap 12–15 menit.
2. Siapkan **fallback** bila kamera scanner tidak bisa dipakai di ruang demo → langsung pakai input manual kunjungan.
3. Pastikan data yang dibuat saat latihan tidak mengganggu data presentasi (hapus lewat aplikasi, bukan lewat SQL mentah).
