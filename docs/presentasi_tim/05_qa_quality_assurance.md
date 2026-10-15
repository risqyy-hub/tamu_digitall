# BAB III.E — Materi Presentasi: QA (Quality Assurance / Testing)

**Tujuan presentasi QA:** menunjukkan **strategi pengujian**, membedakan mana test yang **benar-benar dijalankan**, mana yang **baru diusulkan**, serta melaporkan bug apa yang **benar-benar bisa direproduksi** — tanpa mengarang hasil.

**Perkiraan durasi presentasi QA:** ±10 menit.

---

## 1. Strategi pengujian

| Jenis | Sarana yang ada di project | Sifat |
|---|---|---|
| **End-to-end via HTTP** | `tests/e2e_test.php` (335 baris) — memakai curl + cookie jar, memverifikasi ke database langsung | otomatis, CLI |
| **Fokus fitur hasil kunjungan** | `tests/hasil_kunjungan_test.php` (287 baris) | otomatis, CLI |
| **Visual / UI** | `tests/qa_visual.mjs` — alur Playwright-style: screenshot login, dashboard, form kunjungan, laporan, scanner | semi-otomatis |
| **Manual (browser)** | dilakukan manusia mengikuti test case di bawah | manual |
| **Audit keamanan statis/dinamis** | pemeriksaan kode + HTTP read-only yang saya jalankan pada 14 Okt 2026 | manual + perintah |

**Cara kerja suite (dari kode):** fungsi `ok($nama, $kondisi)` menghitung PASS/GAGAL (`tests/e2e_test.php` baris 28–38), `req()` mengelola cookie sesi (baris 41–75), `csrf_of()` menarik token dari HTML (baris 77–83), dan ada fungsi pembantu untuk membaca `Location` redirect. Eksekusi diakhiri ringkasan + `exit($fail > 0 ? 1 : 0)` (baris 386–387).

**Cara menjalankan (sesuai `README.md` bag. 8):**

```bash
C:\xampp\php\php.exe tests/e2e_test.php
C:\xampp\php\php.exe tests/hasil_kunjungan_test.php
```

> **Peringatan untuk QA:** kedua suite ini **menulis data** ke database (membuat tamu/mitra/registrasi/QR/kunjungan uji, dan `e2e_test.php` **mengubah `settings.nama_instansi`** — baris 348–354, **tidak dikembalikan ke semula**; hanya password yang dikembalikan, baris 372–374). Jalankan hanya pada database demo/lokal, jangan pada data produksi. Karena aturan audit melarang perubahan database, **suite ini tidak saya jalankan** dalam audit ini.

---

## 2. Test case yang SUDAH dijalankan (dalam audit ini, 14 Okt 2026 — sifatnya read-only)

Metode: HTTP GET/POST tanpa mengubah data (tidak ada login, tidak ada insert).

