# BAB III.B — Materi Presentasi: BE (Backend Engineer)

**Tujuan presentasi BE:** membuktikan bahwa proses bisnis di aplikasi benar-benar dikerjakan di **server** (PHP + PDO + database), bukan sekadar tampilan.

**Perkiraan durasi presentasi BE:** ±12 menit (termasuk menunjukkan kode & demo singkat).

Format setiap materi di bawah selalu sama:
**Nama materi → File → Bagian kode → Penjelasan sederhana → Alur kerja → Demo → Pertanyaan penguji.**

> Semua path relatif dari root project. Nomor baris dikutip dari kondisi kode per 14 Okt 2026.

---

## M1. Arsitektur backend (pola "halaman tampil + file aksi")

**Nama materi:** Arsitektur backend PHP native tanpa framework.
**Role:** BE.
**File yang ditunjukkan:**
- `includes/functions.php` (baris 1–20) — titik masuk bersama setiap halaman.
- `includes/modules/kunjungan/index.php` (baris 1–81) — halaman tampil.
- `actions/kunjungan_process.php` (baris 1–20) — pemroses POST.

**Bagian kode:** penjelasan `requires` di `includes/functions.php` baris 11–20:

```php
if (!defined('APP_FUNCTIONS_LOADED')) {
    define('APP_FUNCTIONS_LOADED', true);
    require_once __DIR__ . '/../config/database.php'; // koneksi
    require_once __DIR__ . '/auth.php';               // session & guard
    require_once __DIR__ . '/csrf.php';               // token CSRF
    auth_boot();                                      // mulai session
}
```

**Penjelasan sederhana:** Setiap halaman cukup `require` satu file `functions.php`; file itu otomatis memuat koneksi database, session, dan CSRF. Tidak ada framework — aturan mainnya dibuat sendiri: **GET hanya untuk menampilkan, POST untuk mengubah data, dan semua POST masuk ke folder `actions/`**.

**Alur kerja:** browser buka `admin/tamu/index.php` (GET, tampil) → pengguna menekan Simpan → form POST ke `actions/tamu_process.php` → proses + validasi → `redirect()` kembali ke halaman.

**Demo:** buka Network tab di DevTools → tambah data → tunjukkan method `POST` ke `actions/…` lalu `302` redirect.

**Pertanyaan penguji:**
- *Kenapa tidak pakai Laravel?* → "Tugas memakai PHP native; struktur folder `actions/` + `includes/` dibuat sendiri supaya ringan dan mudah dipelajari, sementara keamanan utama (prepared statement, CSRF, role guard) tetap diterapkan manual."
- *Di mana titik masuk bersama semua halaman?* → `includes/functions.php`.

---

## M2. Konfigurasi environment (.env) tanpa menampilkan credential

**Nama materi:** Pembacaan konfigurasi dari `.env`.
**Role:** BE.
**File:** `config/database.php` (baris 11–39 dan 44–71), `.env.example`.
**Bagian kode:** fungsi `env()` baris 35–39 dan blok `define(...)` baris 44–58; loader loop baris 12–33.

**Penjelasan sederhana:** Nilai koneksi (host, port, nama database, user, password) **tidak ditulis di kode**, melainkan dibaca dari file `.env` yang tidak di-commit ke Git (lihat `.gitignore` baris 2: `.env`). Kode hanya memanggil `env('DB_HOST', …)` dengan nilai bawaan.

**Alur kerja:** file `.env` dibaca → disimpan ke environment → `define('DB_HOST', …)` → dipakai fungsi `db()`.

**Demo:** tunjukkan `.env.example` (boleh) dan `.gitignore`; **jangan buka `.env`** di depan layar. Buka `admin/pengaturan.php` → bagian "Informasi Sistem" menampilkan nama database & versi PHP tanpa password.

**Pertanyaan penguji:**
- *Di mana password database?* → "Di file `.env` yang hanya dibaca PHP, tidak di-commit ke Git, dan tidak pernah ditampilkan ke browser."
- *Apa yang terjadi bila `.env` belum ada?* → "Fungsi `env()` mengembalikan nilai default, lalu `db()` menampilkan halaman galat ramah (baris 113–129) tanpa membocorkan kredensial."

**Peringatan untuk demo:** jangan menampilkan isi `.env`. *(Hasil audit menemukan `.env` masih bisa diunduh lewat browser — lihat `05_qa_quality_assurance.md` BUG-01; sebaikkan dibahas sebagai temuan, bukan ditutup-tutupi.)*

---

## M3. Koneksi PHP ↔ MySQL/MariaDB dengan PDO

**Nama materi:** Koneksi database (PDO singleton).
**Role:** BE.
**File:** `config/database.php` baris 80–132.
**Bagian kode penting:** fungsi `db()`, opsi PDO baris 99–103, dan `SET time_zone` baris 107–112.

