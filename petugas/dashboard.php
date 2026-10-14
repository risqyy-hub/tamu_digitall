<?php
/**
 * petugas/dashboard.php
 * Dashboard petugas / resepsionis.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('petugas');

$today = date('Y-m-d');

$stats = [
    'checkin_hari' => (int) db()->query("SELECT COUNT(*) FROM visits WHERE DATE(jam_masuk) = '{$today}'")->fetchColumn(),
    'aktif'        => (int) db()->query("SELECT COUNT(*) FROM visits WHERE status = 'berlangsung'")->fetchColumn(),
    'selesai_hari' => (int) db()->query("SELECT COUNT(*) FROM visits WHERE status = 'selesai' AND DATE(jam_masuk) = '{$today}'")->fetchColumn(),
    'jaminan_aktif'=> (int) db()->query("SELECT COUNT(*) FROM guarantees WHERE status = 'aktif'")->fetchColumn(),
];

$aktifList = db()->query(
    "SELECT vi.*, v.nama AS nama_tamu, v.telepon, pc.nama_perusahaan,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif
     FROM visits vi JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     WHERE vi.status = 'berlangsung'
     ORDER BY vi.jam_masuk ASC LIMIT 8"
)->fetchAll();

$terbaru = db()->query(
    "SELECT vi.*, v.nama AS nama_tamu FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     ORDER BY vi.jam_masuk DESC LIMIT 5"
)->fetchAll();

$logList = db()->query(
    'SELECT * FROM activity_logs WHERE user_id = ' . (int) current_user_id() . '
     ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

$page_title = 'Dashboard Petugas';
$active_menu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard Petugas</h1>
    <small class="text-muted">Selamat bertugas, <?= e(current_user()['nama']) ?> &middot; <?= e(date('d/m/Y H:i')) ?> WIB</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url('petugas/qr/scanner.php')) ?>" class="btn btn-navy btn-sm">
      <i class="bi bi-qr-code-scan me-1"></i>Scan QR
    </a>
    <a href="<?= e(url('petugas/kunjungan/tambah.php')) ?>" class="btn btn-outline-navy btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Tambah Kunjungan
    </a>
  </div>
</div>

<div class="row g-3 mb-3">
  <?php
  $cards = [
      ['Check-in Hari Ini', $stats['checkin_hari'], 'bi-door-open', 'bg-primary bg-opacity-10 text-primary'],
      ['Sedang Berkunjung', $stats['aktif'], 'bi-people-fill', 'bg-success bg-opacity-10 text-success'],
      ['Selesai Hari Ini',  $stats['selesai_hari'], 'bi-check2-circle', 'bg-secondary bg-opacity-10 text-secondary'],
      ['Jaminan Aktif',     $stats['jaminan_aktif'], 'bi-box-seam', 'bg-warning bg-opacity-10 text-warning'],
  ];
  foreach ($cards as [$label, $value, $icon, $cls]): ?>
    <div class="col-6 col-xl-3">
      <div class="card stat-card">
        <div class="card-body">
          <span class="stat-icon <?= e($cls) ?> mb-3"><i class="bi <?= e($icon) ?>"></i></span>
          <div class="stat-value"><?= (int) $value ?></div>
          <div class="stat-label"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="panel">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-hourglass-split me-2"></i>Sedang Berkunjung</h2>
        <a href="<?= e(url('petugas/kunjungan/aktif.php')) ?>" class="small text-decoration-none">semua &raquo;</a>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr><th>Tamu</th><th>Tujuan</th><th class="text-center">Durasi</th><th class="text-center">Jaminan</th><th class="text-end"></th></tr>
          </thead>
          <tbody>
          <?php if (!$aktifList): ?>
            <tr><td colspan="5">
              <div class="empty-state"><i class="bi bi-check2-circle"></i>Tidak ada tamu yang sedang berkunjung.</div>
            </td></tr>
          <?php endif; ?>
          <?php foreach ($aktifList as $k): ?>
            <tr>
              <td>
                <span class="fw-semibold"><?= e($k['nama_tamu']) ?></span><br>
                <span class="text-muted small"><?= e($k['nama_perusahaan'] ?: $k['telepon']) ?></span>
              </td>
              <td class="small">
                <?= e($k['tujuan']) ?><br><span class="text-muted">&rarr; <?= e($k['pegawai_ditemui']) ?></span>
                <?php if (hasil_kunjungan_ada($k['hasil_kunjungan'] ?? null)): ?>
                  <br><span class="text-muted"><i class="bi bi-journal-text"></i>
                    <span class="hasil-clamp" title="<?= e($k['hasil_kunjungan']) ?>"><?= e($k['hasil_kunjungan']) ?></span>
                  </span>
                <?php endif; ?>
              </td>
              <td class="text-center small" data-durasi-start="<?= e(date('c', strtotime($k['jam_masuk']))) ?>">
                <?= e(format_durasi(durasi_berjalan($k['jam_masuk']))) ?>
              </td>
              <td class="text-center">
                <?php if ($k['jaminan_aktif'] > 0): ?>
                  <span class="badge text-bg-warning"><?= (int) $k['jaminan_aktif'] ?></span>
                <?php else: ?>
                  <span class="text-muted small">-</span>
                <?php endif; ?>
              </td>
              <td class="text-end">
                <a href="<?= e(url('petugas/kunjungan/detail.php?id=' . (int) $k['id'])) ?>"
                   class="btn btn-outline-navy btn-sm btn-icon" title="Lihat detail"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="panel mb-3">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2"></i>Kunjungan Terakhir</h2>
        <a href="<?= e(url('petugas/kunjungan/index.php')) ?>" class="small text-decoration-none">semua &raquo;</a>
      </div>
      <ul class="list-group list-group-flush small">
        <?php if (!$terbaru): ?>
          <li class="list-group-item text-muted">Belum ada kunjungan tercatat.</li>
        <?php endif; ?>
        <?php foreach ($terbaru as $k): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span>
              <a href="<?= e(url('petugas/kunjungan/detail.php?id=' . (int) $k['id'])) ?>" class="text-decoration-none fw-semibold">
                <?= e($k['nama_tamu']) ?>
              </a><br>
              <span class="text-muted"><?= e($k['kode_kunjungan']) ?> &middot; <?= e(format_tgl_waktu($k['jam_masuk'])) ?></span>
            </span>
            <?= badge_status($k['status']) ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>

    <div class="panel">
      <div class="panel-header p-3">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-activity me-2"></i>Aktivitas Saya</h2>
      </div>
      <ul class="list-group list-group-flush small activity-timeline">
        <?php if (!$logList): ?>
          <li class="list-group-item text-muted">Belum ada aktivitas.</li>
        <?php endif; ?>
        <?php foreach ($logList as $log): ?>
          <li class="list-group-item">
            <?= e($log['aktivitas']) ?><br>
            <span class="text-muted"><?= e(format_tgl_waktu($log['created_at'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
