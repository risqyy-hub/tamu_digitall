# BAB III.C — Materi Presentasi: FE (Frontend Engineer)

**Tujuan presentasi FE:** menunjukkan struktur tampilan, interaksi pengguna, dan bagaimana halaman berbicara dengan backend — tanpa mengklaim fitur yang tidak ada di kode.

**Perkiraan durasi presentasi FE:** ±10 menit.

Format: **Nama materi → File yang menghasilkan tampilan → File pendukung (CSS/JS) → Komponen penting → Interaksi → Cara frontend ↔ backend → Screenshot → Pertanyaan penguji.**

---

## M1. Layout dasar aplikasi (header, navbar, sidebar, footer)

**Nama materi:** Kerangka halaman (master layout).
**Role:** FE.
**File:**
- `includes/header.php` — `<head>` + pembuka layout.
- `includes/navbar.php` — bar atas.
- `includes/sidebar.php` — menu kiri.
- `includes/footer.php` — penutup + ekstensi JS.

**Bagian kode penting:**
- `header.php` baris 15–34: `viewport`, judul `<?= e($page_title) ?> · <?= e(setting('nama_instansi', APP_NAME)) ?>`, memuat Bootstrap CSS (baris 23), Bootstrap Icons (24), `assets/css/style.css` (25), lalu `require navbar` dan `require sidebar` (30–31), membuka `<main class="app-main">`.
- `header.php` baris 36–47: menampilkan **flash message** (sukses/gagal) dari sesi — inilah pesan notifikasi hijau/merah setelah aksi.
- `footer.php` baris 19–21: `bootstrap.bundle.min.js`, `sweetalert2.all.min.js`, `assets/js/app.js`.

**Variabel wajib:** setiap halaman menyetel `$page_title` dan `$active_menu` **sebelum** `require header` (contoh `admin/dashboard.php` baris 72–74).

**Penjelasan sederhana:** Tidak ada template engine. Satu halaman = file PHP yang merangkai HTML, lalu memanggil 3 potongan layout bersama sehingga navbar/sidebar identik di semua halaman.

**Screenshot:** tampilan setelah login: navbar atas (nama instansi + lonceng + nama user), sidebar kiri, isi konten, footer.

**Pertanyaan penguji:**
- *Bagaimana sidebar bisa berbeda untuk admin & petugas?* → array `$menu` dibangun di `sidebar.php` baris 10–53 berdasarkan `current_role()`.
- *Kenapa halaman admin tidak menampilkan menu laporan untuk petugas?* → karena item `Laporan` hanya ditambahkan pada blok admin (baris 29–35).

---

## M2. Halaman login

**Nama materi:** Tampilan login.
**Role:** FE.
**File:** `login.php` (84 baris) — berdiri sendiri (tidak memakai layout aplikasi).
**Pendukung:** `assets/css/style.css` (`.auth-body`, `.auth-card` baris 662–694).

**Bagian kode:**
- baris 20: judul memakai nama instansi dari `settings`.
- baris 48–49: `<form method="post" action="…/actions/login_process.php">` + `<?= csrf_field() ?>`.
- baris 51–71: input `username` (bisa username/email) & `password` dengan tombol lihat/sembunyikan password.
- baris 78–80: menampilkan jam layanan dari `setting('jam_buka')`/`jam_tutup`.
- baris 84–92: JavaScript inline kecil untuk toggle tampil-password.

**Interaksi:** isi form → submit → server memproses → bila gagal kembali ke halaman ini dengan alert (ditampilkan lewat `flash_all()` baris 42–46).

**Screenshot:** halaman login (belum terisi), dan alert "Username atau password salah" setelah login salah.

**Pertanyaan penguji:**
- *Apakah validasi password dilakukan di JavaScript?* → Tidak. JS hanya mengubah tampilan input; verifikasi ada di `actions/login_process.php`.
- *Kenapa tombol password pakai JS?* → kenyamanan pengguna; tidak memengaruhi keamanan.

---

## M3. Dashboard Admin

**Nama materi:** Dashboard admin (statistik + grafik).
**Role:** FE.
**File:** `admin/dashboard.php` (240 baris).
**Pendukung:** `assets/vendor/chartjs/chart.umd.min.js` (baris 224), `assets/css/style.css` (`.card.stat-card` baris 436–491, `.panel` baris 339–364).

