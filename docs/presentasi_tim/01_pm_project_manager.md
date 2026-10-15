# BAB III.A — Materi Presentasi: PM (Project Manager)

**Peran PM dalam tim:** memastikan project paham masalahnya, urutannya jelas, semua role tahu tugasnya, dan hasil akhirnya bisa dipertanggungjawabkan — **bukan** berarti PM mengerjakan semua kode.

> **Catatan kontribusi individu (wajib dibaca)**
> Pada saat audit 14 Okt 2026, Git hanya berisi **2 commit dengan 1 penulis** (`risqy`). Namun saat pengecekan ulang **15 Okt 2026**, sudah ada **3 commit oleh 2 penulis** (`risqy` dan `Bintangpa`, commit `excel` yang menambahkan ekspor Excel) plus banyak perubahan yang **belum di-commit** (lihat `05_qa_quality_assurance.md` bag. 8). Artinya kontribusi memang bergerak, tetapi **tetap tidak ada dokumen** yang mencatat siapa mengerjakan bagian mana. Karena itu pembagian di bawah adalah **rekomendasi tanggung jawab presentasi**, bukan klaim siapa membuat apa. Tim harus mengonfirmasi ulang sebelum presentasi (lihat bagian 6).

---

## 1. Tujuan presentasi PM

**Perkiraan durasi presentasi PM:** ±6 menit urutan lisan (bagian 2) + ±2 menit pembuka/penutup = ±8 menit.

- Menjelaskan **mengapa** project ini dibuat (masalah → solusi).
- Menunjukkan **ruang lingkup** yang jelas: apa yang masuk, apa yang tidak.
- Menjelaskan **alur kerja tim** dari analisis → desain → coding → testing → rilis.
- Menunjukkan **bukti nyata** project: struktur folder, README, jumlah modul, hasil test.
- Menutup dengan **hasil akhir & keterbatasan** yang jujur.

## 2. Urutan penjelasan lisan (±6 menit)

| Menit | Bicara | Yang ditunjukkan |
|---|---|---|
| 0.0–1.0 | Masalah & latar belakang | `README.md` baris 1–11, `00_gambaran_project.md` bag. 2–3 |
| 1.0–2.0 | Tujuan & ruang lingkup | `README.md` bag. "Struktur Proyek", daftar folder |
| 2.0–3.0 | Teknologi & alasan pemilihan | `composer.json`, `config/database.php`, `assets/vendor/` |
| 3.0–4.5 | Pembagian tugas 5 role & alur kerja | bagian 3 dokumen ini (tabel) |
| 4.5–5.5 | Prioritas fitur (MVP → lanjutan) | bagian 4 dokumen ini + fitur yang tampil di dashboard |
| 5.5–6.0 | Hasil akhir, pengujian, keterbatasan | `README.md` bag. 8, `tests/`, bagian 6 dokumen ini |

## 3. Pembagian tugas tim (rekomendasi — untuk dikonfirmasi)

| Role | Tanggung jawab ideal | Hasil pekerjaan yang terlihat di project (bukti file) |
|---|---|---|
| **PM** | Perencanaan, pembagian tugas, prioritas fitur, koordinasi, presentasi latar belakang & kesimpulan | `README.md` (struktur, instalasi, aturan revisi mentor), `docs/presentasi_tim/` |
| **BE** | Arsitektur backend, koneksi PDO, skema database, seluruh `actions/`, auth/role/CSRF, laporan & ekspor | `config/database.php`, `includes/auth.php`, `includes/csrf.php`, `includes/functions.php`, seluruh `actions/*.php`, `database.sql`, `setup/create_admin.php` |
| **FE** | Layout, halaman, tampilan dashboard/form/tabel, interaksi JS, responsivitas | `includes/header.php`, `navbar.php`, `sidebar.php`, `footer.php`, `assets/css/style.css`, `assets/js/app.js`, `assets/js/scanner.js`, tampilan di `includes/modules/*` |
| **Analyst** | Analisis kebutuhan, aktor & hak akses, alur bisnis, relasi database, dokumentasi diagram | `database.sql` (relasi/FK), `database/supabase_schema.sql`, `README.md` bag. 3–5, catatan aturan revisi mentor |
| **QA** | Strategi test, test case, eksekusi test, pelaporan bug | `tests/e2e_test.php`, `tests/hasil_kunjungan_test.php`, `tests/qa_visual.mjs`, `05_qa_quality_assurance.md` |

**Hubungan antaranggota (alur kerja):**

```
Analyst  : menyusun kebutuhan + relasi DB  ─┐
   │                                        ├──> BE membangun proses bisnis (actions/, koneksi, skema)
   ▼                                        │
PM      : menyusun prioritas & jadwal ──────┤
                                            ├──> FE membangun tampilan yang memanggil actions/*
   │                                        │
   └──────────────────────────────────────> QA menguji hasil BE+FE memakai tests/*
                                              └──> temuan bug -> kembali ke BE/FE -> diuji ulang
```

**Yang belum memiliki bukti dokumentasi (jangan diklaim sudah ada):**

- Dokumen jadwal/sprint/rapat (Gantt, notulen, backlog) — **belum ditemukan**.
- Dokumen pembagian tugas bernama anggota — **belum ditemukan**.
- Hasil test yang diarsipkan (screenshot/berkas hasil run) — **tidak ada di repo** (hanya skripnya).
- Dokumen ERD/use case/activity diagram — **belum ditemukan** (lihat `04_analyst_system_analyst.md`).

