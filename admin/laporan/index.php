<?php
/**
 * admin/laporan/index.php
 * Laporan kunjungan: filter, tabel, cetak (window.print) & ekspor CSV.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/laporan_helper.php';
require_role('admin');

[$where, $params, $filter] = laporan_build_filter($_GET);

$st = db()->prepare(
    "SELECT COUNT(*) FROM visits vi JOIN visitors v ON v.id = vi.visitor_id {$where}"
);
$st->execute($params);
$total = (int) $st->fetchColumn();

$stSelesai = db()->prepare(
    "SELECT COUNT(*) FROM visits vi JOIN visitors v ON v.id = vi.visitor_id {$where} AND vi.status = 'selesai'"
);
$stSelesai->execute($params);
$totalSelesai = (int) $stSelesai->fetchColumn();

$stDurasi = db()->prepare(
    "SELECT COALESCE(SUM(vi.durasi_menit), 0) FROM visits vi JOIN visitors v ON v.id = vi.visitor_id {$where} AND vi.status = 'selesai'"
);
$stDurasi->execute($params);
$totalDurasi = (int) $stDurasi->fetchColumn();

$page = max(1, get_int('page', 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$stList = db()->prepare(
    "SELECT vi.*, v.nama AS nama_tamu, v.kode_tamu, pc.nama_perusahaan,
            u1.nama AS petugas_masuk, u2.nama AS petugas_keluar,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id) AS jaminan_total,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif
     FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     LEFT JOIN users u1 ON u1.id = vi.petugas_masuk_id
     LEFT JOIN users u2 ON u2.id = vi.petugas_keluar_id
     {$where}
     ORDER BY vi.jam_masuk DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$stList->execute($params);
$rows = $stList->fetchAll();

$mitraList  = db()->query('SELECT id, nama_perusahaan FROM partner_companies ORDER BY nama_perusahaan')->fetchAll();
$petugasList = db()->query("SELECT id, nama FROM users WHERE status = 'aktif' ORDER BY nama")->fetchAll();

$mitraName  = array_combine(array_column($mitraList, 'id'), array_column($mitraList, 'nama_perusahaan'));
$petugasName = array_combine(array_column($petugasList, 'id'), array_column($petugasList, 'nama'));

$qsFilter = http_build_query(array_filter($filter, function ($v) {
    return $v !== '' && $v !== 0;
}));

$page_title = 'Laporan Kunjungan';
$active_menu = 'laporan';
require __DIR__ . '/../../includes/header.php';
?>

<!-- ============ Bagian TIDAK dicetak ============ -->
<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3 no-print">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan Kunjungan</h1>
    <small class="text-muted">Filter, cetak (Save as PDF), atau unduh CSV</small>
  </div>
  <div class="d-flex gap-2">
    <button type="button" class="btn btn-navy btn-sm" onclick="window.print()">
      <i class="bi bi-printer me-1"></i>Cetak / Simpan PDF
    </button>
    <a href="<?= e(url('admin/laporan/ekspor_csv.php' . ($qsFilter !== '' ? '?' . $qsFilter : ''))) ?>"
       class="btn btn-outline-navy btn-sm">
      <i class="bi bi-file-earmark-spreadsheet me-1"></i>Ekspor CSV
    </a>
  </div>
</div>

<div class="panel p-3 mb-3 no-print">
  <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/laporan/index.php')) ?>">
    <div class="col-md-2">
      <label class="form-label small">Dari tanggal</label>
      <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($filter['dari']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Sampai tanggal</label>
      <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($filter['sampai']) ?>">
    </div>
    <div class="col-md-2">
      <label class="form-label small">Status</label>
      <select name="status" class="form-select form-select-sm">
        <option value="">Semua</option>
        <option value="berlangsung" <?= $filter['status'] === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
        <option value="selesai" <?= $filter['status'] === 'selesai' ? 'selected' : '' ?>>Selesai</option>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Mitra</label>
      <select name="mitra_id" class="form-select form-select-sm">
        <option value="">Semua mitra</option>
        <?php foreach ($mitraList as $m): ?>
          <option value="<?= (int) $m['id'] ?>" <?= $filter['mitra_id'] === (int) $m['id'] ? 'selected' : '' ?>>
            <?= e($m['nama_perusahaan']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2">
      <label class="form-label small">Petugas</label>
      <select name="petugas_id" class="form-select form-select-sm">
        <option value="">Semua petugas</option>
        <?php foreach ($petugasList as $p): ?>
          <option value="<?= (int) $p['id'] ?>" <?= $filter['petugas_id'] === (int) $p['id'] ? 'selected' : '' ?>>
            <?= e($p['nama']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="col-md-2 d-grid">
      <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Terapkan</button>
    </div>
  </form>
</div>

<!-- ============ Header laporan (ikut dicetak) ============ -->
<div class="panel p-3 p-md-4">
  <div class="mb-3">
    <h2 class="h6 fw-bold text-navy mb-0"><?= e(setting('nama_instansi', APP_NAME)) ?></h2>
    <p class="mb-0">
      <strong>Laporan Kunjungan</strong><br>
      <span class="small">
        Periode: <?= e(format_tanggal($filter['dari'])) ?> &ndash; <?= e(format_tanggal($filter['sampai'])) ?>
        <?= $filter['status'] ? ' &middot; Status: ' . e($filter['status']) : '' ?>
        <?= $filter['mitra_id'] && isset($mitraName[$filter['mitra_id']]) ? ' &middot; Mitra: ' . e($mitraName[$filter['mitra_id']]) : '' ?>
        <?= $filter['petugas_id'] && isset($petugasName[$filter['petugas_id']]) ? ' &middot; Petugas: ' . e($petugasName[$filter['petugas_id']]) : '' ?>
        <br>
        Dicetak: <?= e(date('d/m/Y H:i:s')) ?> WIB oleh <?= e(current_user()['nama']) ?>
      </span>
    </p>
    <div class="mt-2 small">
      <span class="badge text-bg-light border">Total: <?= $total ?> kunjungan</span>
      <span class="badge text-bg-light border">Selesai: <?= $totalSelesai ?></span>
      <span class="badge text-bg-light border">Total durasi: <?= e(format_durasi($totalDurasi)) ?></span>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-bordered table-sm align-middle mb-0" id="tabelLaporan">
      <thead>
        <tr>
          <th class="text-center">No.</th>
          <th>No. Kunjungan</th>
          <th>Tanggal</th>
          <th>Tamu</th>
          <th>Instansi</th>
          <th>Tujuan</th>
          <th>Hasil Kunjungan</th>
          <th>Pegawai</th>
          <th>Masuk</th>
          <th>Keluar</th>
          <th class="text-center">Durasi</th>
          <th class="text-center">Jaminan</th>
          <th>Status</th>
          <th>Petugas</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="14">
          <div class="empty-state"><i class="bi bi-inbox"></i>Tidak ada data kunjungan pada periode ini.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $i => $r): ?>
        <tr>
          <td class="text-center"><?= $offset + $i + 1 ?></td>
          <td class="small"><?= e($r['kode_kunjungan']) ?></td>
          <td class="small"><?= e(format_tanggal($r['jam_masuk'])) ?></td>
          <td class="small"><?= e($r['nama_tamu']) ?></td>
          <td class="small"><?= e($r['nama_perusahaan'] ?: ($r['instansi'] ?: '-')) ?></td>
          <td class="small"><?= e($r['tujuan']) ?></td>
          <td class="small hasil-cell"><?= cell_hasil_kunjungan($r['hasil_kunjungan'] ?? null) ?></td>
          <td class="small"><?= e($r['pegawai_ditemui']) ?></td>
          <td class="small"><?= e(format_waktu($r['jam_masuk'])) ?></td>
          <td class="small"><?= e(format_waktu($r['jam_keluar'])) ?></td>
          <td class="text-center small">
            <?= e(format_durasi($r['durasi_menit'] !== null ? (int) $r['durasi_menit'] : ($r['status'] === 'berlangsung' ? durasi_berjalan($r['jam_masuk']) : null))) ?>
          </td>
          <td class="text-center small">
            <?= $r['jaminan_total'] == 0 ? '-' : ((int) $r['jaminan_aktif'] > 0 ? (int) $r['jaminan_aktif'] . ' aktif' : 'lunas') ?>
          </td>
          <td class="small"><?= e(ucfirst($r['status'])) ?></td>
          <td class="small"><?= e($r['petugas_masuk'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end no-print">
      <?= pagination($total, $page, $perPage, url('admin/laporan/index.php'), array_filter($filter, function ($v) {
          return $v !== '' && $v !== 0;
      })) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
