<?php
/**
 * admin/aktivitas.php
 * Log aktivitas (audit trail) dengan filter & pagination.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$q    = get_str('q');
$userFilter = get_int('user_id');
$dari = get_str('dari');
$sampai = get_str('sampai');
$page = max(1, get_int('page', 1));
$perPage = 20;

$conds = []; $params = [];
if ($q !== '') {
    $conds[] = '(l.aktivitas LIKE ? OR l.username LIKE ? OR l.ip_address LIKE ?)';
    $like = '%' . $q . '%';
    array_push($params, $like, $like, $like);
}
if ($userFilter > 0) {
    $conds[]  = 'l.user_id = ?';
    $params[] = $userFilter;
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dari)) {
    $conds[]  = 'l.created_at >= ?';
    $params[] = $dari . ' 00:00:00';
}
if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sampai)) {
    $conds[]  = 'l.created_at <= ?';
    $params[] = $sampai . ' 23:59:59';
}
$where = $conds ? 'WHERE ' . implode(' AND ', $conds) : '';

$st = db()->prepare("SELECT COUNT(*) FROM activity_logs l {$where}");
$st->execute($params);
$total = (int) $st->fetchColumn();

$offset = ($page - 1) * $perPage;
$st = db()->prepare(
    "SELECT l.* FROM activity_logs l {$where} ORDER BY l.created_at DESC LIMIT {$perPage} OFFSET {$offset}"
);
$st->execute($params);
$rows = $st->fetchAll();

$userList = db()->query('SELECT id, nama, username FROM users ORDER BY nama')->fetchAll();

$page_title = 'Log Aktivitas';
$active_menu = 'aktivitas';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-3">
  <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2"></i>Log Aktivitas</h1>
  <small class="text-muted"><?= (int) $total ?> aktivitas tercatat</small>
</div>

<div class="panel">
  <div class="panel-header p-3">
    <form class="row g-2 filter-form" method="get" action="<?= e(url('admin/aktivitas.php')) ?>">
      <div class="col-md-3">
        <input type="text" name="q" class="form-control form-control-sm"
               placeholder="Cari aktivitas / user / IP..." value="<?= e($q) ?>">
      </div>
      <div class="col-md-2">
        <select name="user_id" class="form-select form-select-sm">
          <option value="">Semua pengguna</option>
          <?php foreach ($userList as $u): ?>
            <option value="<?= (int) $u['id'] ?>" <?= $userFilter === (int) $u['id'] ? 'selected' : '' ?>>
              <?= e($u['nama']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>">
      </div>
      <div class="col-md-2">
        <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>">
      </div>
      <div class="col-md-2 d-flex gap-2">
        <button class="btn btn-navy btn-sm" type="submit"><i class="bi bi-funnel me-1"></i>Filter</button>
        <a href="<?= e(url('admin/aktivitas.php')) ?>" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>

  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr><th>Waktu</th><th>Pengguna</th><th>Role</th><th>Aktivitas</th><th>IP Address</th></tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="5">
          <div class="empty-state"><i class="bi bi-clock-history"></i>Tidak ada aktivitas dengan filter ini.</div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small text-nowrap"><?= e(format_tgl_lengkap($r['created_at'])) ?></td>
          <td class="small"><?= e($r['username'] ?: 'sistem') ?></td>
          <td class="small"><?= e($r['role'] ?: '-') ?></td>
          <td class="small"><?= e($r['aktivitas']) ?></td>
          <td class="small text-muted"><?= e($r['ip_address'] ?: '-') ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="p-3 d-flex justify-content-end">
      <?= pagination($total, $page, $perPage, url('admin/aktivitas.php'), array_filter([
          'q' => $q, 'user_id' => $userFilter ?: '', 'dari' => $dari, 'sampai' => $sampai,
      ])) ?>
    </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
