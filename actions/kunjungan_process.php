<?php
/**
 * actions/kunjungan_process.php
 * Check-in kunjungan (alur QR & manual) & penyimpanan hasil kunjungan.
 * Identitas tamu SELALU diambil ulang dari database - tidak pernah dipercaya dari browser.
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('petugas');
require_post();
verify_csrf();

$area = is_admin() ? 'admin' : 'petugas';
$base = $area . '/kunjungan';

$action = req_str('action');

/* ============================================================
 *  SIMPAN / PERBARUI HASIL KUNJUNGAN
 *  Satu-satunya kolom yang disentuh: visits.hasil_kunjungan.
 *  Boleh kosong (kunjungan berlangsung / belum ada catatan).
 * ============================================================ */
if ($action === 'simpan_hasil') {
    $visitId = req_int('id');
    $hasil    = req_str('hasil_kunjungan');

    if ($visitId <= 0) {
        flash_set('warning', 'Kunjungan tidak valid.');
        redirect($base . '/index.php');
    }
    if (mb_strlen($hasil) > HASIL_KUNJUNGAN_MAX) {
        flash_set('warning', 'Hasil kunjungan terlalu panjang (maksimal '
            . HASIL_KUNJUNGAN_MAX . ' karakter).');
        redirect($base . '/detail.php?id=' . $visitId);
    }

    $pdo = db();

    try {
        $st = $pdo->prepare('SELECT id, kode_kunjungan FROM visits WHERE id = ?');
        $st->execute([$visitId]);
        $visit = $st->fetch();

        if (!$visit) {
            throw new RuntimeException('Kunjungan tidak ditemukan.');
        }

        // Prepared statement PDO - tidak ada data yang digabung ke SQL.
        $st = $pdo->prepare('UPDATE visits SET hasil_kunjungan = ? WHERE id = ?');
        $st->execute([$hasil !== '' ? $hasil : null, $visitId]);
    } catch (Throwable $e) {
        if ($e instanceof RuntimeException) {
            flash_set('danger', $e->getMessage());
        } else {
            error_log('[HASIL_KUNJUNGAN] ' . $e->getMessage());
            flash_set('danger', 'Terjadi kesalahan saat menyimpan hasil kunjungan. Silakan coba lagi.');
        }
        redirect($base . '/detail.php?id=' . $visitId);
    }

    log_aktivitas('Perbarui hasil kunjungan ' . $visit['kode_kunjungan']);
    flash_set('success', $hasil !== ''
        ? 'Hasil kunjungan ' . $visit['kode_kunjungan'] . ' berhasil disimpan.'
        : 'Hasil kunjungan ' . $visit['kode_kunjungan'] . ' dikosongkan.');
    redirect($base . '/detail.php?id=' . $visitId);
}

if ($action !== 'checkin') {
    flash_set('danger', 'Aksi tidak dikenal.');
    redirect($base . '/index.php');
}

$tujuan        = req_str('tujuan');
$pegawai       = req_str('pegawai_ditemui');
$keperluan     = req_str('keperluan');
$hasilKunjungan = req_str('hasil_kunjungan'); // opsional - boleh kosong saat check-in
$mode          = req_str('mode', 'manual');
$token         = req_str('token');

if (mb_strlen($hasilKunjungan) > HASIL_KUNJUNGAN_MAX) {
    flash_set('warning', 'Hasil kunjungan terlalu panjang (maksimal '
        . HASIL_KUNJUNGAN_MAX . ' karakter).');
    redirect($base . '/tambah.php' . ($token !== '' ? '?token=' . urlencode($token) : ''));
}

if ($tujuan === '' || mb_strlen($tujuan) < 3) {
    flash_set('warning', 'Tujuan kunjungan wajib diisi (minimal 3 karakter).');
    redirect($base . '/tambah.php' . ($token !== '' ? '?token=' . urlencode($token) : ''));
}
if ($pegawai === '' || mb_strlen($pegawai) < 3) {
    flash_set('warning', 'Nama pegawai yang ditemui wajib diisi (minimal 3 karakter).');
    redirect($base . '/tambah.php' . ($token !== '' ? '?token=' . urlencode($token) : ''));
}

$pdo = db();

