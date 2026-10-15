<?php
/**
 * includes/modules/tamu/edit.php
 * Form ubah data tamu.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base = $AREA . '/tamu';
$id   = get_int('id');

$st = db()->prepare('SELECT * FROM visitors WHERE id = ?');
$st->execute([$id]);
$tamu = $st->fetch();

if (!$tamu) {
    flash_set('danger', 'Data tamu tidak ditemukan.');
    redirect($base . '/index.php');
}

$page_title = 'Ubah Tamu';
$active_menu = 'tamu';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Data Tamu</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/detail.php?id=' . $id)) ?>" class="text-decoration-none"><?= e($tamu['nama']) ?></a></li>
    <li class="breadcrumb-item active">Ubah</li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:760px">
  <h2 class="h6 fw-bold text-navy mb-3">
    <i class="bi bi-pencil-square me-2"></i>Ubah Tamu: <?= e($tamu['kode_tamu']) ?>
  </h2>

  <form method="post" action="<?= e(url('actions/tamu_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="<?= (int) $tamu['id'] ?>">

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required maxlength="100"
               value="<?= e($tamu['nama']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Jenis Identitas <span class="text-danger">*</span></label>
        <select name="jenis_identitas" class="form-select" required>
          <?php foreach (['KTP', 'SIM', 'Paspor', 'Kartu Pegawai', 'Lainnya'] as $j): ?>
            <option value="<?= e($j) ?>" <?= ($tamu['jenis_identitas'] === $j) ? 'selected' : '' ?>><?= e($j) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Nomor Identitas</label>
        <input type="text" name="no_identitas" class="form-control" maxlength="50" value="<?= e($tamu['no_identitas']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">No. Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= e($tamu['telepon']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Email (opsional)</label>
        <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($tamu['email']) ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2" maxlength="500"><?= e($tamu['alamat']) ?></textarea>
      </div>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
      <a href="<?= e(url($base . '/detail.php?id=' . $id)) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
