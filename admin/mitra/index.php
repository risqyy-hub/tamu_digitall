<?php
/**
 * admin/mitra/index.php
 * Daftar perusahaan mitra.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$q    = get_str('q');
$page = max(1, get_int('page', 1));
$perPage = 15;

$conds = []; $params = [];
if ($q !== '') {
    $conds[] = '(pc.nama_perusahaan LIKE ? OR pc.kode_mitra LIKE ? OR pc.pic_nama LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare("SELECT COUNT(*) FROM partner_companies pc {$where}");
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT pc.*,
            (SELECT COUNT(*) FROM registered_visitors rv WHERE rv.mitra_id = pc.id) AS jumlah_tamu,
            (SELECT COUNT(*) FROM visits vi WHERE vi.mitra_id = pc.id) AS jumlah_kunjungan
     FROM partner_companies pc
     {$where}
     ORDER BY pc.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$page_title = 'Perusahaan Mitra';
$active_menu = 'mitra';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-building me-2"></i>Perusahaan Mitra</h1>
    <small class="text-muted"><?= $total ?> mitra terdaftar</small>
  </div>
  <a href="<?= e(url('admin/mitra/tambah.php')) ?>" class="btn btn-navy btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Tambah Mitra
  </a>
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/mitra/index.php')) ?>">
      <div class="col-md-6">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Cari nama mitra, kode, PIC..." value="<?= e($q) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i> Cari</button>
        <?php if ($q !== ''): ?>
          <a href="<?= e(url('admin/mitra/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Kode</th><th>Nama Perusahaan</th><th>Kontak</th>
          <th class="text-center">Tamu</th><th class="text-center">Kunjungan</th>
          <th>Status</th><th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">
          <div class="empty-state"><i class="bi bi-building"></i>Belum ada data perusahaan mitra.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small text-muted"><?= e($r['kode_mitra']) ?></td>
          <td class="fw-semibold"><?= e($r['nama_perusahaan']) ?></td>
          <td class="small">
            <?= e($r['pic_nama'] ?: '-') ?><br>
            <span class="text-muted"><?= e($r['telepon'] ?: ($r['pic_telepon'] ?: '-')) ?></span>
          </td>
          <td class="text-center"><?= (int) $r['jumlah_tamu'] ?></td>
          <td class="text-center"><?= (int) $r['jumlah_kunjungan'] ?></td>
          <td><?= badge_status($r['status']) ?></td>
          <td class="text-end">
            <a href="<?= e(url('admin/mitra/detail.php?id=' . (int) $r['id'])) ?>" class="btn btn-outline-navy btn-sm btn-icon" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <a href="<?= e(url('admin/mitra/edit.php?id=' . (int) $r['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Ubah">
              <i class="bi bi-pencil"></i>
            </a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end">
      <?= pagination($total, $page, $perPage, url('admin/mitra/index.php'), array_filter(['q' => $q])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
