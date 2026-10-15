# 08 — Checklist Presentasi

| | |
|---|---|
| **Nama materi** | Checklist kesiapan presentasi per role |
| **Role** | Semua anggota |
| **File rujukan** | Semua dokumen di folder ini + file aplikasi yang disebut |
| **Durasi latihan checklist** | 30–45 menit (sesi terakhir sebelum hari-H) |

> **Cara pakai:** Centang setiap item setelah benar-benar selesai. Jangan mencentang sesuatu yang belum dilakukan. Kolom "Keterangan" boleh diisi catatan (misal: "sudah latihan 2x").

---

## 1. Kesiapan umum (semua anggota)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | Sudah membaca `00_gambaran_project.md` sampai paham istilah & fitur nyata | |
| ☐ | Sudah memahami `06_alur_demo_aplikasi.md` dan tahu urutan demo | |
| ☐ | Sudah berlatih tanya-jawab memakai `07_pertanyaan_pengujian.md` | |
| ☐ | Aplikasi bisa dibuka di `http://localhost/buku_tamu_digital` (Apache+MySQL jalan) | |
| ☐ | Akun login demo disiapkan mengikuti `README.md` root (password TIDAK ditulis di dokumen/slide) | |
| ☐ | Tidak ada satu pun dokumen/slide yang memuat password, token, connection string, atau isi `.env` | |
| ☐ | Tim sepakat menyebutkan 6 bug temuan (BUG-01 s/d BUG-06) dengan jujur bila ditanya kelemahan — dan tahu BUG-01 kini ditambal sementara oleh `.htaccess` baru (masih perlu di-commit) | |
| ☐ | Tim sepakat pembagian tugas disampaikan sebagai **rekomendasi role**, bukan klaim kontribusi per orang | |

---

## 2. Checklist per role

### Project Manager (PM)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | File dipahami: `01_pm_project_manager.md`, `00_gambaran_project.md` | |
| ☐ | Halaman/screenshot disiapkan: latar belakang, tujuan, ruang lingkup, prioritas MoSCoW | |
| ☐ | Menjelaskan pembagian tugas 5 role & mengakui belum ada bukti kontribusi per nama | |
| ☐ | Menyebutkan kekurangan dokumentasi: **ERD/use case & jadwal belum ada** | |
| ☐ | Durasi presentasi PM ± 8 menit (cek perkiraan durasi di `01_pm_project_manager.md`) | |
| ☐ | Siap menjawab pertanyaan B1–B5 di `07_pertanyaan_pengujian.md` | |

### Backend Engineer (BE)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | File dipahami: `02_be_backend_engineer.md`, `config/database.php`, `includes/auth.php`, `includes/csrf.php`, `includes/functions.php`, semua `actions/*.php`, `database.sql` | |
| ☐ | Halaman/screenshot disiapkan: koneksi PDO, guard role, CSRF, alur check-in/out, tabel & relasi DB | |
| ☐ | Bisa menunjukkan bukti keamanan: 302 tanpa login, 403 tanpa CSRF (dari dokumen QA) | |
| ☐ | Siap menjawab pertanyaan C1–C9 di `07_pertanyaan_pengujian.md` | |
| ☐ | Durasi presentasi BE ± 12 menit | |
| ☐ | Tidak membuka/menampilkan isi `.env` atau hash password saat presentasi | |

### Frontend Engineer (FE)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | File dipahami: `03_fe_frontend_engineer.md`, `assets/css/style.css`, `assets/js/app.js`, `assets/js/scanner.js`, `includes/{header,navbar,sidebar,footer}.php` | |
| ☐ | Halaman/screenshot disiapkan: layout + sidebar responsif, dashboard + Chart.js, form kunjungan, halaman scanner | |
| ☐ | Menjelaskan pola `includes/modules/` (modul dipakai bersama admin & petugas) | |
| ☐ | Siap menjawab pertanyaan D1–D7 di `07_pertanyaan_pengujian.md` | |
| ☐ | Durasi presentasi FE ± 10 menit | |

