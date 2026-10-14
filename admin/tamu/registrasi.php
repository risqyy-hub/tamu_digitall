<?php
/**
 * admin/tamu/registrasi.php
 * Kelola tamu terdaftar (registered_visitors) - syarat penerbitan QR.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$q   = get_str('q');
$page = max(1, get_int('page', 1));
$perPage = 15;

$conds  = [];
$params = [];
if ($q !== '') {
    $conds[] = '(v.nama LIKE ? OR v.kode_tamu LIKE ? OR rv.no_kartu LIKE ? OR pc.nama_perusahaan LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like, $like);
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare(
    "SELECT COUNT(*) FROM registered_visitors rv
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     {$where}"
);
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT rv.*, v.nama, v.kode_tamu, v.telepon, pc.nama_perusahaan,
            (SELECT COUNT(*) FROM visitor_qr_codes q WHERE q.registered_visitor_id = rv.id) AS jumlah_qr
     FROM registered_visitors rv
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     {$where}
     ORDER BY rv.registered_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$tamuList   = db()->query('SELECT id, nama, kode_tamu FROM visitors ORDER BY created_at DESC LIMIT 300')->fetchAll();
$mitraList  = db()->query("SELECT id, nama_perusahaan FROM partner_companies WHERE status = 'aktif' ORDER BY nama_perusahaan")->fetchAll();

$page_title = 'Tamu Terdaftar';
$active_menu = 'registrasi';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-person-check me-2"></i>Tamu Terdaftar</h1>
    <small class="text-muted"><?= $total ?> registrasi &middot; tamu terdaftar dapat memiliki QR Code check-in</small>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel p-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-plus me-2"></i>Daftarkan Tamu</h2>
      <form method="post" action="<?= e(url('actions/registrasi_process.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="tambah">
        <div class="mb-3">
          <label class="form-label small fw-semibold">Tamu <span class="text-danger">*</span></label>
          <select name="visitor_id" class="form-select" required>
            <option value="">-- Pilih tamu --</option>
            <?php foreach ($tamuList as $t): ?>
              <option value="<?= (int) $t['id'] ?>"><?= e($t['nama']) ?> (<?= e($t['kode_tamu']) ?>)</option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">Tamu belum ada di data?
            <a href="<?= e(url('admin/tamu/tambah.php?return=' . urlencode('admin/tamu/registrasi.php'))) ?>">Tambah tamu baru</a>.
          </div>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">Perusahaan Mitra (opsional)</label>
          <select name="mitra_id" class="form-select">
            <option value="">-- Tanpa mitra --</option>
            <?php foreach ($mitraList as $m): ?>
              <option value="<?= (int) $m['id'] ?>"><?= e($m['nama_perusahaan']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label small fw-semibold">No. Kartu (opsional)</label>
          <input type="text" name="no_kartu" class="form-control" maxlength="50" placeholder="mis. KRT-0001">
        </div>
        <button class="btn btn-navy w-100" type="submit"><i class="bi bi-check-lg me-1"></i>Daftarkan</button>
      </form>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-header p-3">
        <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/tamu/registrasi.php')) ?>">
          <div class="col-md-8">
            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Cari nama, kode tamu, no. kartu, mitra..." value="<?= e($q) ?>">
          </div>
          <div class="col-md-4 d-flex gap-2">
            <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i> Cari</button>
            <?php if ($q !== ''): ?>
              <a href="<?= e(url('admin/tamu/registrasi.php')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
            <?php endif; ?>
          </div>
        </form>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Tamu</th><th>Mitra</th><th>No. Kartu</th>
              <th class="text-center">QR</th><th>Status</th><th>Terdaftar</th>
              <th class="text-end">Aksi</th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$rows): ?>
            <tr><td colspan="7">
              <div class="empty-state"><i class="bi bi-person-x"></i>Belum ada registrasi tamu terdaftar.</div>
            </td></tr>
          <?php endif; ?>
          <?php foreach ($rows as $r): ?>
            <tr>
              <td>
                <span class="fw-semibold"><?= e($r['nama']) ?></span><br>
                <span class="text-muted small"><?= e($r['kode_tamu']) ?></span>
              </td>
              <td class="small"><?= e($r['nama_perusahaan'] ?: '-') ?></td>
              <td class="small"><?= e($r['no_kartu'] ?: '-') ?></td>
              <td class="text-center small"><?= (int) $r['jumlah_qr'] ?></td>
              <td><?= badge_status($r['status']) ?></td>
              <td class="small"><?= e(format_tanggal($r['registered_at'])) ?></td>
              <td class="text-end text-nowrap">
                <form method="post" action="<?= e(url('actions/registrasi_process.php')) ?>" class="d-inline">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="ubah_status">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <input type="hidden" name="status" value="<?= $r['status'] === 'aktif' ? 'nonaktif' : 'aktif' ?>">
                  <button class="btn btn-sm <?= $r['status'] === 'aktif' ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                          type="submit" title="<?= $r['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>">
                    <i class="bi <?= $r['status'] === 'aktif' ? 'bi-pause-circle' : 'bi-play-circle' ?>"></i>
                  </button>
                </form>
                <a href="<?= e(url('admin/qr/tambah.php?reg_id=' . (int) $r['id'])) ?>"
                   class="btn btn-outline-navy btn-sm btn-icon" title="Terbitkan QR">
                  <i class="bi bi-qr-code"></i>
                </a>
                <form method="post" action="<?= e(url('actions/registrasi_process.php')) ?>" class="d-inline"
                      data-confirm="Hapus registrasi <?= e($r['nama']) ?>? QR terkait ikut terhapus, riwayat kunjungan tetap ada.">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="hapus">
                  <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                  <button class="btn btn-outline-danger btn-sm" type="submit" title="Hapus">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total > $perPage): ?>
        <div class="p-3 d-flex justify-content-end">
          <?= pagination($total, $page, $perPage, url('admin/tamu/registrasi.php'), array_filter(['q' => $q])) ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
