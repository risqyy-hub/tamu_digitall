<?php
/**
 * admin/users/edit.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = get_int('id');
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$id]);
$user = $st->fetch();

if (!$user) {
    flash_set('danger', 'Akun tidak ditemukan.');
    redirect('admin/users/index.php');
}

$isSelf = ((int) $user['id'] === (int) current_user_id());

$page_title = 'Ubah Akun';
$active_menu = 'users';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/users/index.php')) ?>" class="text-decoration-none">Manajemen User</a></li>
    <li class="breadcrumb-item active">Ubah: <?= e($user['username']) ?></li>
  </ol>
</nav>

<div class="panel p-3 p-md-4" style="max-width:720px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-gear me-2"></i>Ubah Akun: <?= e($user['username']) ?></h2>

  <?php if ($isSelf): ?>
    <div class="alert alert-info py-2 small">
      <i class="bi bi-info-circle me-1"></i>
      Ini akun Anda sendiri: role & status tidak dapat diubah di sini, dan password diganti melalui menu Profil.
    </div>
  <?php endif; ?>

  <form method="post" action="<?= e(url('actions/user_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="edit">
    <input type="hidden" name="id" value="<?= (int) $user['id'] ?>">
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control" required maxlength="100" value="<?= e($user['nama']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Username</label>
        <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" readonly>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($user['email']) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Telepon</label>
        <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= e($user['telepon']) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Role</label>
        <select name="role" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
          <option value="petugas" <?= $user['role'] === 'petugas' ? 'selected' : '' ?>>Petugas</option>
          <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
        </select>
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select" <?= $isSelf ? 'disabled' : '' ?>>
          <option value="aktif" <?= $user['status'] === 'aktif' ? 'selected' : '' ?>>Aktif</option>
          <option value="nonaktif" <?= $user['status'] === 'nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
        </select>
      </div>
      <?php if (!$isSelf): ?>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Password Baru (opsional)</label>
          <input type="password" name="password" class="form-control" minlength="8" autocomplete="new-password">
          <div class="form-text">Kosongkan jika tidak diganti.</div>
        </div>
      <?php endif; ?>
    </div>
    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
      <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
