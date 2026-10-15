# BAB III.D — Materi Presentasi: Analyst (System Analyst)

**Tujuan presentasi Analyst:** menunjukkan bahwa fitur aplikasi lahir dari analisis kebutuhan yang bisa **ditelusuri ke kode dan skema database**.

**Perkiraan durasi presentasi Analyst:** ±10 menit.

> **Status dokumen analisis:** di dalam repo **belum ditemukan** file ERD, use case, activity diagram, atau SRS berbentuk gambar/dokumen terpisah. Yang tersedia sebagai bahan analisis adalah: `README.md`, `database.sql` (skema + relasi), `database/supabase_schema.sql`, `database/demo_data.sql`, kode program, dan `database/migrations/001_tambah_hasil_kunjungan.sql`. Diagram pada bagian 5 adalah **rekomendasi dokumentasi tambahan** yang harus dibuat tim, bukan file yang sudah ada.

---

## 1. Permasalahan sistem lama (bahan pembuka)

Disusun dari fitur yang dibangun (bukan dari klaim tanpa bukti):

| Masalah sistem manual | Akibat | Solusi di aplikasi (bukti kode) |
|---|---|---|
| Buku tamu fisik ditulis tangan | sulit dicari, mudah hilang, tidak bisa rekap | tabel `visitors` + `visits` dengan pencarian & filter (`includes/modules/kunjungan/index.php` baris 24–46) |
| Tidak tahu siapa yang sedang di kantor | monitoring lambat | status `berlangsung` + halaman Kunjungan Aktif (`includes/modules/kunjungan/aktif.php`) + kartu "Sedang di Kantor" (`admin/dashboard.php` baris 14) |
| Barang titipan tidak dicatat | risiko hilang, tamu pergi tanpa menyerahkan kembali | tabel `guarantees` + aturan **check-out ditolak bila jaminan aktif** (`actions/checkout_process.php` baris 49–69) |
| Identitas tamu bisa diketik sendiri oleh petugas | manipulasi data | validasi ulang token QR di server (`actions/kunjungan_process.php` baris 107–153) |
| Rekap bulanan dihitung manual | lama & salah hitung | halaman laporan + ekspor CSV (`admin/laporan/`) |
| Siapa yang mengubah data tidak diketahui | tidak ada pertanggungjawaban | `activity_logs` + `log_aktivitas()` (`includes/functions.php` baris 236–255) |

**Kalimat pembuka yang bisa dipakai:** "Aplikasi ini dibangun untuk menggantikan buku tamu fisik dan memecahkan lima masalah di atas — setiap masalah punya padanan fitur yang bisa ditunjukkan di kode."

---

## 2. Kebutuhan pengguna

**Pengguna utama (dari kode):**

1. **Admin / Administrator Sistem** — mengelola data master, akun, QR, laporan, pengaturan, dan melihat seluruh log.
2. **Petugas / Resepsionis** — melayani tamu harian: menerima tamu, check-in, memindai QR, mencatat & mengembalikan jaminan, mengisi hasil kunjungan.
3. **Tamu (pengguna tak langsung)** — datang, menampilkan QR, menitipkan barang, menerima layanan. *Tidak memiliki akun aplikasi* — tidak ada tabel akun tamu.
4. **Sistem / DB** — waktu server, notifikasi otomatis (`notify()` pada check-in, `actions/kunjungan_process.php` baris 226–230).

**Kebutuhan yang terlihat di kode:**
- Admin dapat membuat/mengubah/menghapus akun petugas (`actions/user_process.php`).
- Admin dapat mengatur nama instansi, jam layanan, dan masa berlaku QR default (`actions/settings_process.php` baris 38–43).
- Petugas dapat mencatat kunjungan tanpa harus menunggu admin (`require_role('petugas')` pada `actions/kunjungan_process.php`).
- Petugas **tidak** boleh menghapus data master & membuka laporan/pengaturan/log (guard `require_role('admin')` + `is_admin()` di `tamu_process.php` baris 106, `jaminan_process.php` baris 131).

---

## 3. Aktor dan hak akses