```php
function db(): PDO {
    static $pdo = null;                       // hanya sekali per proses
    if ($pdo instanceof PDO) return $pdo;
    ...
    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', ...);
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,   // prepared statement asli
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $pdo->exec("SET time_zone = '+07:00'");       // samakan zona waktu
```

**Penjelasan sederhana:** `db()` adalah **singleton** — koneksi dibuat sekali lalu dipakai semua halaman. `EMULATE_PREPARES = false` berarti placeholder `?` benar-benar dikirim ke MySQL (bukan disusun ulang oleh PHP), sehingga anti SQL injection. Zona waktu MySQL disamakan dengan PHP (`Asia/Jakarta`) supaya `jam_masuk`/`jam_keluar` konsisten.

**Alur kerja:** `db()` dipanggil → DSN dibentuk dari konstanta `.env` → `new PDO` → `SET time_zone` → objek PDO dikembalikan → dipakai `prepare()/execute()`.

**Demo:** buka `config/database.php` baris 96–112; jalankan `C:\xampp\php\php.exe -r "require 'config/database.php'; echo db()->query('SELECT NOW()')->fetchColumn();"`

**Pertanyaan penguji:**
- *PDO atau mysqli?* → PDO, karena mendukung MySQL sekaligus PostgreSQL (untuk rencana Supabase) hanya dengan mengganti driver di `.env`.
- *Kenapa memakai prepared statement?* → supaya nilai input tidak pernah digabung ke string SQL (anti injection) dan query di-cache server.
- *Ke mana error koneksi ditampilkan?* → log error (`error_log`) + halaman galat generik; detail hanya muncul bila `APP_DEBUG=1`.

---

## M4. Struktur & relasi database

**Nama materi:** Skema database 10 tabel.
**Role:** BE (materi bersama dengan Analyst).
**File:** `database.sql` (MySQL) dan `database/supabase_schema.sql` (PostgreSQL).
**Bagian kode:** setiap `CREATE TABLE` + `CONSTRAINT … FOREIGN KEY`:

| Tabel | Fungsi | Baris pembuatan |
|---|---|---|
| `users` | akun admin/petugas, password hash, `role`, `status` | 39–54 |
| `partner_companies` | perusahaan mitra (`kode_mitra` unik) | 59–74 |
| `visitors` | identitas tamu (`kode_tamu` unik) | 79–94 |
| `registered_visitors` | tamu terdaftar (pemilik QR), FK ke visitor & mitra | 100–115 |
| `visitor_qr_codes` | token **hash** SHA-256 + masa berlaku + status | 122–141 |
| `visits` | transaksi kunjungan (check-in/out, durasi, hasil kunjungan) | 148–182 |
| `guarantees` | jaminan/barang titipan per kunjungan | 189–209 |
| `notifications` | lonceng (user_id NULL = semua orang) | 214–225 |
| `activity_logs` | audit trail (user, IP, user agent) | 230–243 |
| `settings` | konfigurasi key–value (nama instansi, jam layanan, dll.) | 248–256 |

**Relasi penting (FK):**
- `registered_visitors.visitor_id` → `visitors.id` **ON DELETE CASCADE** (baris 113)
- `registered_visitors.mitra_id` → `partner_companies.id` **ON DELETE SET NULL** (baris 114)
- `visitor_qr_codes.registered_visitor_id` → `registered_visitors.id` CASCADE (baris 138)
- `visits.visitor_id` → `visitors.id` **ON DELETE RESTRICT** (baris 176) → tamu dengan riwayat tidak bisa dihapus
- `guarantees.visit_id` → `visits.id` CASCADE (baris 206)
- `visits.petugas_masuk_id/petugas_keluar_id` → `users.id` SET NULL (baris 180–181)

**Penjelasan sederhana:** Satu tamu (`visitors`) bisa punya banyak registrasi (`registered_visitors`); registrasi itu yang diberi QR; setiap kedatangan membuat **baris baru** di `visits` (riwayat tidak ditimpa); satu kunjungan bisa punya banyak `guarantees`. Aturan hapus: tamu ber-riwayat **RESTRICT** (tidak bisa dihapus), hapus registrasi → QR ikut hilang (CASCADE) tetapi riwayat kunjungan tetap ada (`visits.registered_visitor_id` SET NULL).

**Demo:** buka `database.sql` baris 148–182 lalu tunjukkan gambar relasi (lihat `04_analyst_system_analyst.md`).