**Komponen penting:**
1. Judul + tombol aksi cepat: "Tambah Kunjungan" dan "Laporan" (baris 77–90).
2. **6 kartu statistik** dari loop `$cards` (baris 92–116): Kunjungan Hari Ini, Sedang di Kantor, Selesai Hari Ini, Jaminan Aktif, Tamu Terdaftar, Kunjungan Bulan Ini — nilainya **bukan hardcode**, diambil dari query (baris 12–19).
3. **Grafik batang** Kunjungan 7 hari (baris 122–123 canvas + konfigurasi baris 226–242) dan **grafik donat** status 30 hari (baris 128–129 + baris 244–255) — data dikirim dari PHP ke JS lewat `json_encode` (baris 229–231).
4. Tabel "Sedang Berkunjung" (baris 134–174) dengan **durasi berjalan** dan badge jaminan.
5. Tabel "Kunjungan Terbaru" (baris 176–201) + panel "Aktivitas" (baris 203–221).

**Interaksi:** hover kartu/grafik, klik "semua »" untuk pindah ke halaman daftar, klik nama tamu → detail.

**Screenshot:** dashboard admin lengkap dengan grafik terisi (pastikan data demo sudah diimpor).

**Pertanyaan penguji:**
- *Apakah datanya statis?* → Tidak; tiap reload dijalankan query `COUNT` (baris 12–19) dan query grafik (baris 22–49).
- *Library grafik apa?* → Chart.js (baris 224) dengan font default diatur di `assets/js/app.js` baris 74–77.
- *Bagaimana data PHP masuk ke JavaScript?* → `json_encode($chartHari)` (baris 229) — aman karena di-encode, bukan disusun string manual.

---

## M4. Dashboard Petugas

**Nama materi:** Dashboard resepsionis.
**Role:** FE.
**File:** `petugas/dashboard.php` (164 baris).

**Komponen:** 4 kartu statistik (baris 58–77), tabel "Sedang Berkunjung" dengan kolom Tujuan/Durasi/Jaminan (baris 79–131), "Kunjungan Terakhir" (baris 133–155), "Aktivitas Saya" — hanya log milik sendiri (query baris 33–36).

**Interaksi:** tombol cepat **Scan QR** (baris 49–51) dan **Tambah Kunjungan** (baris 52–54).

**Perbedaan dengan admin (bahan presentasi):** tidak ada grafik, tidak ada filter mitra, aktivitas hanya milik sendiri.

**Screenshot:** dashboard petugas.

**Pertanyaan penguji:** *Kenapa dashboard petugas lebih sederhana?* → Karena tugas petugas adalah operasional harian (menerima tamu), sedangkan analitik dipusatkan di admin.

---

## M5. Sidebar responsif & navbar notifikasi

**Nama materi:** Navigasi & notifikasi.
**Role:** FE.
**File:** `includes/sidebar.php` baris 55–73, `includes/navbar.php` baris 31–105.
**Pendukung:** `assets/css/style.css` baris 197–245 (sidebar), `@media (min-width: 992px)` baris 247–266.

**Komponen penting:**
- Sidebar memakai **Bootstrap Offcanvas** `offcanvas-lg offcanvas-start` (baris 55) → di layar < 992px sidebar jadi menu geser yang dibuka lewat tombol ☰ di navbar (`navbar.php` baris 32–36).
- Item aktif diberi kelas `active` berdasar `$active_menu` (baris 66).
- **Lonceng notifikasi** (`navbar.php` baris 43–79): badge jumlah belum dibaca, daftar 5 notifikasi terbaru; tiap item adalah **form POST kecil** ke `actions/notif_process.php` dengan `action=mark_read` (baris 58–67) dan "Tandai semua" `action=mark_all` (baris 70–77).
- **Menu pengguna** (baris 81–103): nama + role, tautan Profil, tombol **Keluar** berupa `<form method="post">` ke `logout.php` dengan CSRF (baris 94–100).

**Interaksi:** klik notifikasi → halaman reload (redirect ke `index.php`) dan badge berkurang; klik logout → kembali ke login dengan pesan "Anda telah keluar".

**Screenshot:** sidebar terbuka di lebar layar kecil (mode responsif) dan dropdown notifikasi.

**Pertanyaan penguji:**
- *Bagaimana menu tampil di HP?* → Offcanvas Bootstrap: di bawah 992px sidebar disembunyikan dan dibuka lewat tombol (`.btn-menu-toggle`, `style.css` baris 175–180).
- *Kenapa notifikasi memakai form POST?* → karena menandai sudah dibaca mengubah data; POST + token CSRF (lihat `includes/csrf.php`).