| Halaman / Aksi | Admin | Petugas | Bukti guard |
|---|---|---|---|
| Dashboard | ✔ (admin) | ✔ (petugas) | `require_role('admin')` / `require_role('petugas')` |
| Data Tamu (lihat/tambah/ubah) | ✔ | ✔ | `require_role('petugas')` + `is_admin()` hanya untuk **hapus** (`actions/tamu_process.php` baris 106) |
| Hapus tamu | ✔ | ✘ | `actions/tamu_process.php` baris 105–109 |
| Tamu Terdaftar (registrasi) | ✔ | ✘ | `require_role('admin')` (`actions/registrasi_process.php` baris 9) |
| Perusahaan Mitra (tambah/ubah/hapus) | ✔ | ✘ | `require_role('admin')` (`actions/mitra_process.php` baris 9) |
| Penerbitan / cabut QR | ✔ | ✘ | `require_role('admin')` (`actions/qr_process.php` baris 10) |
| Scanner QR | ✘ | ✔ | `require_role('petugas')` (`petugas/qr/scanner.php` baris 12) — *admin juga lolos guard ini* |
| Check-in / Check-out | ✔ | ✔ | `require_role('petugas')` (`kunjungan_process.php` baris 9, `checkout_process.php` baris 13) |
| Jaminan: tambah & kembalikan | ✔ | ✔ | `require_role('petugas')` |
| Hapus jaminan | ✔ | ✘ | `is_admin()` (`actions/jaminan_process.php` baris 131–134) |
| Laporan & ekspor CSV | ✔ | ✘ | `require_role('admin')` (`admin/laporan/index.php` baris 8, `ekspor_csv.php` baris 9) |
| Manajemen user, pengaturan, log aktivitas | ✔ | ✘ | `require_role('admin')` |

**Cara kerja guard:** `includes/auth.php` baris 130–151 — `require_role('admin')` = hanya admin; `require_role('petugas')` = admin **atau** petugas; pelanggaran → halaman **403 Akses Ditolak**.

**Demo:** login petugas → ketik URL `admin/users/index.php` → 403 (bisa didemonstrasikan).

---

## 4. Kebutuhan fungsional & nonfungsional (diterjemahkan ke kode)

### 4.1 Fungsional

| Kebutuhan fungsional | Terjemahan ke fitur | Bukti |
|---|---|---|
| Sistem harus mengidentifikasi pengguna | login username/email + password hash + session | `actions/login_process.php` |
| Sistem harus menyimpan identitas tamu | tabel `visitors` dengan kode unik `TM-…` | `actions/tamu_process.php` baris 50–56 |
| Sistem harus mencatat kedatangan & kepulangan | kolom `jam_masuk`, `jam_keluar`, `durasi_menit`, `status` | `database.sql` baris 160–163 |
| Sistem harus mengenali tamu terdaftar lewat QR | tabel `visitor_qr_codes` (hash + masa berlaku) | `actions/qr_process.php` |
| Sistem harus menahan check-out bila barang belum kembali | pengecekan `guarantees.status='aktif'` | `actions/checkout_process.php` baris 49–69 |
| Sistem harus mencatat hasil akhir kunjungan | kolom `visits.hasil_kunjungan` (opsional, maks 4000) | `database/migrations/001_tambah_hasil_kunjungan.sql`, `actions/kunjungan_process.php` baris 23–66 |
| Sistem harus menghasilkan laporan periode | filter tanggal/status/mitra/petugas + cetak + CSV | `includes/laporan_helper.php` |
| Sistem harus mencatat jejak pengguna | tabel `activity_logs` | `includes/functions.php` baris 236–255 |
| Sistem harus memberi tahu petugas bila ada kunjungan baru | tabel `notifications` + lonceng | `notify()` baris 291–296, `includes/navbar.php` |

### 4.2 Nonfungsional (berdasarkan implementasi)

| Kebutuhan nonfungsional | Bukti implementasi |
|---|---|
| **Keamanan data** — password tidak boleh tersimpan teks | `password_hash()` (`actions/user_process.php` baris 63) |
| **Anti SQL injection** | prepared statement + `ATTR_EMULATE_PREPARES = false` (`config/database.php` baris 102) |
| **Anti XSS** | `e()` = `htmlspecialchars` (`includes/functions.php` baris 39–42) |
| **Anti CSRF** | token per sesi + `hash_equals` (`includes/csrf.php`) |
| **Ketersediaan (offline LAN)** | semua aset frontend di-vendor lokal (`assets/vendor/`) |
| **Kinerja** | query dashboard pakai agregat `COUNT`, list dibatasi `LIMIT`, pagination 15/25 baris |
| **Integritas data** | transaksi + `FOR UPDATE` pada check-in/check-out/pengembalian jaminan |
| **Jejak audit** | log IP + user agent (`includes/functions.php` baris 244–251) |
| **Portabilitas database** | driver `mysql`/pgsql dengan nama tabel & kolom sama (`config/database.php`, `database/supabase_schema.sql`) |
| **Kesesuaian zona waktu** | `date_default_timezone_set` + `SET time_zone = '+07:00'` (`config/database.php` baris 60, 111) |

