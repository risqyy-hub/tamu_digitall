<?php
/**
 * includes/modules/tamu/detail.php
 * Detail tamu: identitas, status registrasi, dan riwayat kunjungan.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base = $AREA . '/tamu';
$id   = get_int('id');

$st = db()->prepare('SELECT * FROM visitors WHERE id = ?');
$st->execute([$id]);
$tamu = $st->fetch();

if (!$tamu) {
    flash_set('danger', 'Data tamu tidak ditemukan.');
    redirect($base . '/index.php');
}

// Riwayat registrasi
$st = db()->prepare(
    'SELECT rv.*, pc.nama_perusahaan FROM registered_visitors rv
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     WHERE rv.visitor_id = ? ORDER BY rv.registered_at DESC'
);
$st->execute([$id]);
$registrasi = $st->fetchAll();

// Riwayat kunjungan terakhir
$st = db()->prepare(
    'SELECT vi.*, u.nama AS petugas_masuk FROM visits vi
     LEFT JOIN users u ON u.id = vi.petugas_masuk_id
     WHERE vi.visitor_id = ? ORDER BY vi.jam_masuk DESC LIMIT 15'
);
$st->execute([$id]);
$kunjungan = $st->fetchAll();

$page_title = 'Detail Tamu';
$active_menu = 'tamu';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Data Tamu</a></li>
    <li class="breadcrumb-item active"><?= e($tamu['nama']) ?></li>
  </ol>
</nav>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="panel p-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-vcard me-2"></i>Identitas</h2>
      <table class="table table-sm small mb-0">
        <tr><th style="width:40%">Kode Tamu</th><td><?= e($tamu['kode_tamu']) ?></td></tr>
        <tr><th>Nama</th><td class="fw-semibold"><?= e($tamu['nama']) ?></td></tr>
        <tr><th>Identitas</th><td><?= e($tamu['jenis_identitas']) ?></td></tr>
        <tr><th>No. Identitas</th><td><?= e($tamu['no_identitas'] ?: '-') ?></td></tr>
        <tr><th>Telepon</th><td><?= e($tamu['telepon'] ?: '-') ?></td></tr>
        <tr><th>Email</th><td><?= e($tamu['email'] ?: '-') ?></td></tr>
        <tr><th>Alamat</th><td><?= e($tamu['alamat'] ?: '-') ?></td></tr>
        <tr><th>Terdaftar</th><td><?= e(format_tgl_lengkap($tamu['created_at'])) ?></td></tr>
      </table>
      <div class="mt-3 d-flex gap-2 no-print">
        <a href="<?= e(url($base . '/edit.php?id=' . $id)) ?>" class="btn btn-navy btn-sm">
          <i class="bi bi-pencil me-1"></i>Ubah
        </a>
        <a href="<?= e(url($AREA . '/kunjungan/tambah.php?visitor_id=' . $id)) ?>"
           class="btn btn-outline-navy btn-sm">
          <i class="bi bi-door-open me-1"></i>Buat Kunjungan
        </a>
      </div>
    </div>

    <div class="panel p-3 mt-3">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-person-check me-2"></i>Registrasi</h2>
      <?php if (!$registrasi): ?>
        <p class="text-muted small mb-0">Tamu ini belum terdaftar sebagai tamu terdaftar (pemilik QR).</p>
      <?php else: ?>
        <ul class="list-unstyled small mb-0">
          <?php foreach ($registrasi as $rg): ?>
            <li class="mb-2 border-bottom pb-2">
              <span class="fw-semibold"><?= e($rg['nama_perusahaan'] ?: 'Tanpa instansi') ?></span><br>
              <span class="text-muted">No. kartu: <?= e($rg['no_kartu'] ?: '-') ?> &middot; <?= e(format_tanggal($rg['registered_at'])) ?></span>
              <?= badge_status($rg['status']) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="panel">
      <div class="panel-header p-3 d-flex justify-content-between align-items-center">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-door-open me-2"></i>Riwayat Kunjungan (<?= count($kunjungan) ?> terakhir)</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>No. Kunjungan</th><th>Tujuan</th><th>Petugas</th>
              <th>Masuk</th><th>Keluar</th><th>Status</th><th class="no-print"></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$kunjungan): ?>
            <tr><td colspan="7">
              <div class="empty-state"><i class="bi bi-door-closed"></i>Belum ada riwayat kunjungan.</div>
            </td></tr>
          <?php endif; ?>
          <?php foreach ($kunjungan as $k): ?>
            <tr>
              <td class="small"><?= e($k['kode_kunjungan']) ?></td>
              <td class="small"><?= e($k['tujuan']) ?></td>
              <td class="small"><?= e($k['petugas_masuk'] ?: '-') ?></td>
              <td class="small"><?= e(format_tgl_waktu($k['jam_masuk'])) ?></td>
              <td class="small"><?= e(format_tgl_waktu($k['jam_keluar'])) ?></td>
              <td><?= badge_status($k['status']) ?></td>
              <td class="text-end no-print">
                <a href="<?= e(url($AREA . '/kunjungan/detail.php?id=' . (int) $k['id'])) ?>"
                   class="btn btn-outline-navy btn-sm"><i class="bi bi-eye"></i></a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
