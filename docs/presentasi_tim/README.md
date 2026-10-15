# Dokumen Materi Presentasi Tim

Folder ini berisi hasil **audit kode** project **Sistem Informasi Buku Tamu Digital dan Monitoring Kunjungan Berbasis Web**, lalu diterjemahkan menjadi materi presentasi yang siap dipakai di depan guru/penguji.

Semua isi dokumen ditulis berdasarkan **kode yang benar-benar ada** di project (per 14 Oktober 2026). Setiap penjelasan selalu menyebut file, path relatif, dan bagian kode penting agar bisa langsung dibuka saat presentasi.

> **Aturan penting**
> - Dokumen ini **tidak** memuat password, token, maupun connection string. Untuk akun demo, ikuti `README.md` root project.
> - Jika ada fitur yang belum ada di kode, ditulis **"Belum ditemukan dalam kode yang diperiksa"** — bukan ditebak.
> - Belum ada dokumen yang menyatakan pembagian kerja per nama orang. Karena itu pembagian di BAB II adalah **rekomendasi tanggung jawab role** yang wajib dikonfirmasi ulang oleh tim.

> **Catatan pembaruan 15 Oktober 2026 (setelah audit)**
> Tim ternyata **terus mengembangkan project** setelah audit 14 Okt. Kondisi terbaru yang sudah saya periksa ulang:
> - Git kini punya **3 commit oleh 2 penulis** (`risqy`: `firsttt`, `update tampilan dan fitur hasil kunjungan`; `Bintangpa`: `excel`, 15 Okt — menambah ekspor Excel & mengubah `tests/e2e_test.php`). Artinya klaim "hanya 1 penulis" di dokumen lama **tidak lagi berlaku** — kontribusi per orang tetap perlu dikonfirmasi langsung ke tim.
> - Ada **banyak perubahan yang belum di-commit** (kerja revisi di `actions/`, `includes/modules/`, `includes/sidebar.php`, `assets/css/style.css`, dll.), ditambah 2 file baru yang belum terdaftar: **`.htaccess`** dan **`tests/revisi_test.php`** (537 baris, 78 asersi).
> - **BUG-01 SEMENTARA PERBAIKI:** file `.htaccess` baru melarang akses ke `.env` → `GET /.env` kini **403** (sebelumnya 200). BUG-02, BUG-03, BUG-04, BUG-05, BUG-06 masih saya temukan saat pengecekan ulang 15 Okt.
> - Karena banyak file berubah, **sebagian nomor baris pada dokumen ini bisa bergeser**. Nomor baris tetap akurat untuk kondisi commit `e0b5cc1` (14 Okt). Sebelum presentasi, verifikasi ulang dengan mencari **nama fungsi/teks** yang disebut, bukan hanya angka barisnya.
> - Rincian pengecekan ulang ada di `05_qa_quality_assurance.md` bagian "8. Pengecekan ulang 15 Oktober 2026".

## Daftar isi

| File | Isi | Dipakai oleh |
|---|---|---|
| `00_gambaran_project.md` | Nama project, latar belakang, masalah, tujuan, ruang lingkup, teknologi nyata, ringkasan fitur terimplementasi. | Semua anggota |
| `01_pm_project_manager.md` | Materi PM: latar belakang, perencanaan, pembagian tugas, prioritas fitur, bukti manajemen proyek yang tersedia & yang harus dilengkapi. | PM |
| `02_be_backend_engineer.md` | Materi BE: arsitektur backend, koneksi PDO, struktur database, autentikasi, CRUD, check-in/out, QR, jaminan, laporan, keamanan — lengkap dengan format "file → kode → alur → demo → pertanyaan penguji". | BE |
| `03_fe_frontend_engineer.md` | Materi FE: layout, login, dashboard, sidebar, form, tabel, monitoring, scanner, CSS/JS/Bootstrap/Chart.js, responsivitas. | FE |
| `04_analyst_system_analyst.md` | Materi Analyst: permasalahan sistem lama, aktor & hak akses, kebutuhan fungsional/nonfungsional, alur bisnis, relasi database, rekomendasi diagram. | Analyst |
| `05_qa_quality_assurance.md` | Materi QA: strategi pengujian, tabel test case (dijalankan vs diusulkan), bug yang tereproduksi, risiko yang belum diperiksa. | QA |
| `06_alur_demo_aplikasi.md` | Urutan demo dari membuka aplikasi sampai menunjukkan kode backend & struktur database. | Semua (pemimpin demo) |
| `07_pertanyaan_pengujian.md` | Kumpulan pertanyaan teknis & nonteknis beserta jawaban sesuai implementasi. | Semua |
| `08_checklist_presentasi.md` | Checklist peran: file yang dipahami, halaman/screenshot, demo, pertanyaan, kekurangan dokumentasi. | Semua |

## Cara menggunakan saat presentasi

1. Baca `00_gambaran_project.md` bersama-sama supaya istilah dan urutan fitur sama.
2. Tiap anggota memegang subbabnya sendiri (BAB III di masing-masing file `01`–`05`).
3. Latihan demo mengikuti `06_alur_demo_aplikasi.md` (jangan menampilkan fitur yang belum ada).
4. Latihan tanya-jawab memakai `07_pertanyaan_pengujian.md`.
5. Sebelum hari-H, centang `08_checklist_presentasi.md`.

## Ringkasan hasil audit

- **Ditemukan & dipelajari:** ±100 file PHP di luar `vendor/`, 10 tabel database (`database.sql`), skema Supabase (`database/supabase_schema.sql`), 2 suite test CLI (`tests/e2e_test.php`, `tests/hasil_kunjungan_test.php`), 1 skrip visual (`tests/qa_visual.mjs`), aset CSS/JS lokal (`assets/css/style.css`, `assets/js/app.js`, `assets/js/scanner.js`), dan `README.md`.
- **Tidak dibaca/diabaikan:** `vendor/` (dependency Composer), isi `.env` (rahasia — hanya dipastikan ada), isi credential apa pun.
- **Tidak ada perubahan apa pun** pada file aplikasi maupun database selama audit (hanya pembacaan + HTTP GET/POST read-only).
- **Belum ditemukan:** dokumen ERD/use case/activity diagram, dokumen jadwal/sprint, laporan hasil test bertanda tangan, dan bukti kontribusi per nama anggota.
- **Pengujian audit (hanya-baca)** yang dijalankan 14 Okt 2026 tercatat di `05_qa_quality_assurance.md`, termasuk **6 temuan** yang bisa direproduksi (BUG-01 s/d BUG-06).