**Belum ditemukan dalam kode yang diperiksa:** SLA waktu respons, enkripsi data saat diam (AES), backup otomatis, dan autoscaling — bila ditanya, sampaikan sebagai rencana pengembangan.

---

## 5. Analisis proses bisnis: alur check-in → check-out

### 5.1 Alur utama (activity — dapat digambar ulang oleh tim)

```
[ Tamu datang ]
      │
      ├─(A) Punya QR ──> Petugas memindai (scanner.php)
      │                     │ token -> tambah.php?token=...
      │                     ▼
      │              Server validasi token (hash, status, masa berlaku, registrasi, mitra)
      │                     │ valid? ── tidak ──> pesan error + opsi manual
      │                     ▼ ya
      ├─(B) Tamu terdaftar, tanpa QR ──> petugas memilih dari daftar
      │
      └─(C) Tamu baru (walk-in) ──> petugas mengisi identitas baru
                     │
                     ▼
        [ Isi tujuan, pegawai ditemui, keperluan, hasil (opsional) ]
                     │  POST actions/kunjungan_process.php (action=checkin)
                     ▼
        INSERT visits (kode KT-…, jam_masuk=NOW(), status='berlangsung')
                     │
                     ▼
        [ Kunjungan berlangsung ] ──> monitoring: dashboard, Kunjungan Aktif
                     │
        ┌────────────┴─────────────┐
        ▼                          ▼
  Titip barang?              Tidak
        │
  INSERT guarantees (status='aktif')
        │
        ▼
  Coba check-out ──> ada jaminan 'aktif'? ── ya ──> DITOLAK (pesan + log)
        │ tidak
        ▼
  UPDATE visits SET jam_keluar, durasi_menit, status='selesai'
        │
        ▼
  (opsional) isi "Hasil Kunjungan" ──> tampil di daftar, laporan, ekspor CSV
        │
        ▼
  Selesai ──> log aktivitas + notifikasi
```

**Titik validasi penting (disebut saat presentasi):**
1. Sebelum simpan: cek login & role, cek CSRF, cek panjang input (`kunjungan_process.php` baris 86–93).
2. Saat QR: cek hash, status, `valid_from`, `valid_until`, status registrasi, status mitra (baris 128–145).
3. Saat check-out: `FOR UPDATE`, cek status, cek jaminan aktif (checkout_process baris 32–69).

### 5.2 Alternatif & pengecualian (wajib disebut, menunjukkan analisis matang)

- QR kedaluwarsa/dicabut/registrasi nonaktif → ditolak, user ditawari pendaftaran manual (`includes/modules/kunjungan/tambah.php` baris 89–104).
- Check-out ganda → ditolak dengan pesan waktu selesai (`checkout_process.php` baris 41–46).
- Jaminan hanya bisa ditambah saat kunjungan `berlangsung` (`jaminan_process.php` baris 45–48).
- Jaminan hanya bisa dihapus bila sudah `dikembalikan` dan oleh admin (baris 145–148).
- Tamu dengan riwayat tidak bisa dihapus (`tamu_process.php` baris 121–129) — konsisten dengan FK `RESTRICT`.
- Kunjungan lama tanpa hasil kunjungan ditampilkan `—` / "Belum ada hasil kunjungan" (`includes/functions.php` baris 449–473).

---

## 6. Hubungan tamu, kunjungan, instansi, jaminan, pengguna (relasi database aktual)

Sumber: `database.sql`.

```
partner_companies (mitra/instansi)
        │ 1
        │         registered_visitors (tamu terdaftar = pemilik QR)
        │            │ 1
   n────┤            │
        │            n
visitors ────── n visits (kunjungan) ────── n guarantees (jaminan)
   │ 1                │ 1..n                    │
   │                  │                          │ n
   │                  ├──── visitor_qr_codes ────┘ (visits.qr_id -> qr.id)
   │                  │
   │                  ├──── petugas_masuk_id / petugas_keluar_id -> users.id
   │                  └──── mitra_id -> partner_companies.id
   └── registered_visitors.visitor_id (CASCADE)

users ──< notifications, activity_logs, visitor_qr_codes (issued_by/revoked_by),
          guarantees (diterima_oleh/dikembalikan_oleh), visits (petugas)
```

**Daftar PK/FK lengkap (untuk ditunjukkan saat presentasi):**

