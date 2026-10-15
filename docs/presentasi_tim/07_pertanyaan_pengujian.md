# 07 — Pertanyaan Pengujian (Tanya-Jawab Penguji)

| | |
|---|---|
| **Nama materi** | Kumpulan pertanyaan pengujian & jawaban |
| **Role** | Semua anggota |
| **File rujukan** | Sesuai yang disebut tiap jawaban |
| **Durasi latihan** | 20–25 menit (latihan tim) |

> **Cara pakai:** Setiap jawaban ditulis **berdasarkan kode yang nyata ada**. Jika penguji bertanya sesuatu yang belum ada di kode, jawab jujur: *"Belum ditemukan dalam kode yang diperiksa"*, lalu jelaskan rencana pengembangannya. Jangan mengarang.

---

## A. Pertanyaan umum (untuk semua role)

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| A1 | Apa nama project-nya? | Sistem Informasi Buku Tamu Digital dan Monitoring Kunjungan Berbasis Web. |
| A2 | Mengapa memilih PHP native? | Sesuai `README.md` root: tugas memakai PHP native + MySQL di XAMPP. Kelebihannya: struktur mudah dipahami pemula, tanpa framework tambahan; kekurangannya: pengulangan pola (guard, CSRF) harus ditulis manual di tiap file. |
| A3 | Fitur utama apa saja yang benar-benar berfungsi? | Login admin/petugas, data tamu & pengguna, QR kunjungan, check-in manual/scan, monitoring kunjungan aktif, jaminan dengan upload bukti, check-out, laporan + ekspor CSV/cetak, log aktivitas, pengaturan instansi. Bukti filenya ada di `docs/presentasi_tim/00_gambaran_project.md`. |
| A4 | Apa kelemahan project ini? | Jujur menyebut temuan audit: file SQL & backup masih bisa diakses publik (BUG-02), tautan bukti jaminan 404 (BUG-03), folder upload di web root (BUG-04), logout tanpa proteksi POST/CSRF (BUG-05), suite test mengubah data permanen (BUG-06). Temuan `.env` publik (BUG-01) sudah **ditambal sementara** lewat file `.htaccess` baru per 15 Okt — tapi file itu masih belum di-commit. Rincian di `05_qa_quality_assurance.md`. |
| A5 | Kalau dikembangkan lagi, apa yang mau ditambah? | Notifikasi (email/WA), reset password, ERD & use case doc, perbaikan 6 bug di atas, riwayat kunjungan per tamu, dan dashboard analitik lanjutan. |

---

## B. Untuk Project Manager (PM)

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| B1 | Bagaimana membagi tugas ke 5 role? | Lihat `01_pm_project_manager.md` — BE (semua `actions/` + `config/database.php`), FE (`assets/css`, `assets/js`, tampilan), Analyst (dokumen kebutuhan & relasi DB dari `database.sql`), QA (`tests/`), PM (koordinasi + dokumentasi). |
| B2 | Apa prioritas fitur (MoSCoW)? | **Must:** login, kunjungan check-in/out, monitoring, laporan. **Should:** QR, jaminan. **Could:** ekspor Excel, pengaturan instansi. **Won't (saat ini):** notifikasi otomatis, mobile app. |
| B3 | Apakah ada jadwal/sprint project? | **Belum ditemukan dalam kode yang diperiksa** — tidak ada file jadwal. Inilah yang direkomendasikan dilengkapi. |
| B4 | Bagaimana bukti kontribusi tiap orang? | Saat audit 14 Okt git hanya punya 2 commit oleh 1 penulis ("risqy"); per 15 Okt bertambah jadi 3 commit oleh 2 penulis ("risqy" dan "Bintangpa") plus pekerjaan belum di-commit. Kontribusi per nama **tetap belum bisa dibuktikan lengkap dari kode** dan wajib dikonfirmasi oleh tim sendiri. |
| B5 | Bagaimana cara tim mengelola perubahan? | Sesuai aturan revisi di `README.md` root: ubah kode sesuai arahan mentor, perbarui catatan perubahan, dan jaga kerahasiaan credential. |

---