| ID | Fitur | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Bukti/Status |
|---|---|---|---|---|---|
| TC-A01 | Proteksi halaman | Akses `index.php` tanpa sesi | `GET /index.php` | 302 → `login.php` | **PASS** (302, lokasi `…/login.php`) |
| TC-A02 | Guard admin | Akses `admin/dashboard.php` tanpa sesi | `GET /admin/dashboard.php` | 302 → `login.php` | **PASS** (302) |
| TC-A03 | Guard area petugas | Akses `petugas/dashboard.php` tanpa sesi | `GET /petugas/dashboard.php` | 302 → `login.php` | **PASS** (302) |
| TC-A04 | Guard laporan | Akses `admin/laporan/index.php` tanpa sesi | `GET …` | 302 → `login.php` | **PASS** (302) |
| TC-A05 | Guard manajemen user | Akses `admin/users/index.php` tanpa sesi | `GET …` | 302 → `login.php` | **PASS** (302) |
| TC-A06 | CSRF login | POST `actions/login_process.php` **tanpa** `csrf_token` | `POST username=x&password=y` | 403 | **PASS** (HTTP 403) |
| TC-A07 | Metode yang diizinkan | `GET` ke `actions/login_process.php` (seharusnya POST saja) | `GET …` | 405 | **PASS** (HTTP 405) |
| TC-A08 | Guard aksi CRUD | POST `actions/tamu_process.php` tanpa login | `POST action=tambah&nama=Tes` | 302 → `login.php` (tidak ada data masuk) | **PASS** (302) |
| TC-A09 | Guard check-out | `GET` ke `actions/checkout_process.php` (harus POST + login) | `GET …` | 302 → `login.php` | **PASS** (302) |
| TC-A10 | Aset lokal tersedia | Buka Bootstrap & jsQR lokal | `GET assets/vendor/…` | 200 | **PASS** (200; bukti app jalan tanpa internet) |
| TC-A11 | Pengungkapan `.env` | `GET /.env` | akses langsung | **Seharusnya 403/404** | **GAGAL → BUG-01** (HTTP 200) |
| TC-A12 | Pengungkapan skema & backup | `GET /database.sql`, `GET /backup_buku_tamu_terbaru.sql` | akses langsung | idealnya tidak publik | **TEMUAN → BUG-02** (200; berisi struktur + data) |
| TC-A13 | Tautan bukti jaminan | Cek URL yang dibuat halaman detail | `GET /assets/uploads/bukti-jaminan/…` | 200 bila file ada | **GAGAL → BUG-03** (404; folder `assets/uploads` tidak ada) |
| TC-A14 | Kerahasiaan folder upload | `GET /uploads/bukti-jaminan/README.txt` | tanpa login | idealnya butuh autentikasi | **TEMUAN → BUG-04** (200) |
| TC-A15 | Logout | `GET /logout.php` tanpa sesi | tanpa token CSRF | menurut komentar file: hanya POST + CSRF | **TEMUAN → BUG-05** (302 ke login — berfungsi tanpa POST/CSRF) |

> Semua baris di atas **dijalankan dan bisa diulang** dengan perintah `curl -s -o NUL -w "%{http_code} %{redirect_url}"` terhadap `http://localhost/buku_tamu_digital`. Tidak ada data yang diubah.

---

## 3. Test case dengan BUKTI tidak langsung (riwayat pengujian sudah ada, tetapi hasilnya tidak diarsipkan)

Bukti: file `backup_buku_tamu_terbaru.sql` (backup database) memuat `activity_logs` berisi deretan aksi khas suite test pada **13–14 Okt 2026**, misalnya pola berurutan dalam 1 detik: *login admin → tambah tamu → tambah mitra → registrasi → terbitkan QR → login petugas → check-in manual → check-in via QR → tambah jaminan → check-out DITOLAK → kembalikan jaminan → check-out sukses → unduh CSV → ubah pengaturan → ganti password → logout*, ditambah baris *"Check-in kunjungan … Tester Hasil Kunjungan"* dan sesi login dari **HeadlessChrome** (konsisten dengan `tests/qa_visual.mjs`).

| ID | Fitur | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Bukti/Status |
|---|---|---|---|---|---|
| TC-B01 | Alur end-to-end lengkap | jalankan `tests/e2e_test.php` | CLI, XAMPP aktif | semua asersi OK | **Riwayat tercatat** di `activity_logs` (13–14 Okt 2026). **Output/berkas hasil TIDAK ada di repo** → status: *pernah berjalan, bukti resmi perlu dijalankan ulang & disimpan* |
| TC-B02 | Fitur hasil kunjungan | jalankan `tests/hasil_kunjungan_test.php` | CLI | semua asersi OK | **Riwayat tercatat** (baris "Perbarui hasil kunjungan …", "Tester Hasil Kunjungan"). **Berkas hasil tidak diarsipkan** |
| TC-B03 | Visual screenshot | jalankan `tests/qa_visual.mjs` | runner Playwright/headless | screenshot `qa-*.png` | **Riwayat tercatat** (login HeadlessChrome di log). **File `qa-*.png` tidak ada di repo** → belum bisa ditunjukkan |
| TC-B04 | Cek status suite | baca kode test | `tests/e2e_test.php` mencakup 15 kelompok uji (baris 119–383) | 15 kelompok terdefinisi | **Terverifikasi dari kode** (jumlah asersi 48 sesuai `README.md` — belum diverifikasi ulang dengan menjalankan) |

**Kesimpulan untuk presentasi:** *"Suite test-nya ada dan riwayat pengujian terlihat di log aktivitas, tetapi hasil run belum kami arsipkan. Sebelum presentasi akan kami jalankan ulang dan simpan hasilnya."* — jangan mengatakan "semua PASS" tanpa berkas hasil.

