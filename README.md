# Sistem Informasi Buku Tamu Digital & Monitoring Kunjungan

Aplikasi web pengelolaan buku tamu digital dengan **check-in berbasis QR Code**,
monitoring kunjungan secara real-time, pengelolaan jaminan (KTP/dokumen),
**catatan hasil kunjungan**, pelaporan + cetak/ekspor CSV, serta log audit aktivitas.

- **Backend:** PHP Native (tanpa framework), PDO prepared statements, session, CSRF
- **Frontend:** Bootstrap 5, Chart.js, SweetAlert2, jsQR (scanner kamera)
- **QR:** `chillerlan/php-qrcode` (via Composer) — hanya **hash SHA-256** token yang disimpan
- **Database:** MySQL/MariaDB (default) — skema **Supabase/PostgreSQL** sudah disiapkan untuk migrasi
- **Antarmuka:** Bahasa Indonesia, desain profesional navy/putih, responsif

---

## 1. Prasyarat

- XAMPP dengan **Apache** + **MySQL/MariaDB** berjalan
- PHP **8.1+** (sudah termasuk di XAMPP)
- Composer (hanya diperlukan jika ingin install ulang dependensi QR)

> Library frontend (Bootstrap, Chart.js, jsQR, dll.) sudah di-*vendor* di `assets/vendor/`,
> jadi **tidak memerlukan koneksi internet**. Dependensi Composer (`vendor/chillerlan`)
> juga sudah ikut disertakan.

---

## 2. Instalasi

### a. Buat database & impor skema

Buka **phpMyAdmin** → buat database bernama `buku_tamu_digital`
(pilih collation `utf8mb4_general_ci`) → impor file **`database.sql`**
di root proyek. Skema ini berisi tabel + index + *seed* pengaturan, **tanpa**
baris pengguna (akun dibuat lewat CLI).

*(Opsional)* Impor `database/demo_data.sql` untuk memuat data contoh
(kunjungan, jaminan, tamu, mitra) agar tampilan dashboard/laporan langsung terisi.

> **Database lama (upgrade):** kolom `visits.hasil_kunjungan` ditambahkan lewat
> file migrasi aman `database/migrations/001_tambah_hasil_kunjungan.sql`
> (`ADD COLUMN IF NOT EXISTS`) — tidak menghapus kolom/data lama, tidak membuat
> tabel baru, dan kunjungan lama tetap tampil (catatan kosong ditampilkan `—`).

### b. Konfigurasi environment

```bash
cp .env.example .env
```

Edit `.env` bila perlu (default sudah cocok untuk XAMPP):

```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_NAME=buku_tamu_digital
DB_USER=root
DB_PASS=
APP_TIMEZONE=Asia/Jakarta
```

> Catatan: file `.env` hanya dibaca oleh PHP, bukan disajikan ke browser.
> Tentukan `BASE_URL` di `.env` bila aplikasi tidak diakses otomatis
> (mis. `BASE_URL=/buku_tamu_digital`).

### c. Buat akun admin

Buka terminal/CLI di folder proyek:

```bash
C:\xampp\php\php.exe setup/create_admin.php admin admin12345 "Administrator" admin
C:\xampp\php\php.exe setup/create_admin.php petugas petugas12345 "Petugas Resepsionis" petugas
```

Pola: `create_admin.php <username> <password> <nama_lengkap> <role>`
dengan `role` = `admin` | `petugas`. Password di-hash dengan `password_hash()` (bcrypt).

### d. Akses aplikasi

```
http://localhost/buku_tamu_digital/
```

**Akun demo** (dibuat lokal saat pengembangan — bukan bagian dari skema):

| Role    | Username   | Password      |
|---------|------------|---------------|
| admin   | `admin`    | `admin12345`  |
| petugas | `petugas`  | `petugas12345`|

---

## 3. Alur QR (aturan revisi mentor #1)

1. Admin menerbitkan **QR Code** untuk tamu terdaftar (`admin/qr/`).
   Token acak 256-bit dihasilkan server; **hanya hash SHA-256** yang disimpan di DB,
   token mentah ditampilkan **hanya satu kali** saat penerbitan.
