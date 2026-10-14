<?php
/**
 * includes/laporan_helper.php
 * Pembentuk query laporan bersama untuk halaman laporan & ekspor CSV
 * sehingga hasil filter selalu identik.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

/**
 * @return array [0=>whereSql, 1=>params[], 2=>filter aktif (untuk URL/csv)]
 */
function laporan_build_filter(array $src): array
{
    $dari   = isset($src['dari'])   ? trim((string) $src['dari'])   : '';
    $sampai = isset($src['sampai']) ? trim((string) $src['sampai']) : '';
    $status = isset($src['status']) ? trim((string) $src['status']) : '';
    $mitra  = isset($src['mitra_id']) ? (int) $src['mitra_id'] : 0;
    $petugas = isset($src['petugas_id']) ? (int) $src['petugas_id'] : 0;

    // Default: bulan berjalan
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
        $dari = date('Y-m-01');
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
        $sampai = date('Y-m-d');
    }
    if (!in_array($status, ['berlangsung', 'selesai'], true)) {
        $status = '';
    }

    $conds  = [];
    $params = [];

    $conds[]  = 'vi.jam_masuk >= ?';
    $params[] = $dari . ' 00:00:00';
    $conds[]  = 'vi.jam_masuk <= ?';
    $params[] = $sampai . ' 23:59:59';

    if ($status !== '') {
        $conds[]  = 'vi.status = ?';
        $params[] = $status;
    }
    if ($mitra > 0) {
        $conds[]  = 'vi.mitra_id = ?';
        $params[] = $mitra;
    }
    if ($petugas > 0) {
        $conds[]  = 'vi.petugas_masuk_id = ?';
        $params[] = $petugas;
    }

    return [
        'WHERE ' . implode(' AND ', $conds),
        $params,
        ['dari' => $dari, 'sampai' => $sampai, 'status' => $status, 'mitra_id' => $mitra, 'petugas_id' => $petugas],
    ];
}

/** Query daftar kunjungan untuk laporan (tanpa LIMIT). */
function laporan_query_visits(string $where, array $params): array
{
    $st = db()->prepare(
        "SELECT vi.*, v.nama AS nama_tamu, v.kode_tamu, v.jenis_identitas, v.no_identitas,
                pc.nama_perusahaan, u1.nama AS petugas_masuk, u2.nama AS petugas_keluar,
                (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id) AS jaminan_total,
                (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif
         FROM visits vi
         JOIN visitors v ON v.id = vi.visitor_id
         LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
         LEFT JOIN users u1 ON u1.id = vi.petugas_masuk_id
         LEFT JOIN users u2 ON u2.id = vi.petugas_keluar_id
         {$where}
         ORDER BY vi.jam_masuk DESC"
    );
    $st->execute($params);
    return $st->fetchAll();
}
