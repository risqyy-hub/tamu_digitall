<?php
/**
 * actions/qr_process.php
 * Penerbitan, pencabutan, dan penghapusan QR Code tamu terdaftar.
 * Token acak 256-bit; yang disimpan di database adalah hash SHA-256.
 * Token mentah hanya ditampilkan SEKALI saat penerbitan.
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');
require_post();
verify_csrf();

$action = req_str('action');
$pdo    = db();
$back   = 'admin/qr/index.php';

/* ================= TERBITKAN ================= */
if ($action === 'terbitkan') {
    $regId = req_int('registered_visitor_id');
    $validFrom  = req_str('valid_from');
    $validUntil = req_str('valid_until');

    // Validasi registrasi tamu
    $st = $pdo->prepare(
        "SELECT rv.id, rv.status, v.nama, pc.nama_perusahaan, pc.status AS mitra_status
         FROM registered_visitors rv
         JOIN visitors v ON v.id = rv.visitor_id
         LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
         WHERE rv.id = ?"
    );
    $st->execute([$regId]);
    $reg = $st->fetch();

    if (!$reg) {
        flash_set('warning', 'Tamu terdaftar tidak ditemukan. Daftarkan tamu terlebih dahulu.');
        redirect('admin/qr/tambah.php');
    }
    if ($reg['status'] !== 'aktif') {
        flash_set('warning', 'Registrasi tamu "' . $reg['nama'] . '" berstatus nonaktif.');
        redirect('admin/qr/tambah.php');
    }
    if ($reg['mitra_status'] === 'nonaktif') {
        flash_set('warning', 'Perusahaan mitra terkait berstatus nonaktif. Aktifkan mitra terlebih dahulu.');
        redirect('admin/qr/tambah.php');
    }

    // Masa berlaku
    if ($validFrom === '') {
        $validFrom = date('Y-m-d H:i:s');
    } else {
        $validFrom = str_replace('T', ' ', $validFrom) . (strlen($validFrom) === 16 ? ':00' : '');
    }
    if ($validUntil === '') {
        // Default dari pengaturan (qr_default_days)
        $hari = (int) setting('qr_default_days', '30');
        if ($hari <= 0) {
            $hari = 30;
        }
        $validUntil = date('Y-m-d H:i:s', time() + $hari * 86400);
    } else {
        $validUntil = str_replace('T', ' ', $validUntil) . (strlen($validUntil) === 16 ? ':00' : '');
    }
    if (strtotime($validUntil) <= strtotime($validFrom)) {
        flash_set('warning', 'Masa berlaku QR harus lebih besar dari waktu penerbitan.');
        redirect('admin/qr/tambah.php');
    }

    $token = random_token();
    $st = $pdo->prepare(
        'INSERT INTO visitor_qr_codes (registered_visitor_id, token_hash, status, valid_from, valid_until, issued_by)
         VALUES (?,?,\'aktif\',?,?,?)'
    );
    $st->execute([$regId, hash_token($token), $validFrom, $validUntil, current_user_id()]);
    $qrId = (int) $pdo->lastInsertId();

    // Token mentah disimpan sekali di sesi untuk ditampilkan (sekali pakai)
    flash_token_set('qr_token_' . $qrId, $token);

    log_aktivitas('Menerbitkan QR #' . $qrId . ' untuk tamu ' . $reg['nama']);
    try {
        notify(null, 'QR Code diterbitkan', 'QR untuk ' . $reg['nama'] . ' berlaku s.d. ' . format_tgl_waktu($validUntil), 'info');
    } catch (Throwable $e) {
    }

    flash_set('success', 'QR Code berhasil diterbitkan untuk "' . $reg['nama'] . '". Simpan/cetak sekarang - token hanya ditampilkan satu kali.');
    redirect('admin/qr/tampil.php?id=' . $qrId);
}

/* ================= CABUT ================= */
if ($action === 'cabut') {
    $id = req_int('id');

    $st = $pdo->prepare('SELECT * FROM visitor_qr_codes WHERE id = ?');
    $st->execute([$id]);
    $qr = $st->fetch();

    if (!$qr) {
        flash_set('warning', 'QR tidak ditemukan.');
        redirect($back);
    }
    if ($qr['status'] !== 'aktif') {
        flash_set('warning', 'QR hanya dapat dicabut saat berstatus aktif.');
        redirect($back);
    }

    $st = $pdo->prepare(
        "UPDATE visitor_qr_codes SET status = 'dicabut', revoked_at = NOW(), revoked_by = ? WHERE id = ?"
    );
    $st->execute([current_user_id(), $id]);

    log_aktivitas('Mencabut QR #' . $id);
    flash_set('success', 'QR Code berhasil dicabut dan tidak dapat digunakan lagi.');
    redirect($back);
}

/* ================= HAPUS ================= */
if ($action === 'hapus') {
    $id = req_int('id');

    $st = $pdo->prepare('SELECT * FROM visitor_qr_codes WHERE id = ?');
    $st->execute([$id]);
    $qr = $st->fetch();

    if (!$qr) {
        flash_set('warning', 'QR tidak ditemukan.');
        redirect($back);
    }

    $st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE qr_id = ?');
    $st->execute([$id]);
    $dipakai = (int) $st->fetchColumn();

    if ($dipakai > 0) {
        flash_set('warning', 'QR pernah digunakan pada ' . $dipakai . ' kunjungan. Cukup cabut QR agar riwayat tetap utuh.');
        redirect($back);
    }

    $st = $pdo->prepare('DELETE FROM visitor_qr_codes WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus QR #' . $id);
    flash_set('success', 'QR Code dihapus.');
    redirect($back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