---

## 4. Test case yang DIUSULKAN (belum dijalankan)

| ID | Fitur | Skenario | Langkah Pengujian | Hasil yang Diharapkan | Bukti/Status |
|---|---|---|---|---|---|
| TC-C01 | Login admin | Login benar/salah/nonaktif | Isi form login benar → dashboard admin; salah → alert merah; akun nonaktif → ditolak | sesuai `login_process.php` baris 19–39 | **Diisulkan** |
| TC-C02 | Login petugas | Login role petugas | login → `index.php` mengarah ke `petugas/dashboard.php` | dashboard petugas | **Diisulkan** |
| TC-C03 | Hak akses (in-app) | Petugas membuka `admin/users/index.php` saat sudah login | ketik URL langsung | **403 Akses Ditolak** (`includes/auth.php` baris 130–151) | **Diisulkan** (belum ada sesi login dalam audit) |
| TC-C04 | Session idle | Diam 2 jam di satu halaman lalu klik menu | tunggu / ubah `last_activity` | ter-redirect ke login (`AUTH_IDLE_TIMEOUT` 7200) | **Diisulkan** |
| TC-C05 | CRUD tamu | Tambah/ubah/hapus | isi form <3 karakter → ditolak; sukses → data masuk; hapus tamu ber-riwayat → ditolak | `tamu_process.php` baris 38–41, 121–129 | **Diisulkan** |
| TC-C06 | Validasi form user | username tidak sah / password <8 | submit form tambah user | ditolak dengan pesan (baris 29–36) | **Diisulkan** |
| TC-C07 | Check-in manual | pilih tamu + tujuan kosong | submit | ditolak: "Tujuan wajib diisi" (baris 86–89) | **Diisulkan** |
| TC-C08 | Check-in walk-in | tamu baru tanpa data | isi nama baru + detail | `visitors` & `visits` terisi dalam 1 transaksi | **Diisulkan** |
| TC-C09 | Check-in QR valid | pindai QR aktif | scanner → form → submit | identitas sesuai pemilik QR, `visits.qr_id` terisi | **Diisulkan** |
| TC-C10 | Check-in QR kedaluwarsa | token QR habis masa berlaku | kirim token | ditolak + opsi manual (`kunjungan_process.php` baris 137–139) | **Diisulkan** |
| TC-C11 | Manipulasi identitas | kirim `visitor_id` lain + token QR sah | POST manual (DevTools/curl) | tetap memakai pemilik QR | **Diisulkan** (di `e2e_test.php` baris 252–262 ada ujiannya) |
| TC-C12 | Check-out dengan jaminan | check-out saat jaminan `aktif` | klik Check-out | ditolak, status tetap `berlangsung` | **Diisulkan** |
| TC-C13 | Check-out ganda | check-out kunjungan `selesai` | POST ulang | ditolak (baris 41–46) | **Diisulkan** |
| TC-C14 | Pengembalian jaminan | kembalikan jaminan terakhir | klik Kembalikan | pesan "kunjungan siap di-check-out" (`jaminan_process.php` baris 121–125) | **Diisulkan** |
| TC-C15 | Upload bukti | upload >2MB & file `.exe` | form tambah jaminan | ditolak: ukuran / format (fungsi `upload_bukti`) | **Diisulkan** |
| TC-C16 | Hapus jaminan oleh petugas | petugas mencoba hapus | POST `action=hapus` | ditolak "Hanya admin…" (baris 131–134) | **Diisulkan** |
| TC-C17 | Pencarian & filter | cari kata kunci, filter status & tanggal | form filter | tabel terfilter, pagination muncul bila >15 baris | **Diisulkan** |
| TC-C18 | Laporan & cetak | filter periode lalu Ctrl+P | buka laporan | header instansi/periode tercetak, navigasi hilang (`@media print`) | **Diisulkan** |
| TC-C19 | Ekspor CSV | unduh CSV lalu buka Excel | klik Ekspor CSV | ada BOM, tidak berantakan, sel `=`/`-` dinetralkan | **Diisulkan** (asersi BOM ada di `e2e_test.php` baris 333–335) |
| TC-C20 | Responsivitas | layar 390px / 768px / 1366px | ubah ukuran jendela | sidebar jadi offcanvas, tabel bisa digeser, tidak ada teks terpotong | **Diisulkan** |
| TC-C21 | Scanner di HP nyata | buka `petugas/qr/scanner.php` di HP via `http://localhost` / HTTPS | izinkan kamera → pindai | QR terbaca → pindah ke form | **Diisulkan** (kamera hanya diizinkan di konteks aman) |
| TC-C22 | Konfirmasi aksi berbahaya | hapus data / kembalikan jaminan | klik tombol | dialog SweetAlert2 muncul, batal = tidak ada perubahan | **Diisulkan** |
| TC-C23 | Notifikasi | check-in saat admin login | lihat lonceng | badge bertambah, "tandai semua" mengosongkan | **Diisulkan** |
| TC-C24 | Migrasi PostgreSQL | jalankan `database/supabase_schema.sql` + `DB_DRIVER=pgsql` | set `.env`, buka aplikasi | aplikasi berjalan di Supabase | **Diisulkan — belum pernah dibuktikan** |

