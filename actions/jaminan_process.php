<?php
/**
 * actions/jaminan_process.php
 * Pencatatan jaminan: tambah (penerimaan), kembalikan, hapus (riwayat).
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('petugas');
require_post();
verify_csrf();

$area = is_admin() ? 'admin' : 'petugas';
$back = $area . '/jaminan/index.php';

// Kembali ke halaman asal (jika masih dalam aplikasi)
$ref   = $_SERVER['HTTP_REFERER'] ?? '';
$safeBack = (strpos($ref, BASE_URL) === 0) ? $ref : null;

$action = req_str('action');
$pdo    = db();

/* ================= TAMBAH JAMINAN ================= */
if ($action === 'tambah') {
    $visitId = req_int('visit_id');
    $barang  = req_str('barang');
    $jumlah  = req_int('jumlah', 1);
    $ket     = req_str('keterangan');

    if ($visitId <= 0 || $barang === '' || mb_strlen($barang) < 2) {
        flash_set('warning', 'Kunjungan dan nama barang jaminan wajib diisi.');
        redirect($area . '/jaminan/tambah.php' . ($visitId > 0 ? '?visit_id=' . $visitId : ''));
    }
    if ($jumlah < 1 || $jumlah > 999) {
        $jumlah = 1;
    }

    $st = $pdo->prepare('SELECT id, kode_kunjungan, status FROM visits WHERE id = ?');
    $st->execute([$visitId]);
    $visit = $st->fetch();

    if (!$visit) {
        flash_set('danger', 'Kunjungan tidak ditemukan.');
        redirect($back);
    }
    if ($visit['status'] !== 'berlangsung') {
        flash_set('warning', 'Jaminan hanya dapat ditambahkan pada kunjungan yang masih berlangsung.');
        redirect($area . '/kunjungan/detail.php?id=' . $visitId);
    }

    // Upload bukti (opsional) - divalidasi ketat
    $buktiError = null;
    $bukti = upload_bukti($_FILES['bukti'] ?? [], $buktiError);
    if ($buktiError) {
        flash_set('warning', 'Bukti tidak tersimpan: ' . $buktiError . ' Jaminan tetap dicatat tanpa bukti.');
    }

    $st = $pdo->prepare(
        "INSERT INTO guarantees (visit_id, barang, jumlah, keterangan, bukti_file, status, diterima_oleh, diterima_at)
         VALUES (?,?,?,?,?,'aktif',?,NOW())"
    );
    $st->execute([$visitId, $barang, $jumlah, $ket ?: null, $bukti, current_user_id()]);
    $gid = (int) $pdo->lastInsertId();

    log_aktivitas('Menerima jaminan #' . $gid . ' (' . $barang . ') untuk kunjungan ' . $visit['kode_kunjungan']);

    flash_set('success', 'Jaminan "' . $barang . '" tercatat. Check-out baru dapat dilakukan setelah jaminan dikembalikan.');
    redirect($area . '/kunjungan/detail.php?id=' . $visitId);
}

/* ================= KEMBALIKAN JAMINAN ================= */
if ($action === 'kembalikan') {
    $id = req_int('id');
    if ($id <= 0) {
        flash_set('warning', 'Jaminan tidak valid.');
        redirect($safeBack ?: $back);
    }

    try {
        $pdo->beginTransaction();

        $st = $pdo->prepare('SELECT * FROM guarantees WHERE id = ? FOR UPDATE');
        $st->execute([$id]);
        $g = $st->fetch();

        if (!$g) {
            throw new RuntimeException('Jaminan tidak ditemukan.');
        }
        if ($g['status'] !== 'aktif') {
            throw new RuntimeException('Jaminan ini sudah pernah dikembalikan.');
        }

        $st = $pdo->prepare(
            "UPDATE guarantees
             SET status = 'dikembalikan', dikembalikan_at = NOW(), dikembalikan_oleh = ?
             WHERE id = ? AND status = 'aktif'"
        );
        $st->execute([current_user_id(), $id]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $msg = ($e instanceof RuntimeException)
            ? $e->getMessage()
            : 'Gagal mengembalikan jaminan. Silakan coba lagi.';
        if (!($e instanceof RuntimeException)) {
            error_log('[JAMINAN] ' . $e->getMessage());
        }
        flash_set('danger', $msg);
        redirect($safeBack ?: $back);
    }

    log_aktivitas('Mengembalikan jaminan #' . $id . ' (' . $g['barang'] . ')');

    // Sisa jaminan aktif kunjungan terkait
    $st = $pdo->prepare("SELECT COUNT(*) FROM guarantees WHERE visit_id = ? AND status = 'aktif'");
    $st->execute([$g['visit_id']]);
    $sisa = (int) $st->fetchColumn();

    if ($sisa === 0) {
        flash_set('success', 'Jaminan "' . $g['barang'] . '" telah dikembalikan. Seluruh jaminan lunas - kunjungan siap di-check-out.');
    } else {
        flash_set('success', 'Jaminan "' . $g['barang'] . '" telah dikembalikan. Masih ada ' . $sisa . ' jaminan aktif lain.');
    }
    redirect($safeBack ?: $back);
}

/* ================= HAPUS JAMINAN (hanya riwayat) ================= */
if ($action === 'hapus') {
    if (!is_admin()) {
        flash_set('danger', 'Hanya admin yang dapat menghapus data jaminan.');
        redirect($back);
    }
    $id = req_int('id');

    $st = $pdo->prepare('SELECT * FROM guarantees WHERE id = ?');
    $st->execute([$id]);
    $g = $st->fetch();

    if (!$g) {
        flash_set('warning', 'Jaminan tidak ditemukan.');
        redirect($back);
    }
    if ($g['status'] !== 'dikembalikan') {
        flash_set('warning', 'Hanya jaminan berstatus "dikembalikan" yang dapat dihapus.');
        redirect($back);
    }

    $st = $pdo->prepare('DELETE FROM guarantees WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus jaminan #' . $id . ' (' . $g['barang'] . ')');
    flash_set('success', 'Data jaminan dihapus.');
    redirect($safeBack ?: $back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