**Pertanyaan penguji:**
- *Kenapa `visits` tidak di-update melainkan insert baru tiap kedatangan?* → "Supaya riwayat lengkap; kolom `jam_keluar`/`durasi_menit` baru diisi saat check-out, dan kunjungan berikutnya tetap jadi record terpisah."
- *Kenapa password tidak ada di `database.sql`?* → "Akun dibuat lewat CLI `setup/create_admin.php` dengan `password_hash()` supaya tidak ada password teks biasa di file skema."
- *Kenapa enum pakai VARCHAR + CHECK?* → supaya portabel ke PostgreSQL saat migrasi.

---

## M5. Session, guard role, dan hak akses

**Nama materi:** Proses login & pembagian hak akses.
**Role:** BE.
**File:** `includes/auth.php`, `actions/login_process.php`, `includes/csrf.php`, `index.php`.

**Bagian kode:**

1. `auth_boot()` — `includes/auth.php` baris 14–42: nama cookie `BTDSID`, `httponly => true`, `samesite => 'Lax'`, plus idle timeout `AUTH_IDLE_TIMEOUT = 7200` (baris 11) → sesi kedaluwarsa setelah 2 jam tidak aktif.
2. `require_login()` — baris 108–123: bila belum login → `redirect('login.php')`; untuk request AJAX mengembalikan JSON `401`.
3. `require_role()` — baris 130–151:

```php
$ok = ($role === 'admin')
    ? ($user['role'] === 'admin')                       // hanya admin
    : in_array($user['role'], ['admin','petugas'], true); // admin ATAU petugas
if (!$ok) { http_response_code(403); ... exit; }
```

4. `current_user()` — baris 58–88: mengambil data user dari DB berdasarkan `$_SESSION['user_id']`; jika `status !== 'aktif'` sesi langsung dihancurkan (baris 82–85) → menonaktifkan akun langsung berefek walau sesi masih hidup.
5. `index.php` baris 10–13: admin → `admin/dashboard.php`, selain itu → `petugas/dashboard.php`.

**Penjelasan sederhana:** Setiap halaman memanggil guard-nya sendiri: halaman admin memakai `require_role('admin')`, halaman bersama memakai `require_role('petugas')`. Artinya petugas **tidak bisa** membuka halaman admin walaupun ia mengetik URL-nya langsung.

**Alur kerja:** isi form → `POST actions/login_process.php` → `require_post()` + `verify_csrf()` → cek `password_verify` → `session_regenerate_id(true)` → simpan `user_id/username/role` → redirect sesuai role.

**Demo:**
1. Buka `admin/users/index.php` dalam keadaan belum login → ter-redirect ke `login.php` (sudah diverifikasi: HTTP 302).
2. Login sebagai petugas, ketik manual `…/admin/pengaturan.php` → muncul halaman **403 Akses Ditolak**.

**Pertanyaan penguji:**
- *Bagaimana memastikan petugas tidak membuka menu admin?* → Guard server `require_role('admin')` di setiap file admin + menu sidebar disesuaikan role (`includes/sidebar.php` baris 13–53). Menyembunyikan menu saja tidak cukup, karena itu ada 403.
- *Apa yang mencegah sesi dicuri setelah login?* → `session_regenerate_id(true)` (`actions/login_process.php` baris 42) — anti session fixation; cookie `httponly` + `samesite=Lax`.
- *Kenapa akun nonaktif langsung keluar?* → `current_user()` mengecek `status` setiap request (baris 82–85).
- *Bagaimana kalau password salah berulang kali?* → Ada jeda 0,6 detik + pencatatan ke log (`usleep(600000)`, baris 29–30). **Belum ada** mekanisme lockout akun — ini keterbatasan yang harus diakui.

---

## M6. Login: input → proses → output

**Nama materi:** Autentikasi pengguna.
**Role:** BE.
**File:** `actions/login_process.php` (baris 1–52) bersama `login.php` (form, baris 48–76).

**Bagian kode penting:**
- baris 8–9: `require_post(); verify_csrf();`
- baris 24–26: `SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1` (prepared)
- baris 28–33: `password_verify($password, $user['password'])`, `usleep(600000)`, `log_aktivitas('Percobaan login gagal…')`
- baris 35–39: tolak `status !== 'aktif'`
- baris 42: `session_regenerate_id(true)`
- baris 44–52: simpan sesi → log → flash → `redirect('index.php')`

**Penjelasan sederhana:** Password **tidak pernah** dibandingkan sebagai teks; yang disimpan di database adalah hash bcrypt (`$2y$…`) dan PHP memverifikasinya lewat `password_verify()`. Pesan galat selalu "Username atau password salah" (tidak memberi tahu mana yang salah) supaya tidak membocorkan daftar user.

**Alur kerja:** input username+password → cek CSRF → query user → verifikasi hash → cek status → regenerasi sesi → set `$_SESSION` → redirect `index.php` → `index.php` mengarahkan sesuai role.

