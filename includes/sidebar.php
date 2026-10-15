<?php
/**
 * includes/sidebar.php
 * Sidebar navigasi (responsif) - menu menyesuaikan role pengguna.
 *
 * Catatan revisi:
 *  - Menu "Jaminan" tidak lagi berdiri sendiri; pengelolaan jaminan diintegrasikan
 *    ke halaman Kunjungan Aktif + detail kunjungan (tautan "Semua Jaminan" tetap tersedia).
 *  - Sidebar Petugas disusun ulang: Menu Utama > Layanan Tamu > Akun,
 *    dengan "Tambah Kunjungan" berupa dropdown (input manual & pemindaian QR).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

$role = current_role();
$menu = [];

if ($role === 'admin') {
    $menu = [
        ['label' => 'MENU UTAMA', 'items' => [
            ['key' => 'dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'admin/dashboard.php'],
        ]],
        ['label' => 'DATA MASTER', 'items' => [
            ['key' => 'tamu',       'icon' => 'bi-people',       'label' => 'Data Tamu',        'href' => 'admin/tamu/index.php'],
            ['key' => 'registrasi', 'icon' => 'bi-person-check', 'label' => 'Tamu Terdaftar',   'href' => 'admin/tamu/registrasi.php'],
            ['key' => 'mitra',      'icon' => 'bi-building',     'label' => 'Perusahaan Mitra', 'href' => 'admin/mitra/index.php'],
        ]],
        ['label' => 'KUNJUNGAN', 'items' => [
            ['key' => 'qr',        'icon' => 'bi-qr-code',           'label' => 'QR Code',         'href' => 'admin/qr/index.php'],
            ['key' => 'kunjungan', 'icon' => 'bi-door-open',         'label' => 'Semua Kunjungan', 'href' => 'admin/kunjungan/index.php'],
            ['key' => 'aktif',     'icon' => 'bi-hourglass-split',   'label' => 'Kunjungan Aktif', 'href' => 'admin/kunjungan/aktif.php'],
            // Menu "Jaminan" terpisah DIHAPUS: seluruh fungsi (lihat, tambah,
            // kembalikan, riwayat) diakses lewat Kunjungan Aktif & detail kunjungan.
        ]],
        ['label' => 'LAPORAN & SISTEM', 'items' => [
            ['key' => 'laporan',    'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Laporan',        'href' => 'admin/laporan/index.php'],
            ['key' => 'users',      'icon' => 'bi-person-gear',            'label' => 'Manajemen User', 'href' => 'admin/users/index.php'],
            ['key' => 'aktivitas',  'icon' => 'bi-clock-history',          'label' => 'Log Aktivitas',  'href' => 'admin/aktivitas.php'],
            ['key' => 'pengaturan', 'icon' => 'bi-gear',                   'label' => 'Pengaturan',     'href' => 'admin/pengaturan.php'],
            ['key' => 'profil',     'icon' => 'bi-person-circle',          'label' => 'Profil',         'href' => 'admin/profil.php'],
        ]],
    ];
} else {
    $menu = [
        ['label' => 'MENU UTAMA', 'items' => [
            ['key' => 'dashboard', 'icon' => 'bi-speedometer2', 'label' => 'Dashboard', 'href' => 'petugas/dashboard.php'],
        ]],
        ['label' => 'LAYANAN TAMU', 'items' => [
            ['key' => 'aktif', 'icon' => 'bi-hourglass-split', 'label' => 'Kunjungan Aktif', 'href' => 'petugas/kunjungan/aktif.php'],
            ['key' => 'tambah_kunjungan', 'icon' => 'bi-plus-lg', 'label' => 'Tambah Kunjungan', 'children' => [
                ['key' => 'tambah',  'icon' => 'bi-pencil-square', 'label' => 'Input Manual',   'href' => 'petugas/kunjungan/tambah.php'],
                ['key' => 'scanner', 'icon' => 'bi-qr-code-scan',  'label' => 'Pindai QR Code', 'href' => 'petugas/qr/scanner.php'],
            ]],
            ['key' => 'kunjungan', 'icon' => 'bi-door-open', 'label' => 'Kunjungan', 'href' => 'petugas/kunjungan/index.php'],
            ['key' => 'tamu',      'icon' => 'bi-people',    'label' => 'Data Tamu', 'href' => 'petugas/tamu/index.php'],
        ]],
        ['label' => 'AKUN', 'items' => [
            ['key' => 'profil', 'icon' => 'bi-person-circle', 'label' => 'Profil', 'href' => 'petugas/profil.php'],
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

      <?php foreach ($group['items'] as $item): ?>
        <?php if (!empty($item['children'])):
            // Submenu dropdown (accordion - menekan konten, bukan menutupinya)
            $subOpen = false;
            foreach ($item['children'] as $child) {
                if ($active_menu === $child['key']) {
                    $subOpen = true;
                    break;
                }
            }
            $subId = 'submenu-' . $item['key'];
        ?>
          <details class="sidebar-menu" <?= $subOpen ? 'open' : '' ?>>
            <summary class="sidebar-link<?= $subOpen ? ' active' : '' ?>" aria-controls="<?= e($subId) ?>">
              <i class="bi <?= e($item['icon']) ?>"></i>
              <span class="sidebar-label"><?= e($item['label']) ?></span>
              <i class="bi bi-chevron-down sidebar-caret ms-auto"></i>
            </summary>
            <div class="sidebar-submenu" id="<?= e($subId) ?>">
              <?php foreach ($item['children'] as $child): ?>
                <a class="sidebar-link sidebar-sublink<?= $active_menu === $child['key'] ? ' active' : '' ?>"
                   href="<?= e(url($child['href'])) ?>">
                  <i class="bi <?= e($child['icon']) ?>"></i><span class="sidebar-label"><?= e($child['label']) ?></span>
                </a>
              <?php endforeach; ?>
            </div>
          </details>
        <?php else: ?>
          <a class="sidebar-link<?= $active_menu === $item['key'] ? ' active' : '' ?>"
             href="<?= e(url($item['href'])) ?>">
            <i class="bi <?= e($item['icon']) ?>"></i><span class="sidebar-label"><?= e($item['label']) ?></span>
          </a>
        <?php endif; ?>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>
</aside>
