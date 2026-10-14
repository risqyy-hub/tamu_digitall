<?php
/**
 * admin/pengaturan.php
 * Konfigurasi aplikasi.
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$page_title = 'Pengaturan';
$active_menu = 'pengaturan';
require __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-3">
  <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-gear me-2"></i>Pengaturan Aplikasi</h1>
  <small class="text-muted">Konfigurasi identitas instansi & layanan buku tamu</small>
</div>

<div class="panel p-3 p-md-4" style="max-width:760px">
  <form method="post" action="<?= e(url('actions/settings_process.php')) ?>" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="simpan">

    <div class="row g-3">
      <div class="col-md-8">
        <label class="form-label small fw-semibold">Nama Instansi <span class="text-danger">*</span></label>
        <input type="text" name="nama_instansi" class="form-control" required maxlength="150"
               value="<?= e(setting('nama_instansi')) ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label small fw-semibold">Kontak Instansi</label>
        <input type="text" name="kontak_instansi" class="form-control" maxlength="50"
               value="<?= e(setting('kontak_instansi')) ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Alamat Instansi</label>
        <textarea name="alamat_instansi" class="form-control" rows="2" maxlength="500"><?= e(setting('alamat_instansi')) ?></textarea>
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Jam Buka</label>
        <input type="time" name="jam_buka" class="form-control" value="<?= e(setting('jam_buka', '08:00')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Jam Tutup</label>
        <input type="time" name="jam_tutup" class="form-control" value="<?= e(setting('jam_tutup', '16:00')) ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label small fw-semibold">Masa Berlaku QR Default (hari)</label>
        <input type="number" name="qr_default_days" class="form-control" min="1" max="3650"
               value="<?= e(setting('qr_default_days', '30')) ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-navy mt-4"><i class="bi bi-check-lg me-1"></i>Simpan Pengaturan</button>
  </form>
</div>

<div class="panel p-3 p-md-4 mt-3" style="max-width:760px">
  <h2 class="h6 fw-bold text-navy mb-2"><i class="bi bi-database me-2"></i>Informasi Sistem</h2>
  <table class="table table-sm small mb-0" style="max-width:520px">
    <tr><th>Driver Database</th><td><?= e(DB_DRIVER === 'pgsql' ? 'PostgreSQL (Supabase)' : 'MySQL / MariaDB') ?></td></tr>
    <tr><th>Nama Database</th><td><?= e(DB_NAME) ?></td></tr>
    <tr><th>Versi PHP</th><td><?= e(PHP_VERSION) ?></td></tr>
    <tr><th>Timezone</th><td><?= e(date_default_timezone_get()) ?></td></tr>
    <tr><th>Waktu Server</th><td><?= e(date('d/m/Y H:i:s')) ?></td></tr>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