**Demo:** login salah → muncul alert merah + lihat `admin/aktivitas.php` ada baris "Percobaan login gagal"; login benar → dashboard sesuai role.

**Pertanyaan penguji:**
- *Di mana password disimpan?* → hash bcrypt di `users.password`, dibuat dengan `password_hash()` (`actions/user_process.php` baris 63).
- *Kenapa bisa login pakai email juga?* → query `WHERE username = ? OR email = ?` (baris 24).
- *Bagaimana kalau sesi lama dipakai ulang?* → ID sesi diganti saat login (`session_regenerate_id(true)`).

---

## M7. CSRF & validasi input (pengaman semua form)

**Nama materi:** Token CSRF & sanitasi input.
**Role:** BE.
**File:** `includes/csrf.php` (39 baris), `includes/functions.php` (baris 98–116, 39–42).

**Bagian kode:**
- `csrf_token()` baris 14–20: `bin2hex(random_bytes(32))` per sesi.
- `csrf_field()` baris 23–26: menyisipkan input tersembunyi di setiap form.
- `verify_csrf()` baris 29–36: `hash_equals()` → bila tidak cocok `403`.
- `require_post()` baris 39–45: bila bukan POST → `405`.
- `e()` (`includes/functions.php` baris 39–42): `htmlspecialchars` → anti XSS saat output.

**Penjelasan sederhana:** Semua form membawa "tanda tangan" rahasia dari sesi. Situs lain yang menanam form ke aplikasi kita tidak bisa meniru tanda itu, jadi permintaannya ditolak 403. Hasil audit: `POST /actions/login_process.php` tanpa token → **403**, `GET` ke file aksi → **405** (dikuatkan lewat pengujian read-only, lihat QA).

**Alur kerja:** halaman menampilkan `csrf_field()` → user submit → `verify_csrf()` membandingkan dengan nilai sesi → lolos → proses data.

**Demo:** DevTools → inspect form → tunjukkan input `csrf_token` (64 karakter hex); kirim POST tanpa token lewat curl → 403.

**Pertanyaan penguji:**
- *Kenapa memakai `hash_equals`?* → untuk membandingkan string pada waktu konstan (mencegah timing attack).
- *Validasi dilakukan di mana?* → **di server**: `mb_strlen()`, `filter_var(FILTER_VALIDATE_EMAIL)`, whitelist `in_array(...)`; sisi client (atribut `required`, `maxlength`) hanya kenyamanan.

---

## M8. CRUD data tamu (contoh proses bisnis)

**Nama materi:** Create–Read–Update–Delete data tamu.
**Role:** BE.
**File:** `actions/tamu_process.php` (141 baris), halaman: `includes/modules/tamu/index.php`, `tambah.php`, `edit.php`, `detail.php` (diakses via wrapper `admin/tamu/*` dan `petugas/tamu/*`).

**Bagian kode:**
- Guard: baris 8–10 `require_role('petugas'); require_post(); verify_csrf();`
- Cegah open redirect: fungsi `safe_return_path()` baris 18–24 (regex hanya mengizinkan path `admin/…` atau `petugas/…`).
- **Tambah** baris 29–61: validasi nama ≥3 karakter, whitelist jenis identitas, cek format email, kode unik via `gen_kode_unique('visitors','kode_tamu','TM')`, `INSERT` prepared, `log_aktivitas()`.
- **Ubah** baris 64–102: ambil data lama dulu (`SELECT * … WHERE id = ?`) → validasi → `UPDATE … WHERE id = ?`.
- **Hapus** baris 105–138: khusus admin (`!is_admin()` → ditolak, baris 106–109) dan **ditolak bila tamu punya riwayat kunjungan** (baris 121–129) — sejalan dengan FK `ON DELETE RESTRICT`.

**Penjelasan sederhana:** Setiap aksi memakai satu file `*_process.php` yang dibedakan oleh input `action` (`tambah`/`edit`/`hapus`). Sebelum menghapus, sistem mengecek riwayat; kalau ada kunjungan, data tidak dihapus agar laporan tetap utuh.

**Alur kerja:** form (`GET tambah.php`) → POST `action=tambah` → validasi → `INSERT` → log → redirect ke daftar dengan pesan sukses (flash message).

**Demo:** tambah tamu dengan nama 2 karakter → muncul peringatan validasi; lihat tabulasi `visitors` bertambah; coba hapus tamu yang punya riwayat → ditolak.

**Pertanyaan penguji:**
- *Bagaimana mencegah SQL injection?* → Semua query memakai `prepare()` + `execute([...])`; nilai tidak pernah digabung ke string SQL.
- *Bagaimana kode tamu dibuat unik?* → `gen_kode_unique()` (`includes/functions.php` baris 184–196): prefiks + tanggal + acak, dicek ke DB, maksimal 12 percobaan.
- *Kenapa hapus hanya admin?* → keputusan bisnis: petugas hanya mengelola data operasional; di kode `is_admin()` baris 106.