2. Tamu menampilkan QR-nya. Petugas memindai lewat **kamera** (`petugas/qr/scanner.php`,
   jsQR) atau memasukkan token manual.
3. Hasil pindai diarahkan ke `kunjungan/tambah.php?token=...`.
   **Server memvalidasi ulang token** (status, masa berlaku, registrasi aktif) —
   identitas **tidak** dipercaya dari browser, melainkan diambil dari DB berdasarkan token.
4. QR kedaluwarsa, dicabut, atau milik registrasi non-aktif **ditolak server**.

Form kunjungan juga mendukung **check-in manual** dan **walk-in** (tamu baru tanpa QR).

## 4. Check-out & Jaminan (aturan revisi mentor #2)

`actions/checkout_process.php` berjalan dalam **transaksi DB** dengan penguncian baris
(`FOR UPDATE`):

- Check-out **ditolak** selama masih ada jaminan berstatus **aktif** untuk kunjungan tersebut.
- Validasi dilakukan **di server** (bukan sekadar menyembunyikan tombol di UI).
- Check-out ganda (kunjungan sudah `selesai`) **ditolak**.
- Durasi kunjungan dihitung otomatis dan setiap aksi dicatat ke **log audit**.

## 5. Hasil Kunjungan

Kolom **`visits.hasil_kunjungan`** (`TEXT`, boleh `NULL`) menyimpan catatan apa yang
terjadi setelah tamu menemui pegawai / menyelesaikan aktivitasnya.

- **Form check-in** (`kunjungan/tambah.php`) menyediakan textarea **opsional**
  — hasil **tidak diwajibkan** saat check-in karena aktivitas belum dilakukan.
- **Halaman detail kunjungan** menyediakan panel *Hasil Kunjungan* + tombol
  **Simpan Hasil** (`actions/kunjungan_process.php`, aksi `simpan_hasil`) untuk
  mengisi/memperbarui catatan kapan saja, termasuk setelah check-out.
- **Proses check-out** (`actions/checkout_process.php`) menerima textarea opsional;
  nilai kosong **tidak** menimpa catatan yang sudah tersimpan.
- Tampilan: kolom **Hasil Kunjungan** muncul **setelah kolom Tujuan** pada
  Semua Kunjungan (admin & petugas), riwayat di detail tamu, laporan (layar &
  cetak/PDF), dan ekspor CSV. Catatan panjang dirapikan maksimal 2 baris
  (CSS `.hasil-clamp` + tautan **Lihat Detail**) tanpa memotong data di database.
- Kunjungan lama / masih berlangsung tanpa catatan ditampilkan `—` atau
  **"Belum ada hasil kunjungan"** — bukan kesalahan sistem.
- Batas 4000 karakter, divalidasi di server, disimpan dengan prepared statement
  (PDO) dan hanya oleh pengguna yang sudah login (role guard `petugas`).

---

## 6. Laporan, Cetak & CSV (aturan revisi mentor #3)

`admin/laporan/`:

- **Cetak / Simpan PDF** → `window.print()` dengan gaya `@media print`
  (header instansi + periode + waktu cetak tampil, navigasi disembunyikan).
- **Ekspor CSV** → `actions/...` memakai **`fputcsv()`** native PHP,
  dengan **BOM UTF-8** (compat Excel), *download header*, filter tanggal/status,
  serta pengaman karakter formula (`=`,`+`,`-`,`@`) agar anti *CSV injection*.

---

## 7. Struktur Proyek