## 4. Prioritas fitur (untuk dijelaskan)

Gunakan logika "apa yang paling dulu membuat aplikasi bisa dipakai":

1. **Prioritas 1 (MVP):** login + role, data tamu, check-in manual, daftar kunjungan, check-out, dashboard.
2. **Prioritas 2:** jaminan & aturan blokir check-out, QR terbitkan + scanner, monitoring kunjungan aktif.
3. **Prioritas 3:** laporan (filter, cetak, CSV), hasil kunjungan, notifikasi, log aktivitas, pengaturan instansi.
4. **Prioritas 4 (lanjutan/migrasi):** skema Supabase/PostgreSQL (`database/supabase_schema.sql`), upload bukti jaminan, fitur polish UI.

Bukti pendukung bahwa prioritas itu benar-benar diikuti: `README.md` memuat 3 "aturan revisi mentor" yang berurutan — (1) alur QR, (2) check-out & jaminan, (3) laporan/cetak/CSV — diikuti migrasi kolom hasil kunjungan (`database/migrations/001_tambah_hasil_kunjungan.sql`).

## 5. Materi: bukti manajemen proyek yang benar-benar ada

**Nama materi:** Bukti perencanaan & dokumentasi project.
**Role:** PM.
**File yang ditunjukkan:**
- `README.md` — instalasi, alur QR, aturan check-out/jaminan, laporan, struktur proyek, cara test, rencana migrasi Supabase.
- `composer.json` — deskripsi project & dependensi.
- `database/migrations/001_tambah_hasil_kunjungan.sql` — bukti perubahan terkontrol (migrasi inkremental, "aman, hanya menambah kolom").
- `tests/` — bukti ada tahap pengujian.
- `git log` — riwayat perubahan (audit 14 Okt: 2 commit, 1 penulis; per 15 Okt: 3 commit, 2 penulis — tetap konfirmasi ke tim).

**Bagian kode:** `README.md` baris 1–11 (ringkasan teknologi), baris 92–148 (aturan revisi mentor #1–#3), baris 151–178 (struktur proyek + keamanan), baris 182–208 (cara menjalankan test).

**Penjelasan sederhana:** "Dokumen README menjadi satu-satunya dokumen manajemen yang ada: isinya apa project ini, cara memasang, aturan bisnis yang disepakati mentor, struktur folder, dan cara menjalankan test. Selain itu ada file migrasi yang menunjukkan perubahan skema dilakukan terkontrol, bukan menghapus data."

**Alur kerja:** kebutuhan/mentor → dicatat di README → dipecah ke folder (`actions/`, `includes/modules/`, `assets/`) → diuji lewat `tests/`.

**Demo:** buka `README.md` (VS Code), tunjukkan 3 bagian "aturan revisi mentor", lalu buka folder `tests/` dan `database/migrations/`.

**Pertanyaan penguji:**
- *Di mana jadwal projectnya?* → "Belum ada dokumen jadwal di repo; yang ada adalah urutan revisi mentor di README. Tim akan melengkapi."
- *Siapa yang mengerjakan bagian BE?* → "Di Git tercatat hanya satu akun penulis, jadi pembagian per orang belum bisa dibuktikan dari repo. Tim memakai pembagian role dan akan mengonfirmasinya."

## 6. Bagian yang HARUS dilengkapi PM sebelum hari-H

- [ ] Tulis 1 paragraf latar belakang sesuai instansi nyata (mis. kantor kelurahan/sekolah/perusahaan) — jangan memakai istilah umum saja.
- [ ] Buat tabel pembagian tugas **bernama anggota** + konfirmasi tanda tangan/persetujuan guru.
- [ ] Siapkan jadwal sederhana (minggu 1 analisis, minggu 2 desain DB, dst.) — **belum ada di repo, jangan mengarang bahwa sudah pernah diadakan rapat**.
- [ ] Ambil screenshot bukti (folder `docs/screenshot/` atau PPT): login, dashboard, check-in, QR, laporan.
- [ ] Pastikan akun demo & data demo siap (ikuti `README.md`; jangan menampilkan password di layar presentasi).
- [ ] Catat batasan aplikasi (bagian 7) supaya semua anggota memberi jawaban yang sama.

## 7. Hasil akhir project & keterbatasan (bahan penutup)

**Hasil akhir:**
- Aplikasi web PHP native + MySQL yang sudah bisa dipakai end-to-end: login → input tamu → check-in (QR/manual) → monitoring → jaminan → check-out → laporan/CSV.
- Keamanan yang terpasang: role guard, CSRF, prepared statement, hash password, hash token QR, audit log.
- Ada otomatisasi pengujian: 2 suite test CLI + 1 skrip visual.

**Keterbatasan (jujur, sesuai kode):**
1. Hanya 2 role (admin, petugas) — tidak ada role superuser/tamu/pimpinan.
2. Belum ada fitur laporan untuk petugas (menu Laporan hanya admin).
3. Belum ada reset password via email, belum ada penguncian akun setelah gagal login berulang (yang ada hanya jeda 0,6 detik — `actions/login_process.php` baris 29).
4. Belum ada fitur antrean/reservasi jadwal dan notifikasi email/WA.
5. Belum ada dokumentasi analisis (ERD/use case) dalam bentuk file gambar.
6. Manajemen kontribusi tim belum terekam di version control.