> Pola yang sama persis dipakai mitra (`actions/mitra_process.php`), registrasi (`actions/registrasi_process.php`), dan user (`actions/user_process.php`) — cukup dijelaskan sekali, lalu tunjukkan perbedaan validasinya (mis. user: username regex `^[a-zA-Z0-9._]{3,50}$` dan password min 8, baris 29–36).

---

## M9. Check-in kunjungan (QR, manual, walk-in)

**Nama materi:** Proses check-in.
**Role:** BE.
**File:** `actions/kunjungan_process.php` (204 baris) + form `includes/modules/kunjungan/tambah.php`.

**Bagian kode penting:**

1. Guard & aksi (baris 9–16): `require_role('petugas')`, lalu `action = 'checkin'` atau `'simpan_hasil'`.
2. Validasi input (baris 86–93): `tujuan` dan `pegawai_ditemui` wajib ≥3 karakter; `hasil_kunjungan` opsional maksimal `HASIL_KUNJUNGAN_MAX` = 4000 (`includes/functions.php` baris 13).
3. Transaksi dimulai (baris 98): `$pdo->beginTransaction()`.
4. **Validasi token QR di server** (baris 107–153):

```php
$st = $pdo->prepare("SELECT q.id AS qr_id, q.status, q.valid_from, q.valid_until,
        rv.id AS reg_id, rv.status AS reg_status, ...
     FROM visitor_qr_codes q
     JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
     JOIN visitors v ON v.id = rv.visitor_id
     WHERE q.token_hash = ? FOR UPDATE");
$st->execute([hash_token($token)]);      // token dari browser -> di-hash dulu
```

   Pemeriksaan berurutan: QR ada? status `aktif`? belum lewat `valid_from`? belum lewat `valid_until`? registrasi `aktif`? mitra tidak `nonaktif`? Semua gagal → `RuntimeException` → `rollBack` → pesan ditampilkan (baris 128–145, 210–222). **Identitas tamu diambil dari database, bukan dari form** (baris 147–153).
5. **Mode manual** (baris 155–192): pilih `visitor_id` (dicek ulang ke DB) atau **walk-in** → insert `visitors` baru dengan kode `TM-…`.
6. **Simpan kunjungan** (baris 196–207): kode kunjungan `KT-…` + `INSERT INTO visits (…, jam_masuk, status 'berlangsung', petugas_masuk_id)` dengan `NOW()` di sisi database.
7. Commit, lalu log + notifikasi + flash (baris 209–233).

**Penjelasan sederhana:** "Token QR dari browser tidak dipercaya. Server meng-hash ulang token, mencocokkannya dengan `token_hash` di database, lalu memvalidasi status dan masa berlaku. Identitas tamu diambil dari database, jadi mengganti `visitor_id` di form tidak akan mengubah siapa pemilik QR."

**Alur kerja:**
- QR: pindai (`scanner.js`) → redirect `tambah.php?token=…` → form terisi readonly → POST `mode=qr` → validasi ulang → `INSERT visits` → redirect ke `detail.php`.
- Manual: pilih tamu / isi tamu baru → POST `mode=manual` → `INSERT visits`.
- Walk-in: buat `visitors` baru + `visits` dalam **satu transaksi**.

**Demo:** login petugas → Scan QR → isi tujuan & pegawai → Check-in → buka `detail.php` → tunjukkan kode kunjungan `KT-…` dan jam masuk.

**Pertanyaan penguji:**
- *Apa bedanya check-in via QR dan manual?* → QR: identitas sudah pasti berasal dari registrasi terverifikasi; manual: petugas memilih tamu yang sudah ada atau memasukkan tamu baru.
- *Kenapa transaksi DB dipakai?* → supaya data tamu baru + kunjungannya tersimpan bersamaan atau tidak sama sekali (konsistensi data).
- *Bagaimana kalau QR sudah kedaluwarsa?* → Ditolak server dengan pesan "QR sudah kedaluwarsa (s.d. …)" dan form tersedia untuk pendaftaran manual (baris 137–139 + `includes/modules/kunjungan/tambah.php` baris 89–104).
- *Di mana jam masuk diambil?* → `NOW()` di database, bukan dari browser (lihat catatan di form: "Waktu masuk diambil dari waktu server").

---

## M10. Check-out, durasi, dan validasi jaminan

**Nama materi:** Proses check-out aman.
**Role:** BE.
**File:** `actions/checkout_process.php` (94 baris).

**Bagian kode (urutan validasi sesuai komentar baris 2–10):**