## C. Untuk Backend Engineer (BE)

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| C1 | Bagaimana koneksi ke database? | PDO dengan pola singleton di `config/database.php`, konfigurasi dibaca dari `.env` (hanya dipanggil, isinya tidak ditampilkan). |
| C2 | Bagaimana cara login diproses? | `login_process.php` memverifikasi password memakai `password_verify()`, lalu membuat sesi. Sesi dikonfigurasi `auth_boot()` di `includes/auth.php` (`httponly`, `samesite=Lax`, idle timeout 7200 detik). |
| C3 | Bagaimana hak akses dijaga? | Guard di `includes/auth.php`: `require_login()` untuk semua halaman terproteksi dan `require_role()` untuk membatasi admin/petugas. Terbukti dari pengujian: halaman terproteksi tanpa sesi → redirect 302. |
| C4 | Bagaimana mencegah CSRF? | Semua form mengirim token dari `includes/csrf.php`; POST tanpa token ditolak **403** (sudah diuji, lihat dokumen QA). |
| C5 | Bagaimana proses check-in & check-out? | Check-in: `actions/kunjungan_process.php` (dipakai admin & petugas). Check-out: `actions/checkout_process.php` — sengaja dipisah agar alurnya jelas. |
| C6 | Bagaimana penyimpanan file upload jaminan? | `upload_bukti()` di `includes/functions.php` (deklarasi baris 306; penentuan folder `APP_ROOT . '/uploads/bukti-jaminan'` di baris 333) menyimpan ke folder `uploads/bukti-jaminan/` di web root. Ini jadi temuan BUG-04 (idealnya di luar web root). |
| C7 | Berapa tabel di database & apa relasinya? | 10 tabel dengan primary/foreign key dan CHECK constraint di `database.sql`. Relasi utama: kunjungan ↔ tamu, kunjungan ↔ jaminan, kunjungan ↔ QR, users (role) memakai CHECK constraint. |
| C8 | Apa kelemahan backend yang kalian temukan sendiri? | BUG-01 (`.env` bisa diunduh publik), BUG-02 (file SQL publik), BUG-03 (tautan bukti 404), BUG-05 (logout bisa dipanggil GET). Semua tereproduksi dan didokumentasikan di `05_qa_quality_assurance.md`. |
| C9 | Mengapa memakai PDO bukan mysqli? | PDO mendukung banyak database dan menyediakan prepared statement bawaan untuk mencegah SQL injection; di `actions/*.php` konsisten memakai prepared statement. |

---

## D. Untuk Frontend Engineer (FE)

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| D1 | CSS memakai apa? | Bootstrap lokal + `assets/css/style.css` untuk kustomisasi (sidebar, kartu, tabel, dll). |
| D2 | JavaScript dipakai untuk apa? | `assets/js/app.js` (interaksi umum + inisialisasi Chart.js di dashboard) dan `assets/js/scanner.js` (pemindaian QR di `petugas/qr/scanner.php`). |
| D3 | Bagaimana tampilan dibuat konsisten antar halaman? | Layout dipakai bersama lewat `includes/header.php`, `includes/navbar.php`, `includes/sidebar.php`, `includes/footer.php`. |
| D4 | Bagaimana modul dipakai bersama admin & petugas? | Pola `includes/modules/` (kunjungan, tamu, jaminan, profil). Halaman `admin/kunjungan/index.php` dan `petugas/kunjungan/index.php` sama-sama memuat modul yang sama — menghindari duplikasi kode. |
| D5 | Bagaimana menangani input berbahaya saat ditampilkan? | Semua output dinormalisasi lewat `e()` (escape HTML) dari `includes/functions.php` untuk mencegah XSS. |
| D6 | Apakah sudah responsif? | Ya, memakai kerangka responsif Bootstrap + aturan tambahan di `assets/css/style.css`. |
| D7 | Ada kelemahan UI yang kalian temukan? | BUG-03: tautan bukti jaminan di `includes/modules/kunjungan/detail.php` (baris 170 saat audit; kini **baris 186** setelah revisi tim) memakai helper `asset()` sehingga path menunjuk `assets/uploads` yang tidak ada → 404. Ini temuan QA yang harus diperbaiki di sisi FE/BE. |

---

## E. Untuk System Analyst

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| E1 | Siapa saja aktor sistemnya? | 2 aktor utama: **admin** (penuh) dan **petugas** (terbatas: kunjungan, tamu, QR scanner, jaminan, profil). Lihat guard di `includes/auth.php` dan menu di `includes/sidebar.php`. |
| E2 | Apa kebutuhan fungsional utama? | Kelola data tamu/pengguna/QR, proses kunjungan (check-in/out), monitoring aktif, jaminan + bukti, laporan + ekspor, log aktivitas, pengaturan instansi. |
| E3 | Apa kebutuhan nonfungsional yang dipenuhi? | Keamanan: sesi `httponly` + CSRF + prepared statement + escape output + log aktivitas. Kinerja: query ringkas via helper. **Belum ada** pengujian beban formal. |
| E4 | Apa alur bisnis check-in sampai check-out? | Tamu/QR → `kunjungan_process.php` membuat kunjungan (check-in) → muncul di kunjungan aktif → `checkout_process.php` menutup kunjungan (waktu keluar tercatat) → masuk laporan. |
| E5 | Ada ERD / use case diagram? | **Belum ditemukan dalam kode yang diperiksa.** Bahan untuk membuatnya ada di `database.sql` (10 tabel + FK) dan alur bisnis di `04_analyst_system_analyst.md`. |
| E6 | Apa keputusan desain database yang menarik? | Ada CHECK constraint (misalnya untuk role/status), foreign key antar tabel, dan helper `setting()` untuk konfigurasi instansi di tabel `settings`. |

