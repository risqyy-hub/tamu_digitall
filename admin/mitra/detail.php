<?php
/**
 * admin/mitra/detail.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id = get_int('id');
$st = db()->prepare('SELECT * FROM partner_companies WHERE id = ?');
$st->execute([$id]);
$mitra = $st->fetch();

if (!$mitra) {
    flash_set('danger', 'Data mitra tidak ditemukan.');
    redirect('admin/mitra/index.php');
}

$st = db()->prepare(
    'SELECT rv.*, v.nama, v.kode_tamu FROM registered_visitors rv
     JOIN visitors v ON v.id = rv.visitor_id
     WHERE rv.mitra_id = ? ORDER BY rv.registered_at DESC LIMIT 10'
);
$st->execute([$id]);
$tamuTerdft = $st->fetchAll();

$st = db()->prepare(
    'SELECT vi.*, v.nama AS nama_tamu FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     WHERE vi.mitra_id = ? ORDER BY vi.jam_masuk DESC LIMIT 10'
);
$st->execute([$id]);
$kunjungan = $st->fetchAll();

$page_title = 'Detail Mitra';
$active_menu = 'mitra';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/mitra/index.php')) ?>" class="text-decoration-none">Perusahaan Mitra</a></li>
    <li class="breadcrumb-item active"><?= e($mitra['nama_perusahaan']) ?></li>
  </ol>
</nav>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="panel p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-building me-2"></i>Profil Mitra</h2>
        <?= badge_status($mitra['status']) ?>
      </div>
      <table class="table table-sm small mb-0">
        <tr><th style="width:38%">Kode</th><td><?= e($mitra['kode_mitra']) ?></td></tr>
        <tr><th>Nama</th><td class="fw-semibold"><?= e($mitra['nama_perusahaan']) ?></td></tr>
        <tr><th>Alamat</th><td><?= e($mitra['alamat'] ?: '-') ?></td></tr>
        <tr><th>Telepon</th><td><?= e($mitra['telepon'] ?: '-') ?></td></tr>
        <tr><th>Email</th><td><?= e($mitra['email'] ?: '-') ?></td></tr>
        <tr><th>PIC</th><td><?= e($mitra['pic_nama'] ?: '-') ?><?= $mitra['pic_telepon'] ? ' &middot; ' . e($mitra['pic_telepon']) : '' ?></td></tr>
        <tr><th>Terdaftar</th><td><?= e(format_tgl_lengkap($mitra['created_at'])) ?></td></tr>
      </table>
      <div class="mt-3 d-flex gap-2">
        <a href="<?= e(url('admin/mitra/edit.php?id=' . $id)) ?>" class="btn btn-navy btn-sm">
          <i class="bi bi-pencil me-1"></i>Ubah
        </a>
        <form method="post" action="<?= e(url('actions/mitra_process.php')) ?>" class="d-inline"
              data-confirm="Hapus mitra <?= e($mitra['nama_perusahaan']) ?>? Tindakan ini tidak dapat dibatalkan.">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="hapus">
          <input type="hidden" name="id" value="<?= (int) $id ?>">
          <button class="btn btn-outline-danger btn-sm" type="submit">
            <i class="bi bi-trash me-1"></i>Hapus
          </button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="panel mb-3">
      <div class="panel-header p-3">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-person-check me-2"></i>Tamu Terdaftar (<?= count($tamuTerdft) ?> terakhir)</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>Tamu</th><th>No. Kartu</th><th>Status</th><th>Terdaftar</th></tr></thead>
          <tbody>
          <?php if (!$tamuTerdft): ?>
            <tr><td colspan="4"><div class="empty-state py-3 small">Belum ada tamu terdaftar dari mitra ini.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($tamuTerdft as $t): ?>
            <tr>
              <td><?= e($t['nama']) ?> <span class="text-muted small">(<?= e($t['kode_tamu']) ?>)</span></td>
              <td class="small"><?= e($t['no_kartu'] ?: '-') ?></td>
              <td><?= badge_status($t['status']) ?></td>
              <td class="small"><?= e(format_tanggal($t['registered_at'])) ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="panel">
      <div class="panel-header p-3">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-door-open me-2"></i>Riwayat Kunjungan (<?= count($kunjungan) ?> terakhir)</h2>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead><tr><th>No. Kunjungan</th><th>Tamu</th><th>Masuk</th><th>Status</th><th></th></tr></thead>
          <tbody>
          <?php if (!$kunjungan): ?>
            <tr><td colspan="5"><div class="empty-state py-3 small">Belum ada kunjungan dari mitra ini.</div></td></tr>
          <?php endif; ?>
          <?php foreach ($kunjungan as $k): ?>
            <tr>
              <td class="small"><?= e($k['kode_kunjungan']) ?></td>
              <td class="small"><?= e($k['nama_tamu']) ?></td>
              <td class="small"><?= e(format_tgl_waktu($k['jam_masuk'])) ?></td>
              <td><?= badge_status($k['status']) ?></td>
              <td class="text-end">
                <a href="<?= e(url('admin/kunjungan/detail.php?id=' . (int) $k['id'])) ?>" class="btn btn-outline-navy btn-sm">
                  <i class="bi bi-eye"></i>
                </a>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