try {
    $pdo->beginTransaction();

    $visitorId  = 0;
    $regId      = null;
    $mitraId    = null;
    $qrId       = null;
    $instansi   = null;
    $namaTamu   = '';

    if ($mode === 'qr') {
        /* ---------- Validasi ulang token QR di server ---------- */
        if ($token === '') {
            throw new RuntimeException('Token QR tidak ditemukan pada permintaan.');
        }

        $st = $pdo->prepare(
            "SELECT q.id AS qr_id, q.status AS qr_status, q.valid_from, q.valid_until,
                    rv.id AS reg_id, rv.status AS reg_status, rv.mitra_id,
                    v.id AS visitor_id, v.nama,
                    pc.nama_perusahaan, pc.status AS mitra_status
             FROM visitor_qr_codes q
             JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
             JOIN visitors v ON v.id = rv.visitor_id
             LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
             WHERE q.token_hash = ?
             FOR UPDATE"
        );
        $st->execute([hash_token($token)]);
        $qr = $st->fetch();

        if (!$qr) {
            throw new RuntimeException('QR tidak dikenali. Silakan gunakan pendaftaran manual.');
        }
        if ($qr['qr_status'] !== 'aktif') {
            throw new RuntimeException('QR berstatus "' . $qr['qr_status'] . '" dan tidak dapat digunakan.');
        }
        if ($qr['valid_from'] !== null && strtotime($qr['valid_from']) > time()) {
            throw new RuntimeException('QR belum berlaku (mulai ' . format_tgl_waktu($qr['valid_from']) . ').');
        }
        if ($qr['valid_until'] !== null && strtotime($qr['valid_until']) < time()) {
            throw new RuntimeException('QR sudah kedaluwarsa (s.d. ' . format_tgl_waktu($qr['valid_until']) . ').');
        }
        if ($qr['reg_status'] !== 'aktif') {
            throw new RuntimeException('Registrasi tamu terkait QR sudah tidak aktif.');
        }
        if ($qr['mitra_id'] && $qr['mitra_status'] === 'nonaktif') {
            throw new RuntimeException('Perusahaan mitra terkait QR berstatus nonaktif.');
        }

        // Identitas dari database, bukan dari form
        $visitorId = (int) $qr['visitor_id'];
        $regId     = (int) $qr['reg_id'];
        $mitraId   = $qr['mitra_id'] !== null ? (int) $qr['mitra_id'] : null;
        $qrId      = (int) $qr['qr_id'];
        $instansi  = $qr['nama_perusahaan'] ?: null;
        $namaTamu  = $qr['nama'];

    } else {
        /* ---------- Mode manual ---------- */
        $visitorId = req_int('visitor_id');

        if ($visitorId > 0) {
            $st = $pdo->prepare('SELECT id, nama FROM visitors WHERE id = ?');
            $st->execute([$visitorId]);
            $v = $st->fetch();
            if (!$v) {
                throw new RuntimeException('Tamu tidak ditemukan. Muat ulang halaman dan pilih kembali.');
            }
            $namaTamu  = $v['nama'];
            $mitraId   = req_int('mitra_id') ?: null;
            $instansi  = null;
        } else {
            // Tamu baru (walk-in): buat identitas lalu catat kunjungannya
            $namaBaru = req_str('nama_baru');
            if (mb_strlen($namaBaru) < 3) {
                throw new RuntimeException('Nama tamu baru wajib diisi (minimal 3 karakter).');
            }
            $jenis = req_str('jenis_identitas', 'KTP');
            if (!in_array($jenis, ['KTP', 'SIM', 'Paspor', 'Kartu Pegawai', 'Lainnya'], true)) {
                $jenis = 'Lainnya';
            }
            $noIdentitas = req_str('no_identitas_baru');
            $telepon     = req_str('telepon_baru');
            $instansi    = req_str('instansi_baru');
            $mitraId     = req_int('mitra_id') ?: null;

            $kodeTamu = gen_kode_unique('visitors', 'kode_tamu', 'TM');
            $st = $pdo->prepare(
                'INSERT INTO visitors (kode_tamu, nama, jenis_identitas, no_identitas, telepon)
                 VALUES (?,?,?,?,?)'
            );
            $st->execute([$kodeTamu, $namaBaru, $jenis, $noIdentitas ?: null, $telepon ?: null]);
            $visitorId = (int) $pdo->lastInsertId();
            $namaTamu  = $namaBaru;
        }
    }

    /* ---------- Simpan kunjungan ---------- */
    $kode = gen_kode_unique('visits', 'kode_kunjungan', 'KT');
    $st = $pdo->prepare(
        "INSERT INTO visits
            (kode_kunjungan, visitor_id, registered_visitor_id, mitra_id, qr_id, instansi,
             tujuan, pegawai_ditemui, keperluan, hasil_kunjungan, jam_masuk, status, petugas_masuk_id)
         VALUES (?,?,?,?,?,?,?,?,?,?,NOW(),'berlangsung',?)"
    );
    $st->execute([
        $kode, $visitorId, $regId, $mitraId, $qrId, $instansi,
        $tujuan, $pegawai, $keperluan ?: null, $hasilKunjungan ?: null, current_user_id(),
    ]);
    $visitId = (int) $pdo->lastInsertId();

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    $msg = ($e instanceof RuntimeException)
        ? $e->getMessage()
        : 'Terjadi kesalahan saat menyimpan kunjungan. Silakan coba lagi.';
    if (!($e instanceof RuntimeException)) {
        error_log('[CHECKIN] ' . $e->getMessage());
    }
    flash_set('danger', $msg);
    redirect($base . '/tambah.php' . ($token !== '' ? '?token=' . urlencode($token) : ''));
}

log_aktivitas('Check-in kunjungan ' . $kode . ' - ' . $namaTamu . ($mode === 'qr' ? ' (via QR)' : ''));

try {
    notify(null, 'Kunjungan baru', $kode . ' - ' . $namaTamu . ' check-in pukul ' . date('H:i'), 'info');
} catch (Throwable $e) {
    // notifikasi bersifat pelengkap
}

flash_set('success', 'Check-in berhasil. Kunjungan ' . $kode . ' tercatat untuk ' . $namaTamu . '.');
redirect($base . '/detail.php?id=' . $visitId);