---

## M6. Form check-in (halaman paling kompleks)

**Nama materi:** Form tambah kunjungan.
**Role:** FE.
**File:** `includes/modules/kunjungan/tambah.php` (344 baris) — dipakai admin (`admin/kunjungan/tambah.php`) **dan** petugas (`petugas/kunjungan/tambah.php`).
**Pendukung:** `assets/css/style.css` (`.checkin-card`, `.checkin-step`, `.checkin-summary` baris 834–881).

**Komponen penting:**
1. **Peringatan QR bila tidak valid** (baris 89–104): kartu merah + tombol "Lanjutkan dengan Pendaftaran Manual" dan "Scan Ulang".
2. **Kartu sukses QR** (baris 106–112): identitas tamu dari database.
3. **Tab** "Tamu Terdaftar / Data Ada" vs "Tamu Baru (Walk-in)" (baris 168–230) — pill navigation Bootstrap.
4. Langkah bernomor 1 Identitas & 2 Detail Kunjungan (badge `step-badge`, baris 136–146 dan 234–240).
5. Input `tujuan` dengan `<datalist>` saran (baris 246–248), `pegawai_ditemui`, `keperluan`, `hasil_kunjungan` (opsional, `maxlength` = 4000, baris 261–269).
6. **Panel Ringkasan** kanan (baris 285–306) yang terisi otomatis oleh JS (baris 326–369).
7. Form tersembunyi `action=checkin`, `mode=qr`, `token=…` (baris 124–130).

**Interaksi:** saat user mengetik, panel ringkasan ikut berubah (`form.addEventListener('input', refresh)` baris 366–367); submit → POST ke `actions/kunjungan_process.php`.

**Screenshot:** form check-in mode manual (tab terlihat) dan mode QR (identitas readonly).

**Pertanyaan penguji:**
- *Apakah JS panel ringkasan memvalidasi data?* → Tidak, hanya menampilkan ringkasan; komentar di baris 327 menegaskan "hanya membaca nilai form yang sudah ada (tanpa logika bisnis)".
- *Kenapa identitas tamu mode QR readonly?* → karena diverifikasi di server; mengubahnya lewat DevTools tidak berpengaruh (dicek lagi di backend).

---

## M7. Daftar kunjungan, filter, tabel, dan pagination

**Nama materi:** Tampilan monitoring kunjungan.
**Role:** FE.
**File:** `includes/modules/kunjungan/index.php` (199 baris), `aktif.php` (113 baris).
**Pendukung:** `assets/css/style.css` baris 571–572 (`.filter-form`), baris 412–421 (`.hasil-clamp`), `assets/js/app.js` baris 61–71 (durasi berjalan).

**Komponen:**
- Form filter GET: pencarian `q`, status, tanggal `dari`/`sampai`, pilihan mitra (admin saja) (baris 100–138).
- Tabel 10 kolom: No. Kunjungan, Tamu, Instansi, Tujuan, **Hasil Kunjungan**, Masuk, Keluar, Durasi, Status, Aksi (baris 144–155).
- Kolom hasil kunjungan memakai `cell_hasil_kunjungan()` → teks dipotong **oleh CSS** (2 baris) + tautan "Lihat Detail" (baris 175–177; CSS `.hasil-clamp` baris 412–421).
- Badge status & badge jumlah jaminan aktif (baris 184–191).
- Pagination Bootstrap (`pagination()` di `includes/functions.php` baris 385–428) (baris 204–210).
- Durasi berjalan diperbarui tiap 30 detik lewat atribut `data-durasi-start` (baris 180–182) + `assets/js/app.js` baris 61–71.

**Interaksi:** isi filter → "cari" → URL berubah (`?q=…&status=…`) → tabel terfilter; klik ikon mata → detail.

**Screenshot:** daftar kunjungan dengan filter terpasang + kolom hasil kunjungan.

**Pertanyaan penguji:**
- *Bagaimana filter bekerja?* → form GET dikirim ke URL yang sama; PHP membaca `$_GET` lalu membangun `WHERE` bersiapan.
- *Kenapa pemotongan teks pakai CSS?* → data di database tidak dipotong; hanya tampilan yang dibatasi 2 baris (`includes/functions.php` baris 440–444).
- *Bagaimana durasi bisa berubah tanpa reload?* → `app.js` menghitung ulang dari `data-durasi-start` tiap 30 detik.