```
buku_tamu_digital/
├── config/            # koneksi DB (PDO mysql/pgsql), muat .env
├── includes/          # auth, csrf, fungsi bersama, header/sidebar/footer
│   └── modules/       # modul halaman dipakai bersama admin & petugas
├── admin/             # area admin (thin wrapper → modules)
│   ├── kunjungan/ tamu/ mitra/ qr/ laporan/ users/
├── petugas/           # area petugas (thin wrapper → modules)
│   └── qr/scanner.php # scanner kamera + token manual
├── actions/           # handler POST (login, CRUD, check-in/out, ekspor CSV, dll.)
├── assets/            # css, js, vendor (bootstrap, chart.js, jsqr, dll.)
├── database.sql       # skema MySQL/MariaDB (yang diimpor)
├── database/
│   ├── supabase_schema.sql  # skema PostgreSQL untuk migrasi Supabase
│   ├── demo_data.sql        # data contoh (opsional)
│   └── migrations/          # migrasi inkremental (aman dijalankan ulang)
├── setup/create_admin.php   # CLI pembuatan akun
├── tests/e2e_test.php       # suite E2E berbasis curl (48 asersi)
├── tests/hasil_kunjungan_test.php  # suite fitur Hasil Kunjungan (46 asersi)
└── .env / .env.example
```

**Keamanan yang diterapkan:** CSRF token di setiap POST, role guard (admin/petugas),
prepared statements (anti SQL injection), output escaping (anti XSS),
`password_hash`/`password_verify`, upload bukti jaminan di luar web-root publik,
token QR disimpan sebagai hash, dan audit log untuk setiap aksi penting.

---

## 8. Menjalankan Test E2E

```bash
C:\xampp\php\php.exe tests/e2e_test.php [baseURL] [adminUser] [adminPass] [petugasUser] [petugasPass]
```

Contoh (default sudah menunjuk ke `http://localhost/buku_tamu_digital`):

```bash
C:\xampp\php\php.exe tests/e2e_test.php
```

Suite ini mencakup login, CRUD, penerbitan QR + validasi token, penolakan
check-out berjaminan, perhitungan durasi, halaman laporan, ekspor CSV (BOM & header),
guard role, dsb. (48 asersi).

Untuk fitur **Hasil Kunjungan** tersedia suite terpisah:

```bash
php tests/hasil_kunjungan_test.php [baseURL] [adminUser] [adminPass] [petugasUser] [petugasPass]
```

46 asersi: struktur kolom, check-in tanpa hasil (NULL), simpan/perbarui hasil,
pengosongan, check-out (menyimpan & mempertahankan catatan), kolom pada tabel
admin/petugas, kunjungan lama (`—`), validasi 4000 karakter, CSRF, hak akses,
monitoring, riwayat, laporan, cetak & CSV — data uji dibersihkan otomatis.

---

## 9. Migrasi ke Supabase (PostgreSQL) — untuk nanti

Skema PostgreSQL lengkap (dengan trigger, index, identity) sudah disiapkan di
**`database/supabase_schema.sql`**. Langkah saat diminta berpindah:

1. Buka **Supabase → SQL Editor**, jalankan seluruh isi `database/supabase_schema.sql`.
2. Salin **connection string** (host `db.<project>.supabase.co`, port `5432`,
   database `postgres`, user `postgres`, password + *session pooler* bila perlu).
3. Di `php.ini` XAMPP, aktifkan ekstensi **`pdo_pgsql`**
   (`extension=pdo_pgsql`; `php_pdo_pgsql.dll` & `libpq.dll` sudah tersedia di `C:\xampp\php\ext`).
4. Set environment di `.env`:
   ```
   DB_DRIVER=pgsql
   DB_HOST=db.<project>.supabase.co
   DB_PORT=5432
   DB_NAME=postgres
   DB_USER=postgres
   DB_PASS=<password>
   ```
   Driver PDO sudah menangani `mysql` maupun `pgsql` di `config/database.php`.
5. Buat ulang akun admin via `setup/create_admin.php` (karena skema Supabase tanpa baris user).

---

## 10. Catatan

- Zona waktu disamakan antara PHP (`Asia/Jakarta`) dan sesi MySQL (`+07:00`)
  agar timestamp konsisten.
- Enum status memakai `VARCHAR` + `CHECK` (portabel ke PostgreSQL).
- Gambar QR hanya ditampilkan **sekali** (flash token di session), tidak bisa diambil ulang.
