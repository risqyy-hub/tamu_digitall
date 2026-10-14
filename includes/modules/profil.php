<?php
/**
 * includes/modules/profil.php
 * Profil pengguna login: ubah data diri + ganti password.
 * Dipakai: admin/profil.php dan petugas/profil.php ($AREA di-set wrapper).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$user = current_user();

// Statistik singkat
$st = db()->prepare('SELECT COUNT(*) FROM visits WHERE petugas_masuk_id = ?');
$st->execute([$user['id']]);
$totalKunjungan = (int) $st->fetchColumn();

$page_title = 'Profil Saya';
$active_menu = 'profil';
require __DIR__ . '/../header.php';
?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel p-4 text-center">
      <div class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-navy text-white"
           style="width:72px;height:72px;border-radius:1rem">
        <i class="bi bi-person-fill fs-2"></i>
      </div>
      <h2 class="h6 fw-bold text-navy mb-0"><?= e($user['nama']) ?></h2>
      <p class="text-muted small mb-1">@<?= e($user['username']) ?></p>
      <span class="badge text-bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
        <?= e(ucfirst($user['role'])) ?>
      </span>
      <table class="table table-sm small mt-3 mb-0 text-start">
        <tr><th>Email</th><td><?= e($user['email'] ?: '-') ?></td></tr>
        <tr><th>Telepon</th><td><?= e($user['telepon'] ?: '-') ?></td></tr>
        <tr><th>Status</th><td><?= badge_status($user['status']) ?></td></tr>
        <tr><th>Kunjungan dicatat</th><td><?= $totalKunjungan ?></td></tr>
      </table>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel p-3 p-md-4">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-lines-fill me-2"></i>Ubah Data Diri</h2>
      <form method="post" action="<?= e(url('actions/profil_process.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="update_profile">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
            <input type="text" name="nama" class="form-control" required maxlength="100" value="<?= e($user['nama']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Username</label>
            <input type="text" class="form-control bg-light" value="<?= e($user['username']) ?>" readonly>
            <div class="form-text">Username diatur oleh administrator.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control" maxlength="100" value="<?= e($user['email']) ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Telepon</label>
            <input type="text" name="telepon" class="form-control" maxlength="20" value="<?= e($user['telepon']) ?>">
          </div>
        </div>
        <button type="submit" class="btn btn-navy mt-3"><i class="bi bi-check-lg me-1"></i>Simpan Perubahan</button>
      </form>
    </div>

    <div class="panel p-3 p-md-4 mt-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-key me-2"></i>Ganti Password</h2>
      <form method="post" action="<?= e(url('actions/profil_process.php')) ?>" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="change_password">
        <div class="row g-3">
          <div class="col-12">
            <label class="form-label small fw-semibold">Password Lama <span class="text-danger">*</span></label>
            <input type="password" name="password_lama" class="form-control" required autocomplete="current-password">
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Password Baru <span class="text-danger">*</span></label>
            <input type="password" name="password_baru" class="form-control" required minlength="8" autocomplete="new-password">
            <div class="form-text">Minimal 8 karakter.</div>
          </div>
          <div class="col-md-6">
            <label class="form-label small fw-semibold">Ulangi Password Baru <span class="text-danger">*</span></label>
            <input type="password" name="password_ulang" class="form-control" required minlength="8" autocomplete="new-password">
          </div>
        </div>
        <button type="submit" class="btn btn-navy mt-3"><i class="bi bi-shield-lock me-1"></i>Ganti Password</button>
      </form>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../footer.php'; ?>