---

## F. Untuk Quality Assurance (QA)

| # | Pertanyaan | Jawaban sesuai kode |
|---|---|---|
| F1 | Bagaimana strategi pengujian tim? | 3 lapis: (1) pengujian hanya-baca via HTTP (guard 302, CSRF 403, GET action 405, aset vendor 200), (2) suite test CLI di `tests/e2e_test.php` & `tests/hasil_kunjungan_test.php` plus suite baru `tests/revisi_test.php`, (3) pemeriksaan visual via `tests/qa_visual.mjs`. |
| F2 | Test case mana yang benar-benar dijalankan? | Hanya pengujian **hanya-baca** yang dijalankan saat audit (14 Okt 2026). Suite test yang **menulis ke database tidak dijalankan** demi kepatuhan aturan (tidak boleh mengubah data) — dan itu justru mengungkap BUG-06. Rincian: dijalankan vs diusulkan ada di `05_qa_quality_assurance.md`. |
| F3 | Bug apa yang bisa kalian buktikan? | 6 bug tereproduksi: BUG-01 `.env` 200 (kini 403 berkat `.htaccess` baru, tapi masih perlu di-commit & diuji ulang), BUG-02 SQL publik, BUG-03 bukti 404, BUG-04 upload di web root, BUG-05 logout GET, BUG-06 test mengubah `settings.nama_instansi` permanen. |
| F3b | Ada suite test tambahan? | Ya — `tests/revisi_test.php` (78 asersi) dibuat tim untuk menguji hasil revisi (sidebar, jaminan di Kunjungan Aktif, proteksi `.env`, kolom identitas, penguncian hasil kunjungan). Statusnya: **skrip ada, hasil resmi belum diarsipkan**. |
| F4 | Apa risiko yang belum diperiksa? | Pengujian beban, pengujian keamanan menyeluruh (mis. XSS/SQLi otomatis), kompatibilitas browser banyak versi, dan aksesibilitas (accessibility). |
| F5 | Apakah semua test case PASS? | **Tidak.** Yang dijalankan hanya pengujian hanya-baca; beberapa di antaranya justru menemukan bug (di atas). Sisanya **diusulkan**, bukan klaim lulus. |
| F6 | Bagaimana hasil test didokumentasikan? | Hasil pengujian hanya-baca dicatat di `05_qa_quality_assurance.md`. **Belum ada** arsip hasil test bertanda tangan / screenshot `qa-*.png` yang ditemukan — jujur disebutkan sebagai kekurangan dokumentasi. |

---

## G. Pertanyaan "menjebak" umum & cara menjawab jujur

| Pertanyaan penguji | Jawaban aman & jujur |
|---|---|
| "Apakah sistem ini sudah sempurna?" | "Belum. Audit kami menemukan 6 bug nyata yang kami sendiri yang buktikan, terutama soal keamanan file `.env` dan file SQL yang bisa diakses publik (BUG-01, BUG-02). Rencana perbaikannya ada di dokumen QA." |
| "Siapa yang mengerjakan bagian ini?" | "Pembagian di dokumen kami adalah **rekomendasi peran**, bukan klaim per orang. Dari git kontribusi per nama belum bisa dibuktikan, jadi kami konfirmasi dulu di tim." |
| "Kenapa fitur X tidak ada?" | "Belum ditemukan dalam kode yang diperiksa. Kalau dikembangkan, urutan prioritasnya ada di dokumen PM." |
| "Apakah sudah diuji?" | "Yang kami jalankan saat audit hanya pengujian tidak-mengubah-data. Suite test penuh ada di `tests/` tapi belum kami jalankan karena menulis ke database." |
| "Berapa lama presentasi tiap role?" | Lihat perkiraan durasi di masing-masing file `01`–`05` dan checklist di `08_checklist_presentasi.md`. |