1. baris 13–15: `require_role('petugas'); require_post(); verify_csrf();`
2. baris 32: `SELECT * FROM visits WHERE id = ? FOR UPDATE` — **kunci baris** saat transaksi.
3. baris 41–46: bila `status !== 'berlangsung'` → "Check-out ganda tidak diizinkan."
4. baris 49–69: `SELECT … FROM guarantees WHERE visit_id = ? AND status = 'aktif'` → bila ada → `rollBack`, log `Check-out DITOLAK`, notifikasi, pesan error, redirect ke detail.
5. baris 72–73: durasi = `(jam keluar − jam masuk)` dalam menit.
6. baris 82–95: `UPDATE visits SET jam_keluar=?, durasi_menit=?, status='selesai', petugas_keluar_id=? … WHERE id=? AND status='berlangsung'` — syarat status ikut disertakan (buckling double-check).
7. baris 111–115: log aktivitas + flash sukses + redirect.

**Penjelasan sederhana:** "Check-out ditolak di **server** bukan hanya tombolnya yang disembunyikan di layar. Ada tiga lapis: harus login & berhak, kunjungan masih `berlangsung`, dan semua jaminan sudah `dikembalikan`."

**Alur kerja:** tekan tombol Check-out di `detail.php` → POST `id` → transaksi + `FOR UPDATE` → cek status → cek jaminan → update (jam keluar, durasi, status) → commit → log.

**Demo:**
1. Kunjungan yang masih punya jaminan aktif → tekan Check-out → muncul pesan merah dan status tetap `berlangsung`.
2. Kembalikan jaminan → Check-out lagi → sukses, durasi tampil.

**Pertanyaan penguji:**
- *Apa gunanya `FOR UPDATE`?* → mengunci baris selama transaksi sehingga dua petugas tidak bisa check-out bersamaan (mencegah race condition/check-out ganda).
- *Kenapa tombol Check-out bisa disabled tapi tetap divalidasi server?* → UI hanya kenyamanan; validasi tetap di `checkout_process.php` (lihat juga `includes/modules/kunjungan/detail.php` baris 238–240: "Tombol dinonaktifkan - server tetap memvalidasi saat dikirim").
- *Bagaimana durasi dihitung?* → selisih detik dibagi 60, dibulatkan ke bawah (baris 73), disimpan ke `durasi_menit`, ditampilkan `format_durasi()` (`includes/functions.php` baris 159–170).

---

## M11. Jaminan (barang titipan) & upload bukti

**Nama materi:** Manajemen jaminan.
**Role:** BE.
**File:** `actions/jaminan_process.php` (132 baris), `includes/functions.php` fungsi `upload_bukti()` (baris 306–344), form `includes/modules/jaminan/tambah.php`.

**Bagian kode:**
- **Tambah** (baris 23–68): wajib `visit_id` + `barang` ≥2 karakter; `jumlah` dibatasi 1–999; kunjungan **harus masih `berlangsung`** (baris 45–48); upload bukti opsional; `INSERT` dengan `status='aktif'`, `diterima_oleh = current_user_id()`, `diterima_at = NOW()`.
- **Kembalikan** (baris 71–127): transaksi + `FOR UPDATE` (baris 81) → tolak bila bukan `aktif` (baris 88–90) → `UPDATE … status='dikembalikan', dikembalikan_at, dikembalikan_oleh` → hitung sisa jaminan aktif → pesan "kunjungan siap di-check-out" bila 0 (baris 117–125).
- **Hapus** (baris 130–156): **khusus admin** (baris 131–134) dan hanya jaminan `dikembalikan` (baris 145–148).
- **Upload** (`upload_bukti()`): maksimal `MAX_UPLOAD_MB` (2 MB, `config/database.php` baris 58), cek MIME asli via `finfo` — hanya `image/jpeg`, `image/png`, `application/pdf` (baris 321–331), nama file diganti dengan nama acak, disimpan ke `uploads/bukti-jaminan/`.

**Penjelasan sederhana:** Jaminan adalah "kunci" check-out. Saat menerima KTP/dokumen, petugas mencatatnya (opsional foto bukti). Selama status jaminan `aktif`, check-out ditolak. Setelah dikembalikan, sistem memberi tahu sisa jaminan.

**Alur kerja:** `jaminan/tambah.php?visit_id=…` → POST (multipart) → validasi → upload (bila ada) → `INSERT guarantees` → log → redirect ke detail kunjungan.

**Demo:** pada kunjungan aktif, tambah jaminan "KTP" → tombol Check-out jadi nonaktif → tekan Check-out → ditolak → "Kembalikan" → Check-out sukses.