---

## M8. Halaman detail kunjungan (hasil kunjungan + jaminan + check-out)

**Nama materi:** Detail kunjungan & aksi.
**Role:** FE.
**File:** `includes/modules/kunjungan/detail.php` (231 baris).

**Komponen:**
- Panel "Info Kunjungan" berupa tabel `<dl>` ringkas: kode, tamu, identitas, QR (menampilkan **12 karakter pertama hash saja**, baris 84–93), tujuan, hasil, jam masuk/keluar, durasi, petugas masuk/keluar.
- Panel **Hasil Kunjungan** dengan textarea + tombol "Simpan Hasil" → POST `action=simpan_hasil` (baris 114–137).
- Tabel **jaminan** + tombol "Kembalikan" per baris dengan konfirmasi SweetAlert2 (`data-confirm`, baris 184–192).
- Panel **Check-out**: pesan peringatan bila masih ada jaminan (baris 213–219), textarea hasil opsional (225–233), tombol yang **`disabled`** bila jaminan aktif (baris 235–240) + keterangan "server tetap memvalidasi saat dikirim".

**Interaksi:** simpan hasil → halaman reload dengan pesan sukses; kembalikan jaminan → muncul dialog konfirmasi (diproses `app.js` baris 28–53).

**Screenshot:** detail kunjungan berjalan dengan 1 jaminan aktif (tombol check-out nonaktif), lalu setelah jaminan dikembalikan.

**Pertanyaan penguji:**
- *Kalau tombolnya disabled, apakah aman?* → Tombol hanya hiasan; validasi utama di `actions/checkout_process.php` (bisa dibuktikan dengan mengirim POST langsung → tetap ditolak).
- *Kenapa hanya 12 karakter hash yang ditampilkan?* → supaya cukup sebagai bukti teknis tanpa membocorkan hash penuh.

---

## M9. Scanner QR (kamera + token manual)

**Nama materi:** Komponen scanner QR.
**Role:** FE.
**File:** `petugas/qr/scanner.php` (96 baris).
**Pendukung:** `assets/js/scanner.js` (101 baris), `assets/vendor/jsqr/jsQR.js`, CSS `.scanner-frame` (`style.css` baris 709–734).

**Komponen:**
- `<video id="scanVideo">` + `<canvas id="scanCanvas">` tersembunyi (baris 34–36).
- Tombol **Mulai Kamera** / **Stop** + area status (baris 39–50).
- Form **Input Token Manual** berupa GET ke `kunjungan/tambah.php?token=…` (baris 67–75).
- Pemanggilan `initQrScanner({ onResult: … })` (baris 92–105) yang mengarahkan browser ke `…/kunjungan/tambah.php?token=<hasil pindai>`.

**Alur pada `scanner.js`:**
- `start()` baris 63–92: `navigator.mediaDevices.getUserMedia({video:{facingMode:'environment'}})` → pesan ramah bila izin ditolak (`NotAllowedError`) atau tidak ada kamera (`NotFoundError`).
- `detectLoop()` baris 29–50: gambar video → canvas → `jsQR()` → bila ada hasil: hentikan kamera, panggil `onResult`.
- `stop()` baris 52–61: mematikan track kamera (menghentikan LED kamera).

**Interaksi:** izinkan kamera → arahkan ke QR → halaman pindah ke form check-in yang sudah terisi.

**Screenshot:** halaman scanner (sisi kiri kamera, sisi kanan input manual).

**Pertanyaan penguji:**
- *Di mana pemrosesan QR dilakukan?* → **Di browser** (jsQR) hanya untuk **membaca teks**; keputusan validasi tetap di server (`actions/kunjungan_process.php`).
- *Kenapa kamera tidak bisa dibuka?* → Browser hanya mengizinkan `getUserMedia` di konteks aman (`https://` atau `http://localhost`) — ditegaskan di `scanner.php` baris 79–85.
- *Apa yang terjadi kalau kamera tidak ada?* → Ada jalur alternatif: input token manual (baris 67–75).

---

## M10. CSS, tema, dan responsivitas

**Nama materi:** Desain visual & responsivitas.
**Role:** FE.
**File:** `assets/css/style.css` (934 baris).