---

## 5. Bug yang ditemukan dan BISA direproduksi

### BUG-01 — File `.env` (berisi kredensial) bisa diunduh publik — **TINGGI**
- **Langkah:** `curl -s -o /dev/null -w "%{http_code}" http://localhost/buku_tamu_digital/.env`
- **Hasil:** `200` (ukuran 923 byte).
- **Dampak:** siapa pun yang bisa mengakses URL ini membaca kredensial database.
- **Penyebab:** tidak ada `.htaccess` yang menolak akses file `.env`; `.gitignore` hanya melindungi Git, bukan browser.
- **Saran perbaikan (untuk BE):** buat `.htaccess` root yang menolak `^\.env`, atau letakkan `.env` di luar web root.
- **Dijamin tidak mengarang:** hasil dapat diulang kapan saja.

### BUG-02 — Skema & backup database bisa diunduh publik — **SEDANG**
- **Langkah:** `GET /database.sql` (13.548 byte), `GET /backup_buku_tamu_terbaru.sql` (59.367 byte), `GET /database/supabase_schema.sql`.
- **Hasil:** `200` untuk ketiganya.
- **Dampak:** `backup_buku_tamu_terbaru.sql` berisi **data nyata** (tabel `visitors`, `visits`, `users` berisi hash password, log aktivitas) — penyerang mendapat struktur + data tanpa perlu login. Selain itu file backup tersebut statusnya **untracked** (belum masuk Git) — rawan ter-commit tanpa sengaja.
- **Saran:** pindahkan backup keluar web root, tambahkan aturan tolak `*.sql`.

### BUG-03 — Tautan bukti jaminan mengarah ke URL yang salah (404) — **SEDANG**
- **Bukti kode:** penyimpanan file: `APP_ROOT . '/uploads/bukti-jaminan'` (`includes/functions.php` baris 333); tautan tampilan: `asset('uploads/bukti-jaminan/' . $g['bukti_file'])` (`includes/modules/kunjungan/detail.php` baris 170) → `asset()` menambahkan prefiks `assets/` (`includes/functions.php` baris 33–36).
- **Hasil:** URL menjadi `…/assets/uploads/bukti-jaminan/…` sedangkan folder `assets/uploads` **tidak ada** (`Test-Path` = false) → `404`.
- **Dampak:** petugas tidak bisa membuka foto bukti jaminan dari halaman detail.
- **Saran:** ganti menjadi `url('uploads/bukti-jaminan/' . …)` **atau** pindahkan folder upload ke `assets/uploads/`, dan tambahkan aturan autentikasi (lihat BUG-04).

### BUG-04 — Folder upload masih di dalam web root, bertentangan dengan klaim README — **SEDANG**
- **Bukti:** `GET /uploads/bukti-jaminan/README.txt` → `200` tanpa login; `README.txt` di folder itu menyatakan *"File disimpan di luar web root publik (tidak bisa diakses langsung lewat browser)"*.
- **Dampak:** berkas bukti (foto KTP/dokumen) dapat diakses publik bila nama filenya diketahui/ditebak (namanya memang dibuat acak — `bukti-<timestamp>-<acak>.jpg`, `includes/functions.php` baris 338, sehingga sulit ditebak, tetapi tetap tanpa autentikasi).
- **Catatan:** `.htaccess` di folder upload sudah menolak eksekusi script (`uploads/bukti-jaminan/.htaccess`) — itu sudah benar.

