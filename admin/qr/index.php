<?php
/**
 * admin/qr/index.php
 * Daftar QR Code: status, pencabutan, penghapusan.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$q    = get_str('q');
$statusFilter = get_str('status');
$page = max(1, get_int('page', 1));
$perPage = 15;

$conds = []; $params = [];
if ($q !== '') {
    $conds[] = '(v.nama LIKE ? OR v.kode_tamu LIKE ? OR pc.nama_perusahaan LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if (in_array($statusFilter, ['aktif', 'dicabut'], true)) {
    $conds[]  = 'q.status = ?';
    $params[] = $statusFilter;
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare(
    "SELECT COUNT(*) FROM visitor_qr_codes q
     JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     {$where}"
);
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT q.*, v.nama, v.kode_tamu, pc.nama_perusahaan, u.nama AS issued_nama,
            (SELECT COUNT(*) FROM visits vi WHERE vi.qr_id = q.id) AS jumlah_dipakai
     FROM visitor_qr_codes q
     JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     LEFT JOIN users u ON u.id = q.issued_by
     {$where}
     ORDER BY q.created_at DESC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$regList = db()->query(
    "SELECT rv.id, v.nama, v.kode_tamu, pc.nama_perusahaan
     FROM registered_visitors rv
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     WHERE rv.status = 'aktif'
     ORDER BY rv.registered_at DESC LIMIT 300"
)->fetchAll();

$page_title = 'QR Code';
$active_menu = 'qr';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-qr-code me-2"></i>QR Code Tamu Terdaftar</h1>
    <small class="text-muted"><?= $total ?> QR diterbitkan &middot; token disimpan sebagai hash SHA-256</small>
  </div>
  <a href="<?= e(url('admin/qr/tambah.php')) ?>" class="btn btn-navy btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Terbitkan QR
  </a>
</div>

<div class="alert alert-info py-2 small">
  <i class="bi bi-shield-lock me-1"></i>
  Token QR bersifat acak 256-bit dan <b>hanya ditampilkan satu kali</b> saat penerbitan.
  QR kedaluwarsa/dicabut akan ditolak server saat dipindai.
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/qr/index.php')) ?>">
      <div class="col-md-5">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Cari nama tamu / mitra..." value="<?= e($q) ?>">
      </div>
      <div class="col-md-3">
        <select name="status" class="form-select form-select-sm">
          <option value="">Semua status</option>
          <option value="aktif" <?= $statusFilter === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="dicabut" <?= $statusFilter === 'dicabut' ? 'selected' : '' ?>>Dicabut</option>
        </select>
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i> Filter</button>
        <?php if ($q !== '' || $statusFilter !== ''): ?>
          <a href="<?= e(url('admin/qr/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>#</th><th>Tamu</th><th>Mitra</th><th>Berlaku</th>
          <th>Status</th><th class="text-center">Dipakai</th><th>Diterbitkan</th>
          <th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">
          <div class="empty-state"><i class="bi bi-qr-code"></i>Belum ada QR Code diterbitkan.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <?php
          $kedaluwarsa = ($r['status'] === 'aktif' && $r['valid_until'] && strtotime($r['valid_until']) < time());
          $statusAktual = $r['status'] === 'aktif' ? ($kedaluwarsa ? 'kedaluwarsa' : 'aktif') : $r['status'];
        ?>
        <tr>
          <td class="text-muted small">#<?= (int) $r['id'] ?></td>
          <td>
            <span class="fw-semibold"><?= e($r['nama']) ?></span><br>
            <span class="text-muted small"><?= e($r['kode_tamu']) ?></span>
          </td>
          <td class="small"><?= e($r['nama_perusahaan'] ?: '-') ?></td>
          <td class="small">
            mulai <?= e(format_tgl_waktu($r['valid_from'])) ?><br>
            s.d. <?= e($r['valid_until'] ? format_tgl_waktu($r['valid_until']) : 'tanpa batas') ?>
          </td>
          <td><?= badge_status($statusAktual) ?></td>
          <td class="text-center small"><?= (int) $r['jumlah_dipakai'] ?> kunjungan</td>
          <td class="small"><?= e($r['issued_nama'] ?: '-') ?><br><?= e(format_tgl_waktu($r['created_at'])) ?></td>
          <td class="text-end text-nowrap">
            <a href="<?= e(url('admin/qr/tampil.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-navy btn-sm btn-icon" title="Lihat QR (hanya bisa saat baru diterbitkan)">
              <i class="bi bi-eye"></i>
            </a>
            <?php if ($r['status'] === 'aktif'): ?>
              <form method="post" action="<?= e(url('actions/qr_process.php')) ?>" class="d-inline"
                    data-confirm="Cabut QR milik <?= e($r['nama']) ?>? QR tidak dapat dipakai check-in lagi.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="cabut">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-outline-warning btn-sm" type="submit" title="Cabut QR">
                  <i class="bi bi-slash-circle"></i>
                </button>
              </form>
            <?php else: ?>
              <form method="post" action="<?= e(url('actions/qr_process.php')) ?>" class="d-inline"
                    data-confirm="Hapus QR #<?= (int) $r['id'] ?> dari daftar?">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-outline-danger btn-sm" type="submit" title="Hapus">
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
      <?= pagination($total, $page, $perPage, url('admin/qr/index.php'), array_filter(['q' => $q, 'status' => $statusFilter])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