### System Analyst

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | File dipahami: `04_analyst_system_analyst.md`, `database.sql`, `includes/sidebar.php` (hak akses menu) | |
| ☐ | Halaman/screenshot disiapkan: aktor & hak akses, kebutuhan fungsional/nonfungsional, alur bisnis, relasi tabel | |
| ☐ | Jujur menyebut **ERD/use case/activity diagram belum ditemukan** & menawarkan membuatnya dari `database.sql` | |
| ☐ | Siap menjawab pertanyaan E1–E6 di `07_pertanyaan_pengujian.md` | |
| ☐ | Durasi presentasi Analyst ± 10 menit | |

### Quality Assurance (QA)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | File dipahami: `05_qa_quality_assurance.md`, `tests/e2e_test.php`, `tests/hasil_kunjungan_test.php`, `tests/revisi_test.php` (baru), `tests/qa_visual.mjs` | |
| ☐ | Halaman/screenshot disiapkan: strategi 3 lapis, tabel test case (dijalankan vs diusulkan vs bug vs risiko) | |
| ☐ | Menjelaskan 6 bug tereproduksi (BUG-01 s/d BUG-06) tanpa melebih-lebihkan | |
| ☐ | Tidak mengklaim semua test case PASS; yang dijalankan hanya pengujian tidak-mengubah-data | |
| ☐ | Siap menjawab pertanyaan F1–F6 di `07_pertanyaan_pengujian.md` | |
| ☐ | Durasi presentasi QA ± 10 menit | |

---

## 3. Kesiapan demo (dipimpin PM + BE)

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | Latihan demo langkah 1–13 `06_alur_demo_aplikasi.md` sekali penuh & diukur waktunya (target 12–15 menit) | |
| ☐ | Fallback scanner disiapkan (bila kamera bermasalah → input manual kunjungan) | |
| ☐ | Data latihan sudah dibersihkan lewat aplikasi (bukan SQL mentah) | |
| ☐ | Semua anggota tahu kapan harus menampilkan kode file tertentu | |
| ☐ | Fitur yang **tidak didemokan** sudah disepakati (bagian 5 dokumen `06`) | |

---

## 4. Daftar kekurangan dokumentasi (wajib diakui saat presentasi)

| ☐ | Kekurangan | Status jujur |
|---|---|---|
| ☐ | Dokumen ERD / use case / activity diagram | **Belum ditemukan dalam kode yang diperiksa** |
| ☐ | Jadwal / dokumen manajemen proyek (Gantt, sprint, backlog) | **Belum ditemukan dalam kode yang diperiksa** |
| ☐ | Bukti kontribusi per nama anggota (git history sangat terbatas) | **Belum bisa dibuktikan dari kode; perlu konfirmasi tim** |
| ☐ | Arsip hasil test bertanda tangan / laporan QA resmi | **Belum ditemukan dalam kode yang diperiksa** |
| ☐ | Screenshot pengujian visual (`qa-*.png` yang dirujuk `qa_visual.mjs`) | **Belum ditemukan dalam kode yang diperiksa** |

---

## 5. Checklist hari-H

| ☐ | Item | Keterangan |
|---|---|---|
| ☐ | Laptop presentasi sudah bisa membuka `localhost` aplikasi | |
| ☐ | XAMPP (Apache+MySQL) dites dari rumah/kantor sebelum berangkat | |
| ☐ | Semua dokumen di `docs/presentasi_tim/` bisa dibuka (format markdown terbaca) | |
| ☐ | Pembawaan: menyebutkan kelemahan dengan tenang & profesional (bukan menyembunyikan) | |
| ☐ | Sisa waktu dialokasikan untuk tanya-jawab (target: tiap role bisa jawab 1–2 pertanyaan) | |
