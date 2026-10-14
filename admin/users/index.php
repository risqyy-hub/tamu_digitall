<?php
/**
 * admin/users/index.php
 * Manajemen akun admin & petugas.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$q    = get_str('q');
$page = max(1, get_int('page', 1));
$perPage = 15;

$conds = []; $params = [];
if ($q !== '') {
    $conds[] = '(nama LIKE ? OR username LIKE ? OR email LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare("SELECT COUNT(*) FROM users {$where}");
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT u.*,
            (SELECT COUNT(*) FROM visits vi WHERE vi.petugas_masuk_id = u.id) AS jumlah_kunjungan
     FROM users u
     {$where}
     ORDER BY u.created_at ASC
     LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$page_title = 'Manajemen User';
$active_menu = 'users';
require __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-person-gear me-2"></i>Manajemen User</h1>
    <small class="text-muted"><?= $total ?> akun terdaftar</small>
  </div>
  <a href="<?= e(url('admin/users/tambah.php')) ?>" class="btn btn-navy btn-sm">
    <i class="bi bi-person-plus me-1"></i>Tambah Akun
  </a>
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/users/index.php')) ?>">
      <div class="col-md-6">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Cari nama, username, email..." value="<?= e($q) ?>">
      </div>
      <div class="col-md-3 d-flex gap-2">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-search"></i> Cari</button>
        <?php if ($q !== ''): ?>
          <a href="<?= e(url('admin/users/index.php')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
        <?php endif; ?>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>Nama</th><th>Username</th><th>Kontak</th><th>Role</th>
          <th class="text-center">Kunjungan Dicatat</th><th>Status</th><th class="text-end">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="7">
          <div class="empty-state"><i class="bi bi-person-gear"></i>Tidak ada akun yang cocok.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): $isSelf = ((int) $r['id'] === (int) current_user_id()); ?>
        <tr>
          <td>
            <span class="fw-semibold"><?= e($r['nama']) ?></span>
            <?php if ($isSelf): ?><span class="badge text-bg-light border ms-1">Anda</span><?php endif; ?>
          </td>
          <td class="small">@<?= e($r['username']) ?></td>
          <td class="small"><?= e($r['email'] ?: '-') ?><br><span class="text-muted"><?= e($r['telepon'] ?: '') ?></span></td>
          <td><span class="badge text-bg-<?= $r['role'] === 'admin' ? 'primary' : 'secondary' ?>"><?= e(ucfirst($r['role'])) ?></span></td>
          <td class="text-center"><?= (int) $r['jumlah_kunjungan'] ?></td>
          <td><?= badge_status($r['status']) ?></td>
          <td class="text-end text-nowrap">
            <a href="<?= e(url('admin/users/detail.php?id=' . (int) $r['id'])) ?>" class="btn btn-outline-navy btn-sm" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <a href="<?= e(url('admin/users/edit.php?id=' . (int) $r['id'])) ?>" class="btn btn-outline-secondary btn-sm" title="Ubah">
              <i class="bi bi-pencil"></i>
            </a>
            <?php if (!$isSelf): ?>
              <form method="post" action="<?= e(url('actions/user_process.php')) ?>" class="d-inline"
                    data-confirm="Hapus akun <?= e($r['username']) ?>? Riwayat kunjungan tetap tersimpan.">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="hapus">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-outline-danger btn-sm" type="submit" title="Hapus">
                  <i class="bi bi-trash"></i>
                </button>
              </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end">
      <?= pagination($total, $page, $perPage, url('admin/users/index.php'), array_filter(['q' => $q])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
