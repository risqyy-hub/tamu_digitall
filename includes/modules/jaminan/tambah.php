<?php
/**
 * includes/modules/jaminan/tambah.php
 * Form pencatatan penerimaan jaminan (dari kunjungan yang masih berlangsung).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base    = $AREA . '/jaminan';
$visitIdRaw = get_str('visit_id');
$visitId = ctype_digit($visitIdRaw) ? (int) $visitIdRaw : 0;
$visit = null;
$visitError = '';

if ($visitId <= 0) {
    $visitError = 'ID kunjungan tidak tersedia atau tidak valid. Buka formulir dari detail kunjungan yang masih berlangsung.';
} else {
    $st = db()->prepare(
        'SELECT vi.id, vi.kode_kunjungan, vi.status, v.nama AS nama_tamu
         FROM visits vi
         JOIN visitors v ON v.id = vi.visitor_id
         WHERE vi.id = ?'
    );
    $st->execute([$visitId]);
    $visit = $st->fetch() ?: null;

    if (!$visit) {
        $visitError = 'Kunjungan tidak ditemukan. Periksa kembali ID kunjungan.';
    } elseif ($visit['status'] !== 'berlangsung') {
        $visitError = 'Jaminan hanya dapat ditambahkan pada kunjungan yang masih berlangsung.';
    }
}

$page_title = 'Tambah Jaminan';
$active_menu = 'aktif';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Jaminan</a></li>
    <li class="breadcrumb-item active">Tambah</li>
  </ol>
</nav>

<?php if ($visitError !== ''): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-circle me-1"></i><?= e($visitError) ?>
    <a href="<?= e(url($AREA . '/kunjungan/aktif.php')) ?>">Lihat kunjungan aktif</a>.
  </div>
<?php endif; ?>

<div class="panel p-3 p-md-4" style="max-width:720px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-box-seam me-2"></i>Catat Penerimaan Jaminan</h2>

  <?php if ($visit && $visitError === ''): ?>
  <form method="post" action="<?= e(url('actions/jaminan_process.php')) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">
    <input type="hidden" name="visit_id" value="<?= (int) $visit['id'] ?>">

    <div class="row g-3">
      <div class="col-12">
        <div class="alert alert-light border mb-0">
          <span class="small text-muted">Kunjungan</span><br>
          <strong><?= e($visit['kode_kunjungan']) ?></strong>
          <span class="text-muted">&middot; <?= e($visit['nama_tamu']) ?></span>
        </div>
      </div>
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Barang / Jaminan <span class="text-danger">*</span></label>
        <input type="text" name="barang" class="form-control" required maxlength="150"
               placeholder="mis. KTP, SIM A, Laptop, Kunci Kendaraan" value="<?= e($_POST['barang'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Jumlah <span class="text-danger">*</span></label>
        <input type="number" name="jumlah" class="form-control" min="1" max="999" value="<?= e($_POST['jumlah'] ?? '1') ?>" required>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Bukti Foto/Dokumen (opsional)</label>
        <input type="file" name="bukti" class="form-control form-control-sm" accept=".jpg,.jpeg,.png,.pdf">
        <div class="form-text">Format: JPG/PNG/PDF, maksimal <?= MAX_UPLOAD_MB ?> MB.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Keterangan</label>
        <textarea name="keterangan" class="form-control" rows="2" maxlength="500"
                  placeholder="Kondisi barang, catatan penerimaan, dsb."><?= e($_POST['keterangan'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy">
        <i class="bi bi-check-lg me-1"></i>Simpan Jaminan
      </button>
      <a href="<?= e(url($AREA . '/kunjungan/detail.php?id=' . (int) $visit['id'])) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
  <?php else: ?>
    <a href="<?= e(url($AREA . '/kunjungan/aktif.php')) ?>" class="btn btn-outline-secondary">Kembali</a>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
