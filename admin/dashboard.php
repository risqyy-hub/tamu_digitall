<?php
/**
 * admin/dashboard.php
 * Dashboard admin: statistik & grafik dari database.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

/* ---------- Statistik ---------- */
$today = date('Y-m-d');

$stats = [
    'hari_ini'      => (int) db()->query("SELECT COUNT(*) FROM visits WHERE DATE(jam_masuk) = '{$today}'")->fetchColumn(),
    'di_kantor'     => (int) db()->query("SELECT COUNT(*) FROM visits WHERE status = 'berlangsung'")->fetchColumn(),
    'selesai_hari'  => (int) db()->query("SELECT COUNT(*) FROM visits WHERE status = 'selesai' AND DATE(jam_masuk) = '{$today}'")->fetchColumn(),
    'jaminan_aktif' => (int) db()->query("SELECT COUNT(*) FROM guarantees WHERE status = 'aktif'")->fetchColumn(),
    'tamu_terdaftar'=> (int) db()->query("SELECT COUNT(*) FROM registered_visitors WHERE status = 'aktif'")->fetchColumn(),
    'bulan_ini'     => (int) db()->query("SELECT COUNT(*) FROM visits WHERE jam_masuk >= DATE_FORMAT(CURDATE(), '%Y-%m-01')")->fetchColumn(),
];

/* ---------- Grafik kunjungan 7 hari ---------- */
$rows7 = db()->query(
    "SELECT DATE(jam_masuk) AS hari, COUNT(*) AS jml
     FROM visits WHERE jam_masuk >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(jam_masuk) ORDER BY hari ASC"
)->fetchAll();

$chartHari = [];
$chartJml  = [];
$mapJml = [];
foreach ($rows7 as $r) {
    $mapJml[date('Y-m-d', strtotime($r['hari']))] = (int) $r['jml'];
}
for ($i = 6; $i >= 0; $i--) {
    $tgl = date('Y-m-d', time() - $i * 86400);
    $chartHari[] = date('d M', strtotime($tgl));
    $chartJml[]  = $mapJml[$tgl] ?? 0;
}

/* ---------- Grafik status 30 hari ---------- */
$st30 = db()->query(
    "SELECT status, COUNT(*) AS jml FROM visits
     WHERE jam_masuk >= DATE_SUB(NOW(), INTERVAL 30 DAY)
     GROUP BY status"
)->fetchAll();
$statusMap = ['berlangsung' => 0, 'selesai' => 0];
foreach ($st30 as $s) {
    $statusMap[$s['status']] = (int) $s['jml'];
}

/* ---------- Tabel ---------- */
$terbaru = db()->query(
    "SELECT vi.*, v.nama AS nama_tamu, u.nama AS petugas_masuk
     FROM visits vi JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN users u ON u.id = vi.petugas_masuk_id
     ORDER BY vi.jam_masuk DESC LIMIT 6"
)->fetchAll();

$aktifList = db()->query(
    "SELECT vi.*, v.nama AS nama_tamu, pc.nama_perusahaan,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif
     FROM visits vi JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     WHERE vi.status = 'berlangsung'
     ORDER BY vi.jam_masuk ASC LIMIT 6"
)->fetchAll();

$logList = db()->query(
    "SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$page_title = 'Dashboard Admin';
$active_menu = 'dashboard';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-speedometer2 me-2"></i>Dashboard Admin</h1>
    <small class="text-muted">Ringkasan monitoring kunjungan &middot; <?= e(date('d/m/Y H:i', time())) ?> WIB</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url('admin/kunjungan/tambah.php')) ?>" class="btn btn-navy btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Tambah Kunjungan
    </a>
    <a href="<?= e(url('admin/laporan/index.php')) ?>" class="btn btn-outline-navy btn-sm">
      <i class="bi bi-file-earmark-bar-graph me-1"></i>Laporan
    </a>
  </div>
</div>

<!-- Kartu statistik -->
<div class="row g-3 mb-3">
  <?php
  $cards = [
      ['Kunjungan Hari Ini', $stats['hari_ini'], 'bi-door-open',        'bg-primary bg-opacity-10 text-primary'],
      ['Sedang di Kantor',   $stats['di_kantor'], 'bi-people-fill',     'bg-success bg-opacity-10 text-success'],
      ['Selesai Hari Ini',   $stats['selesai_hari'], 'bi-check2-circle','bg-secondary bg-opacity-10 text-secondary'],
      ['Jaminan Aktif',      $stats['jaminan_aktif'], 'bi-box-seam',    'bg-warning bg-opacity-10 text-warning'],
      ['Tamu Terdaftar',     $stats['tamu_terdaftar'], 'bi-person-check','bg-info bg-opacity-10 text-info'],
      ['Kunjungan Bulan Ini', $stats['bulan_ini'], 'bi-calendar3',      'bg-dark bg-opacity-10 text-navy'],
  ];
  foreach ($cards as [$label, $value, $icon, $cls]): ?>
    <div class="col-6 col-md-4 col-xl-2">
      <div class="card stat-card">
        <div class="card-body py-3">
          <div class="d-flex align-items-center gap-2 mb-2">
            <span class="stat-icon <?= e($cls) ?>"><i class="bi <?= e($icon) ?>"></i></span>
          </div>
          <div class="stat-value"><?= (int) $value ?></div>
          <div class="text-muted small"><?= e($label) ?></div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<!-- Grafik -->