**Pertanyaan penguji:**
- *Bagaimana keamanan upload file?* → Ukuran dibatasi, MIME dicek dengan `finfo` (bukan ekstensi), nama file diganti, eksekusi script dilarang lewat `.htaccess`.
- *Kenapa jaminan yang sudah dikembalikan tidak dihapus otomatis?* → dipertahankan sebagai riwayat; penghapusan hanya admin dan hanya status `dikembalikan`.
- *Apa itu `diterima_oleh`/`dikembalikan_oleh`?* → FK ke `users` (siapa petugas yang menerima/mengembalikan), mendukung akuntabilitas.

---

## M12. Token QR: hash SHA-256 dan tampil sekali

**Nama materi:** Pengolahan token QR.
**Role:** BE.
**File:** `actions/qr_process.php` (125 baris), `includes/functions.php` baris 202–230, `admin/qr/tampil.php`.

**Bagian kode:**
- `random_token()` baris 203–206: `bin2hex(random_bytes(32))` → 64 karakter hex (256 bit).
- `hash_token()` baris 208–212: `hash('sha256', $token)`.
- Penerbitan (`actions/qr_process.php` baris 69–78): token dibuat → **hanya hash yang di-INSERT** → token mentah disimpan di sesi lewat `flash_token_set('qr_token_'.$qrId, $token)`.
- Masa berlaku (baris 49–67): default dari `setting('qr_default_days')`, divalidasi `valid_until > valid_from`.
- Pencabutan (baris 91–115) & penghapusan (baris 118–145) — penghapusan ditolak bila QR pernah dipakai kunjungan (baris 130–137).
- Tampil (`admin/qr/tampil.php` baris 11, 30–32): token diambil dari sesi **sekali pakai** (`flash_token_get`), digambar jadi PNG base64; jika sesi sudah habis → "Gambar QR tidak dapat ditampilkan ulang" (baris 89–101).

**Penjelasan sederhana:** "Kalau database bocor, penyerang hanya menemukan hash SHA-256 yang tidak bisa dipakai sebagai token. Token mentah ditampilkan hanya satu kali saat penerbitan, lalu hilang dari sesi."

**Alur kerja:** admin menerbitkan → token dibuat → hash disimpan → gambar QR tampil sekali → tamu menampilkan QR → petugas memindai → server meng-hash ulang dan mencocokkan.

**Demo:** menu QR Code → Terbitkan → tunjukkan gambar QR + peringatan "hanya sekali" → refresh halaman tampil → gambar hilang.

**Pertanyaan penguji:**
- *Kenapa tidak menyimpan token asli?* → agar token tidak bisa dipakai ulang oleh penyerang yang membaca database.
- *Kenapa hash SHA-256, bukan bcrypt?* → Token adalah rahasia acak 256-bit (bukan password buatan manusia), jadi satu kali hash cukup dan diperlukan pencocokan cepat via index `UNIQUE token_hash` (`database.sql` baris 135).
- *Bagaimana kalau QR hilang/dicabut?* → dicabut lewat tombol Cabut; penerbitan baru diperlukan (token lama tidak bisa ditampilkan ulang).

---

## M13. Laporan, cetak, dan ekspor CSV

**Nama materi:** Pengolahan laporan & ekspor.
**Role:** BE.
**File:** `includes/laporan_helper.php`, `admin/laporan/index.php`, `admin/laporan/ekspor_csv.php`, `includes/functions.php` baris 351–379.

**Bagian kode:**
- `laporan_build_filter()` (`laporan_helper.php` baris 14–59): tanggal divalidasi regex `^\d{4}-\d{2}-\d{2}$` (baris 23–28), `status` di-whitelist (baris 29–31), nilai masuk sebagai **parameter prepared statement** (bukan string SQL).
- Halaman laporan: agregat `COUNT`/`SUM(durasi_menit)` baris 12–28; tombol `window.print()` baris 73; tautan ekspor baris 76–79.
- Ekspor (`ekspor_csv.php`): `require_role('admin')` baris 9, filter memakai helper yang **sama** baris 11, `LIMIT 5000` baris 26, header kolom baris 31–36, `send_csv()` baris 71.
- `send_csv()` (`includes/functions.php` baris 361–379): header `Content-Disposition: attachment`, **BOM UTF-8** `\xEF\xBB\xBF` (baris 372) supaya Excel membaca rapi, `fputcsv()` untuk tiap baris.
- `csv_cell()` baris 351–358: sel yang diawali `=`, `+`, `-`, `@` diberi kutip depan → **anti CSV formula injection**.

**Penjelasan sederhana:** Filter laporan dibangun oleh satu fungsi bersama sehingga hasil layar dan hasil CSV **identik**. CSV diberi BOM agar tidak berantakan di Excel, dan karakter yang bisa dieksekusi sebagai formula di Excel dinetralkan.

**Alur kerja:** `?dari=…&sampai=…&status=…` → `laporan_build_filter()` → `WHERE` + parameter → query → tampil (atau ditulis ke `php://output` sebagai CSV) → log "Mengunduh CSV laporan …".

