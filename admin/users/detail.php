<?php
/**
 * admin/users/detail.php
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

$st = db()->prepare(
    'SELECT vi.*, v.nama AS nama_tamu FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     WHERE vi.petugas_masuk_id = ? ORDER BY vi.jam_masuk DESC LIMIT 10'
);
$st->execute([$id]);
$kunjungan = $st->fetchAll();

$st = db()->prepare('SELECT * FROM activity_logs WHERE user_id = ? ORDER BY created_at DESC LIMIT 10');
$st->execute([$id]);
$logs = $st->fetchAll();

$page_title = 'Detail Akun';
$active_menu = 'users';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/users/index.php')) ?>" class="text-decoration-none">Manajemen User</a></li>
    <li class="breadcrumb-item active"><?= e($user['username']) ?></li>
  </ol>
</nav>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-person-vcard me-2"></i>Info Akun</h2>
        <?= badge_status($user['status']) ?>
      </div>
      <table class="table table-sm small mb-0">
        <tr><th style="width:40%">Nama</th><td><?= e($user['nama']) ?></td></tr>
        <tr><th>Username</th><td>@<?= e($user['username']) ?></td></tr>
        <tr><th>Email</th><td><?= e($user['email'] ?: '-') ?></td></tr>
        <tr><th>Telepon</th><td><?= e($user['telepon'] ?: '-') ?></td></tr>
        <tr><th>Role</th><td><span class="badge text-bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= e(ucfirst($user['role'])) ?></span></td></tr>
        <tr><th>Terdaftar</th><td><?= e(format_tgl_lengkap($user['created_at'])) ?></td></tr>
      </table>
      <div class="mt-3">
        <a href="<?= e(url('admin/users/edit.php?id=' . $id)) ?>" class="btn btn-navy btn-sm">
          <i class="bi bi-pencil me-1"></i>Ubah Akun
        </a>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header p-3">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-door-open me-2"></i>Kunjungan Dicatat (<?= count($kunjungan) ?> terakhir)</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <tbody>
          <?php if (!$kunjungan): ?>
            <tr><td><div class="empty-state py-3 small">Belum pernah mencatat kunjungan.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($kunjungan as $k): ?>
            <tr>
              <td class="small"><?= e($k['kode_kunjungan']) ?><br><span class="text-muted"><?= e($k['nama_tamu']) ?></span></td>
              <td class="small text-end"><?= e(format_tgl_waktu($k['jam_masuk'])) ?><br><?= badge_status($k['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header p-3">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2"></i>Aktivitas (<?= count($logs) ?> terakhir)</h2>
      </div>
      <ul class="list-group list-group-flush small">
        <?php if (!$logs): ?>
          <li class="list-group-item text-muted">Belum ada aktivitas.</li>
        <?php endif; ?>
        <?php foreach ($logs as $log): ?>
          <li class="list-group-item">
            <?= e($log['aktivitas']) ?><br>
            <span class="text-muted"><?= e(format_tgl_waktu($log['created_at'])) ?> &middot; <?= e($log['ip_address']) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
