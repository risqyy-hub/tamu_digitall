<?php
/**
 * actions/checkout_process.php
 * Check-out kunjungan dengan validasi SERVER-SIDE:
 *  1. pastikan login & berhak,
 *  2. kunjungan ada & masih 'berlangsung' (FOR UPDATE - cegah check-out ganda),
 *  3. tolak bila masih ada jaminan berstatus 'aktif',
 *  4. catat jam keluar, hitung durasi, ubah status menjadi 'selesai',
 *  5. catat ke audit log.
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('petugas');
require_post();
verify_csrf();

$area = is_admin() ? 'admin' : 'petugas';
$back = $area . '/kunjungan/aktif.php';

$id = req_int('id');
if ($id <= 0) {
    flash_set('warning', 'Kunjungan tidak valid.');
    redirect($back);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // 1) Kunci baris kunjungan
    $st = $pdo->prepare('SELECT * FROM visits WHERE id = ? FOR UPDATE');
    $st->execute([$id]);
    $visit = $st->fetch();

    if (!$visit) {
        throw new RuntimeException('Kunjungan tidak ditemukan.');
    }

    // 2) Status harus masih berlangsung (menolak check-out ganda)
    if ($visit['status'] !== 'berlangsung') {
        throw new RuntimeException(
            'Kunjungan ' . $visit['kode_kunjungan'] . ' sudah selesai pada '
            . format_tgl_waktu($visit['jam_keluar']) . '. Check-out ganda tidak diizinkan.'
        );
    }

    // 3) Validasi jaminan: SEMUA harus sudah dikembalikan
    $st = $pdo->prepare("SELECT id, barang FROM guarantees WHERE visit_id = ? AND status = 'aktif'");
    $st->execute([$id]);
    $aktif = $st->fetchAll();

    if (count($aktif) > 0) {
        $daftar = implode(', ', array_map(function ($g) {
            return $g['barang'] . ' (' . $g['id'] . ')';
        }, $aktif));
        $pdo->rollBack();

        // Notifikasi & audit percobaan check-out yang ditolak
        try {
            log_aktivitas('Check-out DITOLAK (jaminan aktif): ' . $visit['kode_kunjungan'] . ' - ' . $daftar);
            notify(null, 'Check-out ditolak', $visit['kode_kunjungan'] . ' masih memiliki jaminan aktif: ' . $daftar, 'warning');
        } catch (Throwable $e) {
        }

        flash_set('danger', 'Check-out ditolak: masih ada ' . count($aktif)
            . ' jaminan yang belum dikembalikan (' . $daftar . ').');
        redirect($area . '/kunjungan/detail.php?id=' . $id);
    }

    // 4) Catat check-out
    $now      = date('Y-m-d H:i:s');
    $durasi   = max(0, (int) floor((strtotime($now) - strtotime($visit['jam_masuk'])) / 60));

    // Hasil kunjungan: hanya diisi bila BELUM PERNAH tersimpan.
    // Hasil yang sudah tersimpan tidak boleh diubah/ditimpa oleh check-out.
    $hasil = req_str('hasil_kunjungan');
    if (mb_strlen($hasil) > HASIL_KUNJUNGAN_MAX) {
        throw new RuntimeException('Hasil kunjungan terlalu panjang (maksimal '
            . HASIL_KUNJUNGAN_MAX . ' karakter).');
    }

    $sql    = "UPDATE visits
               SET jam_keluar = ?, durasi_menit = ?, status = 'selesai', petugas_keluar_id = ?";
    $params = [$now, $durasi, current_user_id()];

    $hasilTersimpan = hasil_kunjungan_ada($visit['hasil_kunjungan'] ?? null);
    if ($hasil !== '' && !$hasilTersimpan) {
        // Baru pertama kali dicatat -> aman disimpan bersama check-out.
        $sql         .= ', hasil_kunjungan = ?';
        $params[]     = $hasil;
    }
    // Bila sudah ada catatan final: kolom hasil_kunjungan TIDAK disentuh sama sekali.

    $sql         .= ' WHERE id = ? AND status = \'berlangsung\'';
    $params[]     = $id;

    $st = $pdo->prepare($sql);
    $st->execute($params);

    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    if ($e instanceof RuntimeException) {
        flash_set('danger', $e->getMessage());
    } else {
        error_log('[CHECKOUT] ' . $e->getMessage());
        flash_set('danger', 'Terjadi kesalahan saat check-out. Silakan coba lagi.');
    }
    redirect($area . '/kunjungan/detail.php?id=' . $id);
}

log_aktivitas('Check-out kunjungan ' . $visit['kode_kunjungan'] . ' (durasi ' . format_durasi($durasi) . ')');

flash_set('success', 'Check-out berhasil. Kunjungan ' . $visit['kode_kunjungan']
    . ' selesai dengan durasi ' . format_durasi($durasi) . '.');
redirect($area . '/kunjungan/detail.php?id=' . $id);
