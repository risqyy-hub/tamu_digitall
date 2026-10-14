<?php
/**
 * includes/modules/jaminan/index.php
 * Daftar jaminan (aktif / dikembalikan) + filter + aksi kembalikan.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base    = $AREA . '/jaminan';
$status  = get_str('status');
$q       = get_str('q');
$page    = max(1, get_int('page', 1));
$perPage = 15;

$conds  = [];
$params = [];

if (in_array($status, ['aktif', 'dikembalikan'], true)) {
    $conds[]  = 'g.status = ?';
    $params[] = $status;
}
if ($q !== '') {
    $conds[] = '(vi.kode_kunjungan LIKE ? OR v.nama LIKE ? OR g.barang LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare(
    "SELECT COUNT(*) FROM guarantees g
     JOIN visits vi ON vi.id = g.visit_id
     JOIN visitors v ON v.id = vi.visitor_id
     {$where}"
);
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT g.*, vi.kode_kunjungan, vi.status AS kunjungan_status, v.nama AS nama_tamu,
            u1.nama AS diterima_nama, u2.nama AS dikembalikan_nama
     FROM guarantees g
     JOIN visits vi ON vi.id = g.visit_id
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN users u1 ON u1.id = g.diterima_oleh
     LEFT JOIN users u2 ON u2.id = g.dikembalikan_oleh
     {$where}
     ORDER BY (g.status = 'aktif') DESC, g.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

// jumlah jaminan aktif keseluruhan (untuk peringatan check-out)
$st = db()->query("SELECT COUNT(*) FROM guarantees WHERE status = 'aktif'");
$TotalAktif = (int) $st->fetchColumn();

$page_title = 'Jaminan';
$active_menu = 'jaminan';
require __DIR__ . '/../../header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-box-seam me-2"></i>Manajemen Jaminan</h1>
    <small class="text-muted">
      <?= $total ?> data jaminan &middot;
      <span class="text-warning fw-semibold"><?= $TotalAktif ?> jaminan belum dikembalikan</span>
    </small>
  </div>
  <a href="<?= e(url($base . '/tambah.php')) ?>" class="btn btn-navy btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Tambah Jaminan
  </a>
</div>

<?php if ($TotalAktif > 0): ?>
  <div class="alert alert-warning py-2 small">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Kunjungan dengan jaminan aktif <b>tidak dapat check-out</b> sampai jaminan dikembalikan.
    <a href="<?= e(url($base . '/index.php?status=aktif')) ?>">Lihat semua jaminan aktif</a>.
  </div>
<?php endif; ?>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url($base . '/index.php')) ?>">
      <div class="col-md-4">
        <label class="form-label small">Cari</label>
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="No. kunjungan / nama tamu / barang" value="<?= e($q) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small">Status</label>
        <select name="status" class="form-select form-select-sm">
          <option value="">Semua status</option>
          <option value="aktif" <?= $status === 'aktif' ? 'selected' : '' ?>>Aktif (belum kembali)</option>
          <option value="dikembalikan" <?= $status === 'dikembalikan' ? 'selected' : '' ?>>Dikembalikan</option>
        </select>
      </div>
      <div class="col-md-2 d-grid">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i> Filter</button>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Barang</th><th class="text-center">Jml</th>
          <th>No. Kunjungan</th><th>Tamu</th>
          <th>Status</th><th>Diterima</th><th>Dikembalikan</th>
          <th class="text-end no-print">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">
          <div class="empty-state"><i class="bi bi-box-seam"></i>Tidak ada data jaminan dengan filter ini.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $g): ?>
        <tr>
          <td>
            <span class="fw-semibold"><?= e($g['barang']) ?></span>
            <?php if ($g['keterangan']): ?><br><span class="text-muted small"><?= e(mb_substr($g['keterangan'], 0, 60)) ?></span><?php endif; ?>
          </td>
          <td class="text-center"><?= (int) $g['jumlah'] ?></td>
          <td class="small"><?= e($g['kode_kunjungan']) ?></td>
          <td class="small"><?= e($g['nama_tamu']) ?></td>
          <td><?= badge_status($g['status']) ?></td>
          <td class="small"><?= e(format_tgl_waktu($g['diterima_at'])) ?><br><span class="text-muted"><?= e($g['diterima_nama'] ?: '-') ?></span></td>
          <td class="small"><?= e(format_tgl_waktu($g['dikembalikan_at'])) ?><br><span class="text-muted"><?= e($g['dikembalikan_nama'] ?: '-') ?></span></td>
          <td class="text-end no-print">
            <?php if ($g['status'] === 'aktif'): ?>
              <form method="post" action="<?= e(url('actions/jaminan_process.php')) ?>" class="d-inline"
                    data-confirm="Kembalikan jaminan <?= e($g['barang']) ?> kepada tamu <?= e($g['nama_tamu']) ?>?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="kembalikan">
                <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                <button class="btn btn-success btn-sm" type="submit">
                  <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
                </button>
              </form>
            <?php endif; ?>
            <a href="<?= e(url($AREA . '/kunjungan/detail.php?id=' . (int) $g['visit_id'])) ?>"
               class="btn btn-outline-navy btn-sm btn-icon" title="Lihat kunjungan">
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
      <?= pagination($total, $page, $perPage, url($base . '/index.php'), array_filter(['q' => $q, 'status' => $status])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