**Demo:** buka Laporan → filter bulan ini → Cetak (pratinjau print) → Ekspor CSV → buka file CSV di Excel (kolom tidak berantakan).

**Pertanyaan penguji:**
- *Kenapa CSV perlu BOM?* → Excel membutuhkan BOM UTF-8 untuk menampilkan karakter Indonesia (dan teks dari DB) dengan benar.
- *Apa itu CSV injection?* → sel diawali `=` bisa menjalankan formula saat file dibuka; dinetralkan dengan awalan kutip di `csv_cell()`.
- *Kenapa ekspor dibatasi 5000 baris?* → agar server tetap responsif; laporan berjalan di periode terbatas (baris 13–14).
- *Siapa yang boleh mengunduh?* → hanya admin (`require_role('admin')`).

---

## M14. Audit log, notifikasi, dan pengaturan

**Nama materi:** Jejak audit & konfigurasi.
**Role:** BE.
**File:** `includes/functions.php` baris 236–296, `admin/aktivitas.php`, `includes/navbar.php` baris 12–29, `actions/settings_process.php`.

**Bagian kode:**
- `log_aktivitas()` baris 236–255: INSERT ke `activity_logs` (user, username, role, aktivitas, IP, user agent) — kegagalan log **tidak menggagalkan proses utama** (baris 252–254).
- `setting()`/`set_setting()` baris 261–285: cache per request; `set_setting` memakai `ON DUPLICATE KEY UPDATE` (MySQL) / `ON CONFLICT` (PostgreSQL) — cabang driver DB_DRIVER.
- `notify()` baris 291–296; lonceng di `includes/navbar.php` (query `user_id = ? OR user_id IS NULL`, baris 16–21).
- Pengaturan (`actions/settings_process.php` baris 24–43): nama instansi wajib, jam divalidasi regex `^\d{2}:\d{2}$`, `qr_default_days` 1–3650.

**Penjelasan sederhana:** Setiap aksi penting (login, CRUD, check-in/out, ekspor, ganti password) menyimpan jejak ke `activity_logs` sehingga admin bisa menelusuri "siapa, kapan, dari IP apa".

**Demo:** jalankan beberapa aksi → buka `admin/aktivitas.php` → filter tanggal/pengguna.

**Pertanyaan penguji:**
- *Kenapa log tidak membuat aplikasi error bila tabel bermasalah?* → karena dibungkus `try/catch` dan hanya `error_log()`.
- *Bagaimana pengaturan disimpan?* → tabel `settings` (key–value), dibaca ulang oleh `setting()` di setiap halaman.

---

## M15. Ringkasan keamanan (untuk slide terakhir BE)

| Serangan | Pengaman | Lokasi kode |
|---|---|---|
| SQL injection | Prepared statement + `EMULATE_PREPARES=false` | seluruh `actions/*`, `config/database.php` baris 102 |
| XSS | Output escaping `e()` = `htmlspecialchars` | `includes/functions.php` baris 39–42 |
| CSRF | Token per sesi + `hash_equals` + hanya POST | `includes/csrf.php` |
| Sesi curi | `httponly`, `samesite=Lax`, regenerasi ID login, idle timeout 2 jam | `includes/auth.php` baris 14–42, `login_process` baris 42 |
| Password bocor | `password_hash()` bcrypt, tidak ada password teks di skema | `actions/user_process.php` baris 63; `database.sql` baris 36–37 |
| Manipulasi QR | Validasi ulang token di server, identitas dari DB | `actions/kunjungan_process.php` baris 107–153 |
| Check-out ganda / race | Transaksi + `FOR UPDATE` + syarat status di UPDATE | `actions/checkout_process.php` baris 32, 91 |
| Upload berbahaya | Cek ukuran + `finfo` MIME + nama acak + `.htaccess` tolak eksekusi | `includes/functions.php` baris 306–344; `uploads/bukti-jaminan/.htaccess` |
| Open redirect | `safe_return_path()` regex | `actions/tamu_process.php` baris 18–24 |
| CSV injection | `csv_cell()` | `includes/functions.php` baris 351–358 |
| Akses file sensitif | *(belum sempurna — lihat temuan QA BUG-01/BUG-02)* | `.env`, `backup_buku_tamu_terbaru.sql` |

**Pertanyaan penguji:** *Apa kelemahan keamanan yang belum tertangani?* → Jujur: (1) file `.env` dan backup SQL masih bisa diakses lewat browser (perlu `.htaccess`/penempatan di luar web root), (2) folder upload masih berada dalam web root padahal `README.txt` menyatakan "di luar web root publik", (3) belum ada lockout akun setelah login gagal berulang.
