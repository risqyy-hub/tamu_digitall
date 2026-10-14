<?php
/**
 * includes/modules/tamu/index.php
 * Daftar tamu + pencarian + pagination.
 * Dipakai: admin/tamu/index.php dan petugas/tamu/index.php ($AREA sudah di-set wrapper).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base   = $AREA . '/tamu';
$q      = get_str('q');
$page   = max(1, get_int('page', 1));
$perPage = 15;

$where  = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE (v.nama LIKE ? OR v.kode_tamu LIKE ? OR v.no_identitas LIKE ? OR v.telepon LIKE ?)';
    $like  = '%' . $q . '%';
    $params = [$like, $like, $like, $like];
}

$st = db()->prepare('SELECT COUNT(*) FROM visitors v ' . $where);
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT v.*,
            (SELECT COUNT(*) FROM visits vi WHERE vi.visitor_id = v.id) AS total_kunjungan,
            (SELECT COUNT(*) FROM registered_visitors rv WHERE rv.visitor_id = v.id AND rv.status = 'aktif') AS terdaftar
     FROM visitors v
     {$where}
     ORDER BY v.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$page_title = 'Data Tamu';
$active_menu = 'tamu';
require __DIR__ . '/../../header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-people me-2"></i>Data Tamu</h1>
    <small class="text-muted"><?= $total ?> data tamu tercatat</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url($base . '/tambah.php')) ?>" class="btn btn-navy btn-sm">
      <i class="bi bi-person-plus me-1"></i>Tambah Tamu
    </a>
  </div>
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 align-items-end filter-form" method="get" action="<?= e(url($base . '/index.php')) ?>">
      <div class="col-sm-8 col-md-5">
        <label class="form-label small">Cari</label>
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Nama, kode tamu, no. identitas, telepon..." value="<?= e($q) ?>">
      </div>
      <div class="col-sm-4 col-md-2">
        <button class="btn btn-navy btn-sm w-100" type="submit">
          <i class="bi bi-search me-1"></i>Cari
        </button>
      </div>
      <?php if ($q !== ''): ?>
        <div class="col-md-2">
          <a href="<?= e(url($base . '/index.php')) ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-x-circle me-1"></i>Reset
          </a>
        </div>
      <?php endif; ?>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Kode</th>
          <th>Nama</th>
          <th>Identitas</th>
          <th>Telepon</th>
          <th class="text-center">Terdaftar</th>
          <th class="text-center">Kunjungan</th>
          <th class="text-end no-print">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">
          <div class="empty-state">
            <i class="bi bi-people"></i>
            <?php if ($q !== ''): ?>
              Tidak ada tamu yang cocok dengan pencarian "<b><?= e($q) ?></b>".
            <?php else: ?>
              Belum ada data tamu. Mulai dengan menambahkan tamu baru.
            <?php endif; ?>
          </div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-muted small"><?= e($r['kode_tamu']) ?></td>
          <td class="fw-semibold"><?= e($r['nama']) ?></td>
          <td class="small"><?= e($r['jenis_identitas']) ?><?= $r['no_identitas'] ? ' &middot; ' . e($r['no_identitas']) : '' ?></td>
          <td class="small"><?= e($r['telepon'] ?: '-') ?></td>
          <td class="text-center">
            <?= $r['terdaftar'] > 0 ? '<span class="badge text-bg-success">Ya</span>' : '<span class="badge text-bg-secondary">Tidak</span>' ?>
          </td>
          <td class="text-center"><?= (int) $r['total_kunjungan'] ?></td>
          <td class="text-end no-print">
            <a href="<?= e(url($base . '/detail.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-navy btn-sm btn-icon" title="Detail"><i class="bi bi-eye"></i></a>
            <a href="<?= e(url($base . '/edit.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-secondary btn-sm" title="Ubah"><i class="bi bi-pencil"></i></a>
            <?php if (is_admin()): ?>
              <form method="post" action="<?= e(url('actions/tamu_process.php')) ?>" class="d-inline"
                    data-confirm="Hapus data tamu <?= e($r['nama']) ?>? Tindakan ini tidak dapat dibatalkan.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-outline-danger btn-sm" title="Hapus" type="submit">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end">
      <?= pagination($total, $page, $perPage, url($base . '/index.php'), array_filter(['q' => $q])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
