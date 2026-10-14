<?php
/**
 * login.php
 * Halaman login admin & petugas.
 */
require_once __DIR__ . '/includes/functions.php';

// Sudah login? langsung ke dashboard
if (current_user()) {
    redirect('index.php');
}

$page_title = 'Login';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Login &middot; <?= e(setting('nama_instansi', APP_NAME)) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" media="print" onload="this.media='all'"
      href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="auth-body">

<div class="card auth-card">
  <div class="card-body p-4 p-md-5">
    <div class="text-center mb-4">
      <div class="auth-logo mx-auto mb-3">
        <i class="bi bi-journal-bookmark-fill fs-2"></i>
      </div>
      <h1 class="h5 fw-bold text-navy mb-1">Buku Tamu Digital</h1>
      <p class="text-muted small mb-1"><?= e(setting('nama_instansi', APP_NAME)) ?></p>
      <p class="text-muted small">Silakan login untuk melanjutkan</p>
    </div>

    <?php foreach (flash_all() as $f): ?>
      <div class="alert alert-<?= e($f['type']) ?> py-2 small" role="alert">
        <i class="bi bi-info-circle me-1"></i><?= e($f['message']) ?>
      </div>
    <?php endforeach; ?>

    <form method="post" action="<?= e(url('actions/login_process.php')) ?>" autocomplete="off">
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="username">Username / Email</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" id="username" name="username"
                 value="<?= e($_POST['username'] ?? '') ?>" placeholder="mis. admin" required autofocus>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label small fw-semibold" for="password">Password</label>
        <div class="input-group">
          <span class="input-group-text bg-light"><i class="bi bi-lock"></i></span>
          <input type="password" class="form-control" id="password" name="password"
                 placeholder="Password" required>
          <button class="btn btn-outline-secondary" type="button" id="togglePass"
                  aria-label="Tampilkan password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-cream w-100 py-2">
        <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
      </button>
    </form>

    <p class="text-center text-muted small mt-4 mb-0">
      Layanan buku tamu: <?= e(setting('jam_buka', '08:00')) ?> &ndash; <?= e(setting('jam_tutup', '16:00')) ?> WIB
    </p>
  </div>
</div>

<script>
document.getElementById('togglePass').addEventListener('click', function () {
  var inp = document.getElementById('password');
  var icon = this.querySelector('i');
  var show = inp.type === 'password';
  inp.type = show ? 'text' : 'password';
  icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
});
</script>
</body>
</html>