### BUG-05 — `logout.php` tidak memverifikasi metode/CSRF (berbeda dengan komentarnya) — **RENDAH**
- **Bukti kode:** komentar baris 4 menulis *"Proses keluar - hanya menerima POST + token CSRF"*, tetapi **tidak ada** `require_post()`/`verify_csrf()` di `logout.php`.
- **Hasil:** `GET /logout.php` → `302` ke `login.php` (dibuktikan dalam audit).
- **Dampak:** pihak ketiga bisa memaksa logout pengguna lewat gambar/link tersembunyi (`<img src="…/logout.php">`). Tidak kehilangan data.

### BUG-06 — Efek samping suite test: pengaturan aplikasi berubah permanen — **RENDAH (proses)**
- **Bukti:** `tests/e2e_test.php` baris 348–354 menyimpan `nama_instansi = 'Kantor Pusat Contoh'` (dan jam tutup, `qr_default_days`) **tanpa dikembalikan**; password admin memang dikembalikan (baris 372–374).
- **Dampak:** setelah test dijalankan, tampilan nama instansi di login/header/laporan berubah. Saat ini nilai di database memang 'Kantor Pusat Contoh' (terlihat di backup).
- **Saran:** QA harus mengembalikan `settings` seperti semula setelah menjalankan suite, atau suite dibuat men-simpan nilai awal.

---

## 6. Risiko / bug yang BELUM diperiksa (jangan diklaim aman)

| ID | Risiko | Alasan belum diperiksa | Cara memeriksa |
|---|---|---|---|
| R-01 | Check-out ganda saat 2 petugas bersamaan | butuh 2 sesi + timing bersamaan | jalankan 2 POST `checkout_process.php` hampir bersamaan; pastikan hanya 1 yang sukses (uji `FOR UPDATE`) |
| R-02 | Kamera QR di HP nyata | butuh perangkat fisik | buka `petugas/qr/scanner.php` lewat `https://` atau `localhost` di HP |
| R-03 | Cetak laporan di berbagai browser | butuh pratinjau print nyata | Ctrl+P di Chrome & Edge, periksa header & `.no-print` |
| R-04 | Buka CSV di Excel Windows/LibreOffice lokal | butuh aplikasi spreadsheet | unduh CSV, buka, periksa BOM & karakter Indonesia |
| R-05 | Idle timeout 2 jam | butuh penungguan 2 jam | ubah sementara `AUTH_IDLE_TIMEOUT` **hanya di lingkungan uji** (jangan di repo presentasi) |
| R-06 | Migrasi ke PostgreSQL/Supabase | belum pernah dijalankan | ikuti `README.md` bag. 9 pada database uji |
| R-07 | Batas `LIMIT 5000` pada ekspor | butuh data >5000 baris | buat data uji banyak di database demo |
| R-08 | Pesan error membocorkan info saat `APP_DEBUG=1` | `.env` audit memakai `APP_DEBUG=1` | pastikan `APP_DEBUG` mati saat demo/deploy |
| R-09 | Akses file statis lain (mis. `composer.lock`, `.env.example`) | belum dicek satu per satu | audit enumerasi file secara manual |

---

## 7. Ringkasan status untuk slide QA

- **Dijalankan & lulus (audit read-only):** 10 test case (TC-A01–A10) — guard halaman, CSRF, metode, aset lokal.
- **Dijalankan & GAGAL / temuan:** 5 test case (TC-A11–A15) → BUG-01 s.d. BUG-05.
- **Ada riwayat pengujian, hasil belum diarsipkan:** 4 butir (TC-B01–B04).
- **Diusulkan, belum dijalankan:** 24 test case (TC-C01–C24).
- **Risiko belum diperiksa:** 9 butir (R-01–R-09).

**Kalimat penutup yang jujur:** "Kami tidak menyetujui pengujian yang semua berstatus PASS. Yang sudah kami buktikan hari ini adalah pengujian keamanan dasar; pengujian fungsional lengkap sudah tersedianya skripnya, tinggal dijalankan ulang dan diarsipkan hasilnya sebelum hari-H."

