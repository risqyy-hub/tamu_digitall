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
$visitId = get_int('visit_id');

$activeVisits = db()->query(
    "SELECT vi.id, vi.kode_kunjungan, v.nama AS nama_tamu
     FROM visits vi JOIN visitors v ON v.id = vi.visitor_id
     WHERE vi.status = 'berlangsung'
     ORDER BY vi.jam_masuk DESC"
)->fetchAll();

$selected = null;
if ($visitId > 0) {
    foreach ($activeVisits as $av) {
        if ((int) $av['id'] === $visitId) {
            $selected = $av;
            break;
        }
    }
    if (!$selected) {
        flash_set('warning', 'Kunjungan tidak ditemukan atau sudah selesai.');
        redirect($base . '/index.php');
    }
}

$page_title = 'Tambah Jaminan';
$active_menu = 'jaminan';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Jaminan</a></li>
    <li class="breadcrumb-item active">Tambah</li>
  </ol>
</nav>

<?php if (!$activeVisits): ?>
  <div class="alert alert-info">
    <i class="bi bi-info-circle me-1"></i>
    Saat ini tidak ada kunjungan yang sedang berlangsung. Jaminan hanya dapat ditambahkan saat kunjungan aktif.
    <a href="<?= e(url($AREA . '/kunjungan/tambah.php')) ?>">Buat kunjungan baru</a>.
  </div>
<?php endif; ?>

<div class="panel p-3 p-md-4" style="max-width:720px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-box-seam me-2"></i>Catat Penerimaan Jaminan</h2>

  <form method="post" action="<?= e(url('actions/jaminan_process.php')) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">

    <div class="row g-3">
      <div class="col-12">
        <label class="form-label small fw-semibold">Kunjungan <span class="text-danger">*</span></label>
        <select name="visit_id" class="form-select" required>
          <?php if ($selected): ?>
            <option value="<?= (int) $selected['id'] ?>" selected>
              <?= e($selected['kode_kunjungan']) ?> &middot; <?= e($selected['nama_tamu']) ?>
            </option>
          <?php else: ?>
            <option value="">-- Pilih kunjungan aktif --</option>
            <?php foreach ($activeVisits as $av): ?>
              <option value="<?= (int) $av['id'] ?>">
                <?= e($av['kode_kunjungan']) ?> &middot; <?= e($av['nama_tamu']) ?>
              </option>
            <?php endforeach; ?>
          <?php endif; ?>
        </select>
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
      <button type="submit" class="btn btn-navy" <?= !$activeVisits ? 'disabled' : '' ?>>
        <i class="bi bi-check-lg me-1"></i>Simpan Jaminan
      </button>
      <a href="<?= e(url($base . '/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