**Bagian penting:**
- `:root` baris 14–33: variabel warna navy (`--deep-navy`, `--navy-surface`), krem (`--cream`), radius, transisi → satu sumber warna tema navy/putih.
- Tombol tema: `.btn-navy` (87), `.btn-outline-navy` (101), `.btn-cream` (125), `.btn-icon` (113).
- Layout: `.app-wrapper` flex column (143), navbar gradien navy (147–180), sidebar (197–245), `.panel` kartu konten (339), `.page-header` (277), `.empty-state` (633), `.notif-menu` (613), `.auth-card` (662), `.scanner-frame` (709), `.checkin-summary` (834).
- **Responsivitas:** `@media (min-width: 992px)` baris 247 (sidebar jadi kolom tetap) dan baris 880 (ringkasan check-in jadi `sticky`); sidebar memakai offcanvas untuk layar kecil; tabel dibungkus `.table-responsive`.
- **Cetak:** dua blok `@media print` baris 923 dan 991 → menyembunyikan `.no-print` (nav, filter, tombol) dan memperbesar teks laporan/QR supaya hasil cetak rapi.
- Khusus hasil kunjungan: `.hasil-clamp` baris 412–421 (`-webkit-line-clamp: 2`) dan di `@media print` dihilangkan (baris 977–978) supaya tercetak penuh.

**Interaksi visual:** efek hover kartu statistik (baris 460), fokus `:focus-visible` (baris 71–76) untuk aksesibilitas keyboard.

**Screenshot:** bandingkan lebar layar 1440px (sidebar terbuka) vs 390px (sidebar jadi tombol ☰); dan pratinjau cetak laporan (Ctrl+P) — navigasi hilang, header laporan muncul.

**Pertanyaan penguji:**
- *Kenapa pakai CSS variabel?* → agar konsistensi warna mudah diubah di satu tempat (`:root`).
- *Apakah sudah responsif?* → Ya, memakai grid Bootstrap + offcanvas di < 992px + `table-responsive`; belum ada pengujian visual terdokumentasi untuk banyak perangkat (lihat QA).
- *Kenapa CSS print terpisah?* → agar tampilan layar (menu, tombol) tidak ikut tercetak, sedangkan header laporan & tabel tetap muncul.

---

## M11. JavaScript global (`assets/js/app.js`, 71 baris)

**Nama materi:** Interaksi JavaScript.
**Role:** FE.
**File:** `assets/js/app.js`.

**Isi (semua pelengkap, keamanan tetap di server):**
1. Baris 10–18: setelan bawaan SweetAlert2 (kelas tombol Bootstrap).
2. Baris 21–25: alert otomatis hilang setelah 6 detik.
3. Baris 28–53: **konfirmasi aksi penting** untuk `<form data-confirm="…">` — dipakai pada hapus data & kembalikan jaminan.
4. Baris 61–71: pembaruan durasi berjalan tiap 30 detik.
5. Baris 74–77: setelan font Chart.js.

**Pertanyaan penguji:**
- *Apakah semua tombol hapus memakai konfirmasi?* → Hanya form yang diberi atribut `data-confirm`; itu yang membuat muncul dialog SweetAlert2.
- *Kalau JavaScript dimatikan, aplikasi tetap jalan?* → Ya untuk alur inti (form tetap POST); kehilangan konfirmasi dialog & durasi live — itulah alasan "JS hanya pelengkap UX" (komentar baris 3–4).

---

## M12. Cara frontend ↔ backend (ringkasan untuk slide)

```
Halaman PHP (GET)  ── menampilkan form + <?= csrf_field() ?> + value lama
        │  user menekan tombol
        ▼
POST ke /actions/*.php  ── require_role → require_post → verify_csrf → validasi → query
        │
        ▼
redirect()  ── header Location kembali ke halaman + flash message di session
        │
        ▼
Halaman reload  ── header.php menampilkan alert flash + data terbaru dari database
```

**Bukti di kode:** semua form memakai `action="<?= e(url('actions/…_process.php')) ?>"` dan respon server selalu `redirect()` (`includes/functions.php` baris 45–53), bukan halaman hasil mentah.

**Pertanyaan penguji:** *Apakah memakai AJAX/fetch?* → Mayoritas alur memakai form POST biasa + redirect; AJAX baru disiapkan untuk kebutuhan khusus (ada penanganan respons 401 JSON di `includes/auth.php` baris 112–117), tetapi **belum ditemukan halaman yang memakai fetch untuk operasi utama** — sampaikan apa adanya.
