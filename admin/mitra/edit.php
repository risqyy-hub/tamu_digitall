<?php
/**
 * admin/mitra/edit.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = get_int('id');
$st = db()->prepare('SELECT * FROM partner_companies WHERE id = ?');
$st->execute([$id]);
$mitra = $st->fetch();

if (!$mitra) {
    flash_set('danger', 'Data mitra tidak ditemukan.');
    redirect('admin/mitra/index.php');
}

$page_title = 'Ubah Mitra';
$active_menu = 'mitra';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/mitra/index.php')) ?>" class="text-decoration-none">Perusahaan Mitra</a></li>
    <li class="breadcrumb-item"><a href="<?= e(url('admin/mitra/detail.php?id=' . $id)) ?>" class="text-decoration-none"><?= e($mitra['nama_perusahaan']) ?></a></li>
    <li class="breadcrumb-item active">Ubah</li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:760px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-building-add me-2"></i>Ubah Mitra: <?= e($mitra['kode_mitra']) ?></h2>
  <form method="post" action="<?= e(url('actions/mitra_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="<?= (int) $mitra['id'] ?>">
    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Perusahaan / Instansi <span class="text-danger">*</span></label>
        <input type="text" name="nama_perusahaan" class="form-control" required maxlength="150"
               value="<?= e($mitra['nama_perusahaan']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Status Kerja Sama</label>
        <select name="status" class="form-select">
          <option value="aktif" <?= $mitra['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $mitra['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Alamat</label>
        <textarea name="alamat" class="form-control" rows="2" maxlength="1000"><?= e($mitra['alamat']) ?></textarea>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= e($mitra['telepon']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($mitra['email']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Nama PIC</label>
        <input type="text" name="pic_nama" class="form-control" maxlength="100" value="<?= e($mitra['pic_nama']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Telepon PIC</label>
        <input type="text" name="pic_telepon" class="form-control" maxlength="20" value="<?= e($mitra['pic_telepon']) ?>">
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
      <a href="<?= e(url('admin/mitra/detail.php?id=' . $id)) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
