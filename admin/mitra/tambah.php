<?php
/**
 * admin/mitra/tambah.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$page_title = 'Tambah Mitra';
$active_menu = 'mitra';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/mitra/index.php')) ?>" class="text-decoration-none">Perusahaan Mitra</a></li>
    <li class="breadcrumb-item active">Tambah</li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:760px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-building-add me-2"></i>Form Tambah Mitra</h2>
  <form method="post" action="<?= e(url('actions/mitra_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Perusahaan / Instansi <span class="text-danger">*</span></label>
        <input type="text" name="nama_perusahaan" class="form-control" required maxlength="150"
               value="<?= e($_POST['nama_perusahaan'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Status Kerja Sama</label>
        <select name="status" class="form-select">
          <option value="aktif">Aktif</option>
          <option value="nonaktif">Nonaktif</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2" maxlength="1000"><?= e($_POST['alamat'] ?? '') ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= e($_POST['telepon'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($_POST['email'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Nama PIC</label>
        <input type="text" name="pic_nama" class="form-control" maxlength="100" value="<?= e($_POST['pic_nama'] ?? '') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Telepon PIC</label>
        <input type="text" name="pic_telepon" class="form-control" maxlength="20" value="<?= e($_POST['pic_telepon'] ?? '') ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Mitra</button>
      <a href="<?= e(url('admin/mitra/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
