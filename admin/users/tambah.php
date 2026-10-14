<?php
/**
 * admin/users/tambah.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$page_title = 'Tambah Akun';
$active_menu = 'users';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/users/index.php')) ?>" class="text-decoration-none">Manajemen User</a></li>
    <li class="breadcrumb-item active">Tambah</li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:720px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-plus me-2"></i>Tambah Akun Petugas/Admin</h2>
  <form method="post" action="<?= e(url('actions/user_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="tambah">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required maxlength="100">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control" required maxlength="50"
               pattern="[a-zA-Z0-9._]{3,50}" placeholder="huruf, angka, titik, underscore">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" maxlength="100">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control" required minlength="8" autocomplete="new-password">
        <div class="form-text">Minimal 8 karakter. Disimpan sebagai hash bcrypt.</div>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Role <span class="text-danger">*</span></label>
        <select name="role" class="form-select">
          <option value="petugas">Petugas / Resepsionis</option>
          <option value="admin">Admin</option>
        </select>
      </div>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Akun</button>
      <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
