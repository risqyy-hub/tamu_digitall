<?php
/**
 * includes/navbar.php
 * Navbar atas: nama instansi, lonceng notifikasi, menu pengguna.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

$user = current_user();

// Notifikasi belum dibaca (milik sendiri + untuk semua pengguna)
$notifList = [];
$notifUnread = 0;
try {
    $st = db()->prepare(
        'SELECT id, judul, pesan, tipe, created_at FROM notifications
         WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0
         ORDER BY created_at DESC LIMIT 5'
    );
    $st->execute([$user['id'] ?? 0]);
    $notifList = $st->fetchAll();

    $st2 = db()->prepare('SELECT COUNT(*) FROM notifications WHERE (user_id = ? OR user_id IS NULL) AND is_read = 0');
    $st2->execute([$user['id'] ?? 0]);
    $notifUnread = (int) $st2->fetchColumn();
} catch (Throwable $e) {
    // abaikan bila tabel belum tersedia
}
?>
<nav class="navbar navbar-expand navbar-light app-navbar bg-white border-bottom sticky-top px-3">
  <button class="btn btn-light border me-2 d-lg-none" type="button"
          data-bs-toggle="offcanvas" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu"
          aria-label="Buka menu">
    <i class="bi bi-list"></i>
  </button>
  <span class="navbar-brand fw-bold text-navy mb-0">
    <i class="bi bi-journal-bookmark-fill me-1"></i><?= e(setting('nama_instansi', APP_NAME)) ?>
  </span>

  <ul class="navbar-nav ms-auto align-items-center">
    <li class="nav-item dropdown me-2">
      <a class="nav-link position-relative" href="#" data-bs-toggle="dropdown" aria-expanded="false"
         title="Notifikasi">
        <i class="bi bi-bell fs-5"></i>
        <?php if ($notifUnread > 0): ?>
          <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                style="font-size:.62rem"><?= $notifUnread > 99 ? '99+' : $notifUnread ?></span>
        <?php endif; ?>
      </a>
      <div class="dropdown-menu dropdown-menu-end notif-menu" style="min-width:320px">
        <h6 class="dropdown-header">Notifikasi</h6>
        <?php if (!$notifList): ?>
          <p class="text-muted small px-3 py-2 mb-0">Tidak ada notifikasi baru.</p>
        <?php endif; ?>
        <?php foreach ($notifList as $n): ?>
          <form method="post" action="<?= e(url('actions/notif_process.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_read">
            <input type="hidden" name="id" value="<?= (int) $n['id'] ?>">
            <button class="dropdown-item notif-item text-start w-100" type="submit">
              <span class="d-block fw-semibold"><?= e($n['judul']) ?></span>
              <span class="d-block text-muted small"><?= e(mb_substr((string) $n['pesan'], 0, 80)) ?></span>
              <span class="d-block text-muted" style="font-size:.7rem"><?= e(format_tgl_waktu($n['created_at'])) ?></span>
            </button>
          </form>
        <?php endforeach; ?>
        <?php if ($notifUnread > 0): ?>
          <form method="post" action="<?= e(url('actions/notif_process.php')) ?>" class="border-top">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="mark_all">
            <button class="dropdown-item text-center text-primary small" type="submit">
              Tandai semua sudah dibaca
            </button>
          </form>
        <?php endif; ?>
      </div>
    </li>

    <li class="nav-item dropdown">
      <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" data-bs-toggle="dropdown"
         aria-expanded="false">
        <span class="rounded-circle bg-navy text-white d-inline-flex align-items-center justify-content-center"
              style="width:32px;height:32px">
          <i class="bi bi-person-fill"></i>
        </span>
        <span class="ms-2 d-none d-sm-inline small">
          <?= e($user['nama'] ?? '') ?> <span class="text-muted">(<?= e(ucfirst($user['role'] ?? '')) ?>)</span>
        </span>
      </a>
      <ul class="dropdown-menu dropdown-menu-end">
        <li><a class="dropdown-item" href="<?= e(url('admin/profil.php')) ?>"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
        <li><hr class="dropdown-divider"></li>
        <li>
          <form method="post" action="<?= e(url('logout.php')) ?>" class="m-0">
            <?= csrf_field() ?>
            <button class="dropdown-item text-danger w-100 text-start" type="submit">
              <i class="bi bi-box-arrow-right me-2"></i>Keluar
            </button>
          </form>
        </li>
      </ul>
    </li>
  </ul>
</nav>
