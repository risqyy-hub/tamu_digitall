<?php
/**
 * includes/modules/tamu/tambah.php
 * Form tambah tamu (admin & petugas).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base  = $AREA . '/tamu';
$return = get_str('return', $base . '/index.php');

$page_title = 'Tambah Tamu';
$active_menu = 'tamu';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Data Tamu</a></li>
    <li class="breadcrumb-item active">Tambah</li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:760px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-plus me-2"></i>Form Tambah Tamu</h2>

  <form method="post" action="<?= e(url('actions/tamu_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">
    <input type="hidden" name="return" value="<?= e($return) ?>">

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required maxlength="100"
               placeholder="Nama sesuai identitas" value="<?= e($_POST['nama'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Jenis Identitas <span class="text-danger">*</span></label>
        <select name="jenis_identitas" class="form-select" required>
          <?php foreach (['KTP', 'SIM', 'Paspor', 'Kartu Pegawai', 'Lainnya'] as $j): ?>
            <option value="<?= e($j) ?>" <?= (($_POST['jenis_identitas'] ?? 'KTP') === $j) ? 'selected' : '' ?>><?= e($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">No. Identitas</label>
        <input type="text" name="no_identitas" class="form-control" maxlength="50"
               placeholder="NIK / no. SIM / paspor" value="<?= e($_POST['no_identitas'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">No. Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20"
               placeholder="08xxxxxxxxxx" value="<?= e($_POST['telepon'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Email (opsional)</label>
        <input type="email" name="email" class="form-control" maxlength="100"
               placeholder="nama@email.com" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2" maxlength="500"><?= e($_POST['alamat'] ?? '') ?></textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Tamu</button>
      <a href="<?= e(url($return)) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
