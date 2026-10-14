<?php
/**
 * actions/registrasi_process.php
 * Kelola registrasi tamu terdaftar (pemilik QR).
 * Akses: admin.
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');
require_post();
verify_csrf();

$action = req_str('action');
$pdo    = db();
$back   = 'admin/tamu/registrasi.php';

/* ================= TAMBAH ================= */
if ($action === 'tambah') {
    $visitorId = req_int('visitor_id');
    $mitraId   = req_int('mitra_id') ?: null;
    $noKartu   = req_str('no_kartu');

    $st = $pdo->prepare('SELECT id, nama FROM visitors WHERE id = ?');
    $st->execute([$visitorId]);
    $tamu = $st->fetch();

    if (!$tamu) {
        flash_set('warning', 'Tamu tidak ditemukan. Pilih kembali daftar tamu.');
        redirect($back);
    }
    if ($mitraId) {
        $st = $pdo->prepare('SELECT id FROM partner_companies WHERE id = ?');
        $st->execute([$mitraId]);
        if (!$st->fetch()) {
            $mitraId = null;
        }
    }
    if ($noKartu !== '') {
        $st = $pdo->prepare('SELECT id FROM registered_visitors WHERE no_kartu = ?');
        $st->execute([$noKartu]);
        if ($st->fetch()) {
            flash_set('warning', 'Nomor kartu "' . $noKartu . '" sudah dipakai registrasi lain.');
            redirect($back);
        }
    }

    $st = $pdo->prepare(
        'INSERT INTO registered_visitors (visitor_id, mitra_id, no_kartu, status) VALUES (?,?,?,\'aktif\')'
    );
    $st->execute([$visitorId, $mitraId, $noKartu ?: null]);
    $id = (int) $pdo->lastInsertId();

    log_aktivitas('Mendaftarkan tamu ' . $tamu['nama'] . ' sebagai tamu terdaftar (#' . $id . ')');
    flash_set('success', 'Tamu "' . $tamu['nama'] . '" terdaftar sebagai tamu terdaftar (dapat memiliki QR).');
    redirect($back);
}

/* ================= UBAH STATUS ================= */
if ($action === 'ubah_status') {
    $id     = req_int('id');
    $status = req_str('status');

    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        flash_set('warning', 'Status tidak valid.');
        redirect($back);
    }

    $st = $pdo->prepare('SELECT * FROM registered_visitors WHERE id = ?');
    $st->execute([$id]);
    $reg = $st->fetch();

    if (!$reg) {
        flash_set('warning', 'Registrasi tidak ditemukan.');
        redirect($back);
    }

    $st = $pdo->prepare('UPDATE registered_visitors SET status = ? WHERE id = ?');
    $st->execute([$status, $id]);

    log_aktivitas('Mengubah status registrasi tamu #' . $id . ' menjadi ' . $status);
    flash_set('success', 'Status registrasi diperbarui menjadi "' . $status . '".');
    redirect($back);
}

/* ================= HAPUS ================= */
if ($action === 'hapus') {
    $id = req_int('id');

    $st = $pdo->prepare(
        'SELECT rv.*, v.nama FROM registered_visitors rv JOIN visitors v ON v.id = rv.visitor_id WHERE rv.id = ?'
    );
    $st->execute([$id]);
    $reg = $st->fetch();

    if (!$reg) {
        flash_set('warning', 'Registrasi tidak ditemukan.');
        redirect($back);
    }

    // QR terkait ikut terhapus (FK cascade); riwayat kunjungan tetap aman (FK set null)
    $st = $pdo->prepare('DELETE FROM registered_visitors WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus registrasi tamu ' . $reg['nama'] . ' (#' . $id . ')');
    flash_set('success', 'Registrasi "' . $reg['nama'] . '" dihapus beserta QR yang terkait. Riwayat kunjungan tetap tersimpan.');
    redirect($back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
