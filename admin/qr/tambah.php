<?php
/**
 * admin/qr/tambah.php
 * Form penerbitan QR untuk tamu terdaftar.
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$regPref = get_int('reg_id');
$regList = db()->query(
    "SELECT rv.id, v.nama, v.kode_tamu, pc.nama_perusahaan
     FROM registered_visitors rv
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     WHERE rv.status = 'aktif'
     ORDER BY rv.registered_at DESC LIMIT 300"
)->fetchAll();

$defaultUntil = date('Y-m-d\TH:i', time() + ((int) setting('qr_default_days', '30')) * 86400);
$nowLocal = date('Y-m-d\TH:i');

$page_title = 'Terbitkan QR';
$active_menu = 'qr';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/qr/index.php')) ?>" class="text-decoration-none">QR Code</a></li>
    <li class="breadcrumb-item active">Terbitkan</li>
  </ol>
</nav>

<?php if (!$regList): ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle me-1"></i>
    Belum ada tamu terdaftar berstatus aktif.
    <a href="<?= e(url('admin/tamu/registrasi.php')) ?>">Daftarkan tamu terlebih dahulu</a>.
  </div>
<?php endif; ?>

<div class="panel p-3 p-md-4" style="max-width:720px">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-qr-code me-2"></i>Terbitkan QR Code</h2>

  <form method="post" action="<?= e(url('actions/qr_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="terbitkan">

    <div class="mb-3">
      <label class="form-label small fw-semibold">Tamu Terdaftar <span class="text-danger">*</span></label>
      <select name="registered_visitor_id" class="form-select" required>
        <option value="">-- Pilih tamu terdaftar --</option>
        <?php foreach ($regList as $r): ?>
          <option value="<?= (int) $r['id'] ?>" <?= $regPref === (int) $r['id'] ? 'selected' : '' ?>>
            <?= e($r['nama']) ?> (<?= e($r['kode_tamu']) ?>)<?= $r['nama_perusahaan'] ? ' - ' . e($r['nama_perusahaan']) : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Berlaku Mulai</label>
        <input type="datetime-local" name="valid_from" class="form-control" value="<?= e($nowLocal) ?>">
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Berlaku Sampai</label>
        <input type="datetime-local" name="valid_until" class="form-control" value="<?= e($defaultUntil) ?>">
        <div class="form-text">Kosongkan untuk memakai masa berlaku default (<?= e(setting('qr_default_days', '30')) ?> hari).</div>
      </div>
    </div>

    <div class="alert alert-info py-2 small mt-3 mb-0">
      <i class="bi bi-info-circle me-1"></i>
      Setelah diterbitkan, <b>QR hanya dapat dilihat satu kali</b> (halaman berikutnya). Simpan/cetak langsung.
      Jika hilang, cabut QR lalu terbitkan yang baru.
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy" <?= !$regList ? 'disabled' : '' ?>>
        <i class="bi bi-qr-code me-1"></i>Terbitkan Sekarang
      </button>
      <a href="<?= e(url('admin/qr/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