<div class="row g-3 mb-3">
  <div class="col-lg-8">
    <div class="panel p-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-bar-chart-line me-2"></i>Kunjungan 7 Hari Terakhir</h2>
      <canvas id="chartKunjungan" height="110"></canvas>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="panel p-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-pie-chart me-2"></i>Status Kunjungan (30 hari)</h2>
      <canvas id="chartStatus" height="180"></canvas>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-hourglass-split me-2"></i>Sedang Berkunjung</h2>
        <a href="<?= e(url('admin/kunjungan/aktif.php')) ?>" class="small text-decoration-none">semua &raquo;</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <tbody>
          <?php if (!$aktifList): ?>
            <tr><td><div class="empty-state py-3 small"><i class="bi bi-check2-circle"></i>Tidak ada kunjungan aktif.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($aktifList as $k): ?>
            <tr>
              <td>
                <a href="<?= e(url('admin/kunjungan/detail.php?id=' . (int) $k['id'])) ?>" class="text-decoration-none fw-semibold">
                  <?= e($k['nama_tamu']) ?>
                </a><br>
                <span class="small text-muted"><?= e($k['nama_perusahaan'] ?: $k['tujuan']) ?></span>
              </td>
              <td class="small text-end">
                <span data-durasi-start="<?= e(date('c', strtotime($k['jam_masuk']))) ?>">
                  <?= e(format_durasi(durasi_berjalan($k['jam_masuk']))) ?>
                </span>
                <?php if ($k['jaminan_aktif'] > 0): ?>
                  <br><span class="badge text-bg-warning"><i class="bi bi-box-seam"></i> jaminan</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-4">
    <div class="panel">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-clock-history me-2"></i>Kunjungan Terbaru</h2>
        <a href="<?= e(url('admin/kunjungan/index.php')) ?>" class="small text-decoration-none">semua &raquo;</a>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <tbody>
          <?php if (!$terbaru): ?>
            <tr><td><div class="empty-state py-3 small"><i class="bi bi-inbox"></i>Belum ada kunjungan.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($terbaru as $k): ?>
            <tr>
              <td>
                <?= e($k['nama_tamu']) ?><br>
                <span class="small text-muted"><?= e($k['kode_kunjungan']) ?> &middot; <?= e(format_tgl_waktu($k['jam_masuk'])) ?></span>
              </td>
              <td class="text-end"><?= badge_status($k['status']) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-3">
    <div class="panel">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-activity me-2"></i>Aktivitas</h2>
        <a href="<?= e(url('admin/aktivitas.php')) ?>" class="small text-decoration-none">semua &raquo;</a>
      </div>
      <ul class="list-group list-group-flush small">
        <?php if (!$logList): ?>
          <li class="list-group-item text-muted">Belum ada aktivitas tercatat.</li>
        <?php endif; ?>
        <?php foreach ($logList as $log): ?>
          <li class="list-group-item">
            <?= e($log['aktivitas']) ?><br>
            <span class="text-muted"><?= e($log['username'] ?: 'sistem') ?> &middot; <?= e(format_tgl_waktu($log['created_at'])) ?></span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>

<script src="<?= e(asset('vendor/chartjs/chart.umd.min.js')) ?>"></script>
<script>
new Chart(document.getElementById('chartKunjungan'), {
  type: 'bar',
  data: {
    labels: <?= json_encode($chartHari) ?>,
    datasets: [{
      label: 'Jumlah kunjungan',
      data: <?= json_encode($chartJml) ?>,
      backgroundColor: 'rgba(11, 42, 91, .82)',
      borderRadius: 6
    }]
  },
  options: {
    plugins: { legend: { display: false } },
    scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
  }
});

new Chart(document.getElementById('chartStatus'), {
  type: 'doughnut',
  data: {
    labels: ['Berlangsung', 'Selesai'],
    datasets: [{
      data: [<?= (int) $statusMap['berlangsung'] ?>, <?= (int) $statusMap['selesai'] ?>],
      backgroundColor: ['#0b2a5b', '#94a3b8']
    }]
  },
  options: { plugins: { legend: { position: 'bottom' } } }
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
