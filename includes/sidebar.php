<?php
/**
 * includes/sidebar.php
 * Sidebar navigasi (responsif) - menu menyesuaikan role pengguna.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

$role = current_role();
$menu = [];

if ($role === 'admin') {
    $menu = [
        ['label' => 'MENU UTAMA', 'items' => [
            ['dashboard', 'bi-speedometer2', 'Dashboard', 'admin/dashboard.php'],
        ]],
        ['label' => 'DATA MASTER', 'items' => [
            ['tamu',       'bi-people',      'Data Tamu',        'admin/tamu/index.php'],
            ['registrasi', 'bi-person-check','Tamu Terdaftar',   'admin/tamu/registrasi.php'],
            ['mitra',      'bi-building',    'Perusahaan Mitra', 'admin/mitra/index.php'],
        ]],
        ['label' => 'KUNJUNGAN', 'items' => [
            ['qr',       'bi-qr-code',   'QR Code',         'admin/qr/index.php'],
            ['kunjungan','bi-door-open', 'Semua Kunjungan', 'admin/kunjungan/index.php'],
            ['aktif',    'bi-hourglass-split', 'Kunjungan Aktif', 'admin/kunjungan/aktif.php'],
            ['jaminan',  'bi-box-seam',  'Jaminan',         'admin/jaminan/index.php'],
        ]],
        ['label' => 'LAPORAN & SISTEM', 'items' => [
            ['laporan',     'bi-file-earmark-bar-graph', 'Laporan',         'admin/laporan/index.php'],
            ['users',       'bi-person-gear',            'Manajemen User',  'admin/users/index.php'],
            ['aktivitas',   'bi-clock-history',          'Log Aktivitas',   'admin/aktivitas.php'],
            ['pengaturan',  'bi-gear',                   'Pengaturan',      'admin/pengaturan.php'],
            ['profil',      'bi-person-circle',          'Profil',          'admin/profil.php'],
        ]],
    ];
} else {
    $menu = [
        ['label' => 'MENU UTAMA', 'items' => [
            ['dashboard', 'bi-speedometer2', 'Dashboard', 'petugas/dashboard.php'],
        ]],
        ['label' => 'LAYANAN TAMU', 'items' => [
            ['tamu',       'bi-people',      'Data Tamu',   'petugas/tamu/index.php'],
            ['scanner',    'bi-qr-code-scan','Scan QR',     'petugas/qr/scanner.php'],
            ['kunjungan',  'bi-door-open',   'Kunjungan',   'petugas/kunjungan/index.php'],
            ['aktif',      'bi-hourglass-split', 'Kunjungan Aktif', 'petugas/kunjungan/aktif.php'],
            ['jaminan',    'bi-box-seam',    'Jaminan',     'petugas/jaminan/index.php'],
        ]],
        ['label' => 'AKUN', 'items' => [
            ['profil', 'bi-person-circle', 'Profil', 'petugas/profil.php'],
        ]],
    ];
}
?>
<aside class="sidebar offcanvas-lg offcanvas-start" tabindex="-1" id="sidebarMenu">
  <div class="offcanvas-header d-lg-none">
    <span class="fw-bold"><i class="bi bi-journal-bookmark-fill me-1"></i>Menu</span>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#sidebarMenu"
            aria-label="Tutup menu"></button>
  </div>

  <div class="sidebar-body p-2">
    <?php foreach ($menu as $group): ?>
      <div class="sidebar-heading px-3 pt-3 pb-1 text-uppercase"><?= e($group['label']) ?></div>
      <?php foreach ($group['items'] as [$key, $icon, $label, $href]): ?>
        <a class="sidebar-link<?= $active_menu === $key ? ' active' : '' ?>"
           href="<?= e(url($href)) ?>">
          <i class="bi <?= e($icon) ?> me-2"></i><?= e($label) ?>
        </a>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</aside>
