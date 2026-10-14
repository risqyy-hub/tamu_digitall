<?php
/**
 * includes/modules/kunjungan/index.php
 * Daftar semua kunjungan + filter status/tanggal/pencarian + pagination.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base    = $AREA . '/kunjungan';
$q       = get_str('q');
$status  = get_str('status');
$dari    = get_str('dari');
$sampai  = get_str('sampai');
$mitraId = get_int('mitra_id');
$page    = max(1, get_int('page', 1));
$perPage = 15;

$conds   = [];
$params  = [];

if ($q !== '') {
    $conds[] = '(vi.kode_kunjungan LIKE ? OR v.nama LIKE ? OR vi.tujuan LIKE ? OR vi.pegawai_ditemui LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
if (in_array($status, ['berlangsung', 'selesai'], true)) {
    $conds[]  = 'vi.status = ?';
    $params[] = $status;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
    $conds[]  = 'vi.jam_masuk >= ?';
    $params[] = $dari . ' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
    $conds[]  = 'vi.jam_masuk <= ?';
    $params[] = $sampai . ' 23:59:59';
}
if ($mitraId > 0 && is_admin()) {
    $conds[]  = 'vi.mitra_id = ?';
    $params[] = $mitraId;
}

$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare(
    "SELECT COUNT(*) FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     {$where}"
);
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT vi.*, v.nama AS nama_tamu, v.kode_tamu, v.jenis_identitas, v.no_identitas,
            pc.nama_perusahaan, u.nama AS petugas_masuk,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif
     FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     LEFT JOIN users u ON u.id = vi.petugas_masuk_id
     {$where}
     ORDER BY vi.jam_masuk DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

// daftar mitra untuk filter (admin)
$mitraList = [];
if (is_admin()) {
    $mitraList = db()->query('SELECT id, nama_perusahaan FROM partner_companies ORDER BY nama_perusahaan')->fetchAll();
}

$page_title = 'Semua Kunjungan';
$active_menu = 'kunjungan';
require __DIR__ . '/../../header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-door-open me-2"></i>Semua Kunjungan</h1>
    <small class="text-muted"><?= $total ?> kunjungan ditemukan</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url($AREA . '/kunjungan/aktif.php')) ?>" class="btn btn-outline-navy btn-sm">
      <i class="bi bi-hourglass-split me-1"></i>Kunjungan Aktif
    </a>
    <a href="<?= e(url($base . '/tambah.php')) ?>" class="btn btn-navy btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Tambah Kunjungan
    </a>
  </div>
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url($base . '/index.php')) ?>">
      <div class="col-md-3">
        <label class="form-label small">Pencarian</label>
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="No. kunjungan / nama tamu / tujuan" value="<?= e($q) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Semua status</option>
          <option value="berlangsung" <?= $status === 'berlangsung' ? 'selected' : '' ?>>Berlangsung</option>
          <option value="selesai" <?= $status === 'selesai' ? 'selected' : '' ?>>Selesai</option>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label small">Dari tanggal</label>
        <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label small">Sampai tanggal</label>
        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>">
      </div>
      <?php if (is_admin() && $mitraList): ?>
        <div class="col-md-2">
          <label class="form-label small">Mitra</label>
          <select name="mitra_id" class="form-select form-select-sm">
            <option value="">Semua mitra</option>
            <?php foreach ($mitraList as $m): ?>
              <option value="<?= (int) $m['id'] ?>" <?= $mitraId === (int) $m['id'] ? 'selected' : '' ?>>
                <?= e($m['nama_perusahaan']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
      <div class="col-md-1 d-grid">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i></button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>No. Kunjungan</th>
          <th>Tamu</th>
          <th>Instansi</th>
          <th>Tujuan</th>
          <th>Hasil Kunjungan</th>
          <th>Masuk</th>
          <th>Keluar</th>
          <th class="text-center">Durasi</th>
          <th class="text-center">Status</th>
          <th class="text-end no-print">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="10">
          <div class="empty-state">
            <i class="bi bi-inbox"></i>Belum ada data kunjungan yang cocok dengan filter.
          </div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <?php $durasi = $r['durasi_menit'] !== null ? (int) $r['durasi_menit'] : durasi_berjalan($r['jam_masuk']); ?>
        <tr>
          <td class="small"><?= e($r['kode_kunjungan']) ?></td>
          <td>
            <span class="fw-semibold"><?= e($r['nama_tamu']) ?></span><br>
            <span class="text-muted small"><?= e($r['kode_tamu']) ?><?= $r['qr_id'] ? ' &middot; <i class="bi bi-qr-code"></i> QR' : '' ?></span>
          </td>
          <td class="small"><?= e($r['nama_perusahaan'] ?: $r['instansi'] ?: '-') ?></td>
          <td class="small"><?= e($r['tujuan']) ?></td>
          <td class="small hasil-cell">
            <?= cell_hasil_kunjungan($r['hasil_kunjungan'] ?? null, url($base . '/detail.php?id=' . (int) $r['id'])) ?>
          </td>
          <td class="small"><?= e(format_tgl_waktu($r['jam_masuk'])) ?></td>
          <td class="small"><?= e(format_tgl_waktu($r['jam_keluar'])) ?></td>
          <td class="text-center small"
              <?= $r['status'] === 'berlangsung' ? 'data-durasi-start="' . e(date('c', strtotime($r['jam_masuk']))) . '"' : '' ?>>
            <?= e(format_durasi($durasi)) ?>
          </td>
          <td class="text-center">
            <?= badge_status($r['status']) ?>
            <?php if ($r['jaminan_aktif'] > 0): ?>
              <br><span class="badge text-bg-warning mt-1" title="Jaminan belum dikembalikan">
                <i class="bi bi-box-seam"></i> <?= (int) $r['jaminan_aktif'] ?>
              </span>
            <?php endif; ?>
          </td>
          <td class="text-end no-print">
            <a href="<?= e(url($base . '/detail.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-navy btn-sm btn-icon" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end">
      <?= pagination($total, $page, $perPage, url($base . '/index.php'), array_filter([
          'q' => $q, 'status' => $status, 'dari' => $dari, 'sampai' => $sampai, 'mitra_id' => $mitraId ?: '',
      ])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