---

## 8. Pengecekan ulang 15 Oktober 2026 (setelah tim melanjutkan pengembangan)

Karena project terus berubah setelah audit 14 Okt (commit baru `1fc2f21 "excel"` oleh `Bintangpa` + banyak perubahan yang **belum di-commit**), saya melakukan pengecekan ulang ringkas. **Ini temuan baru — bukan bagian dari audit 14 Okt.**

### 8.1 Yang berubah sejak audit

| Perubahan | Bukti |
|---|---|
| Git kini **3 commit, 2 penulis** (`risqy`, `Bintangpa`) | `git log` |
| Ekspor Excel ditambahkan | `admin/laporan/ekspor_excel.php`; tes baru di `tests/e2e_test.php` bag. 13 ("Laporan, Cetak, CSV & Excel") |
| **File `.htaccess` baru** di root (belum di-commit, `git status` = `??`) | menolak `^\.env` dan file berawalan titik, `Options -Indexes` |
| **Suite test baru** `tests/revisi_test.php` (537 baris, **78 asersi**, belum di-commit) | menguji sidebar, jaminan di Kunjungan Aktif, proteksi `.env`, kolom identitas, penguncian hasil kunjungan |
| Banyak revisi belum di-commit di `actions/`, `includes/modules/`, `includes/sidebar.php`, `assets/css/style.css` | `git diff --stat` |

### 8.2 Status bug setelah pengecekan ulang (HTTP read-only, 15 Okt)

| Bug | Status 14 Okt | Status 15 Okt | Keterangan |
|---|---|---|---|
| **BUG-01** `.env` publik | 200 | **403 — SEMENTARA DITANGANI** | ditolak oleh `.htaccess` baru. **Catatan:** `.htaccess` itu sendiri masih **uncommitted** — bila tidak di-commit, perbaikan bisa hilang. |
| **BUG-02** `database.sql` & backup publik | 200 | **MASIH 200** | `GET /database.sql` dan `GET /backup_buku_tamu_terbaru.sql` tetap terbuka. `.htaccess` baru hanya memblokir file berawalan titik, bukan `*.sql`. |
| **BUG-03** tautan bukti 404 | `asset('uploads/…')` di `detail.php` baris 170 | **MASIH ADA** | kode yang sama kini ada di **`includes/modules/kunjungan/detail.php` baris 186** (barisnya bergeser karena revisi). |
| **BUG-04** upload di web root | `README.txt` 200 | **MASIH 200** | `GET /uploads/bukti-jaminan/README.txt` → 200. |
| **BUG-05** `logout.php` tanpa POST/CSRF | GET → 302 | **MASIH ADA** | `logout.php` tidak berubah; tidak ada `require_post()`/`verify_csrf()`. |
| **BUG-06** suite mengubah `settings` permanen | `e2e_test.php` baris 348–354 | **MASIH ADA** | kini di `tests/e2e_test.php` bag. 14 (POST `settings_process.php` dengan `'Kantor Pusat Contoh'`, disusul verifikasi `ok('pengaturan tersimpan…')`) — nilai **tetap tidak dikembalikan**; hanya password yang dikembalikan. |

### 8.3 Konsekuensi untuk presentasi

- **Slide QA** boleh menyebut BUG-01 sebagai *"sudah ditambal sementara lewat `.htaccess` (perlu di-commit & diuji ulang)"* — jangan klaim "selesai" selama file-nya belum masuk Git.
- **BUG-02 s/d BUG-06** tetap disampaikan sebagai temuan terbuka.
- Seluruh **nomor baris** di dokumen ini akurat untuk kondisi commit `e0b5cc1` (14 Okt). Untuk file yang kini berubah (`includes/modules/kunjungan/detail.php`, `tests/e2e_test.php`, `actions/*`), **cari teksnya**, jangan mengandalkan angka baris lama.
- Test case TC-A11 pada tabel bagian 3 **sudah berganti hasil** (14 Okt: GAGAL/200 → 15 Okt: 403). Saat presentasi, gunakan status 15 Okt dan jelaskan sebabnya (file `.htaccess` baru).