| Tabel | Primary Key | Foreign Key | Sifat |
|---|---|---|---|
| `users` | `id` | — | `username`, `email` UNIQ |
| `partner_companies` | `id` | — | `kode_mitra` UNIQ |
| `visitors` | `id` | — | `kode_tamu` UNIQ |
| `registered_visitors` | `id` | `visitor_id`→visitors, `mitra_id`→partner_companies | CASCADE / SET NULL |
| `visitor_qr_codes` | `id` | `registered_visitor_id`→registered_visitors, `issued_by`, `revoked_by`→users | CASCADE / SET NULL; `token_hash` UNIQ |
| `visits` | `id` | `visitor_id`→visitors (**RESTRICT**), `registered_visitor_id`, `mitra_id`, `qr_id`, `petugas_masuk_id`, `petugas_keluar_id` | kode_kunjungan UNIK |
| `guarantees` | `id` | `visit_id`→visits (CASCADE), `diterima_oleh`, `dikembalikan_oleh`→users | — |
| `notifications` | `id` | `user_id`→users (CASCADE), NULL = semua | — |
| `activity_logs` | `id` | `user_id`→users (SET NULL) | — |
| `settings` | `id` | — | `key_name` UNIK |

**Penjelasan sederhana untuk penguji:**
- "Satu tamu bisa datang berkali-kali → satu baris `visitors`, banyak baris `visits`."
- "Mitra bersifat opsional: kalau mitra dihapus, `visits.mitra_id` jadi NULL (riwayat tetap ada), tetapi tamu yang punya riwayat tidak bisa dihapus sama sekali (`RESTRICT`)."
- "Hapus registrasi → QR ikut terhapus (CASCADE) tetapi riwayat kunjungan tetap tersimpan (`SET NULL`)."

**Aturan bisnis yang ter-eksekusi di database (constraint):**
- `CHECK (role IN ('admin','petugas'))`, `CHECK (status IN ('aktif','nonaktif'))` pada users.
- `CHECK (status IN ('berlangsung','selesai'))` pada visits.
- `CHECK (status IN ('aktif','dikembalikan'))` pada guarantees.
- Alasan pemilihan VARCHAR+CHECK: portabel ke PostgreSQL (komentar `database.sql` baris 10–12).

---

## 7. Kebutuhan bisnis → fitur (contoh presentasi)

| Kebutuhan bisnis (bahasa pengguna) | Diterjemahkan menjadi | Implementasi |
|---|---|---|
| "Tamu wajib menyerahkan KTP sebelum masuk kerja" | Jaminan wajib dicatat & check-out ditolak sampai dikembalikan | `guarantees` + validasi di `actions/checkout_process.php` |
| "Saya mau tahu berapa lama tamu di dalam" | Kolom `durasi_menit` dihitung saat check-out + durasi berjalan saat masih berlangsung | `checkout_process.php` baris 72–73; `durasi_berjalan()` `includes/functions.php` baris 173–177 |
| "Pimpinan minta rekap bulanan" | Halaman laporan dengan filter periode + ekspor CSV | `admin/laporan/` + `includes/laporan_helper.php` |
| "Jangan sampai petugas bisa menghapus riwayat" | Guard admin-only + cek riwayat sebelum hapus + log audit | `tamu_process.php` baris 105–129; `activity_logs` |
| "Tamu langganan tidak perlu isi data ulang" | Registrasi tamu terdaftar + QR Code | `registered_visitors`, `visitor_qr_codes` |
| "Kalau ada tamu baru, semua petugas harus tahu" | Notifikasi otomatis check-in | `notify(null, 'Kunjungan baru', …)` `kunjungan_process.php` baris 227 |

---

## 8. Rekomendasi dokumentasi tambahan (BELUM ADA — dibuat oleh Analyst)

Buat di folder `docs/analyst/` sebelum hari-H (durasi ±1 hari kerja):

- [ ] **ERD** (Mermaid/Draw.io) dari tabel bagian 6 — sumber `database.sql`.
- [ ] **Diagram Use Case** 2 aktor utama (admin, petugas) + 1 aktor non-login (tamu) dari tabel hak akses bagian 3.
- [ ] **Activity diagram** alur check-in → check-out dari skema bagian 5.1.
- [ ] **Diagram keadaan (state)** kunjungan: `berlangsung → selesai`, dan jaminan `aktif → dikembalikan` (dasar aturan bisnis).
- [ ] **Tabel kebutuhan (RTM)** yang memetakan tiap kebutuhan ke file implementasi (bisa memakai tabel bagian 4).
- [ ] **Catatan keputusan desain (ADR) sederhana**: kenapa PHP native, kenapa token QR di-hash, kenapa `visits` insert baru tiap kedatangan.

> Saat presentasi, jika ditanya "Di mana ERD-nya?" jawab jujur: **"Belum ada sebagai file; kami menyusunnya dari skema `database.sql` — inilah hasilnya (tunjukkan gambar)."** Jangan mengaku file sudah ada sejak awal.
