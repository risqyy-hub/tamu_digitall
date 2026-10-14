<?php
/**
 * admin/laporan/ekspor_csv.php
 * Unduh laporan kunjungan sebagai CSV (fputcsv + BOM UTF-8 + proteksi formula injection).
 * Mengikuti filter yang sama dengan halaman laporan.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/laporan_helper.php';
require_role('admin');

[$where, $params, $filter] = laporan_build_filter($_GET);

// Batasi agar tetap ringan (per periode laporan wajar)
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
     ORDER BY vi.jam_masuk DESC
     LIMIT 5000"
);
$st->execute($params);
$rows = $st->fetchAll();

$header = [
    'No. Kunjungan', 'Tanggal', 'Kode Tamu', 'Nama Tamu', 'Jenis Identitas', 'No. Identitas',
    'Instansi/Mitra', 'Tujuan', 'Hasil Kunjungan', 'Pegawai Ditemui', 'Keperluan',
    'Jam Masuk', 'Jam Keluar', 'Durasi (menit)', 'Status',
    'Jumlah Jaminan', 'Jaminan Aktif', 'Petugas Check-in', 'Petugas Check-out', 'QR Digunakan',
];

$data = [];
foreach ($rows as $r) {
    $durasi = $r['durasi_menit'] !== null
        ? (int) $r['durasi_menit']
        : ($r['status'] === 'berlangsung' ? durasi_berjalan($r['jam_masuk']) : '');
    $data[] = [
        $r['kode_kunjungan'],
        format_tanggal($r['jam_masuk']),
        $r['kode_tamu'],
        $r['nama_tamu'],
        $r['jenis_identitas'],
        $r['no_identitas'],
        $r['nama_perusahaan'] ?: ($r['instansi'] ?: ''),
        $r['tujuan'],
        $r['hasil_kunjungan'] ?? '',
        $r['pegawai_ditemui'],
        $r['keperluan'],
        format_tgl_waktu($r['jam_masuk']),
        $r['jam_keluar'] ? format_tgl_waktu($r['jam_keluar']) : '',
        $durasi,
        $r['status'],
        (int) $r['jaminan_total'],
        (int) $r['jaminan_aktif'],
        $r['petugas_masuk'],
        $r['petugas_keluar'],
        $r['qr_id'] ? 'Ya' : 'Tidak',
    ];
}

log_aktivitas('Mengunduh CSV laporan kunjungan (' . count($data) . ' baris, periode '
    . $filter['dari'] . ' s.d. ' . $filter['sampai'] . ')');

$filename = 'laporan-kunjungan-' . $filter['dari'] . '_' . $filter['sampai'] . '.csv';
send_csv($filename, $header, $data);
