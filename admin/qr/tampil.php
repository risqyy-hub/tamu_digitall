<?php
/**
 * admin/qr/tampil.php
 * Menampilkan gambar QR hasil penerbitan (token mentah hanya tersedia sekali,
 * diambil dari sesi via flash_token - tidak pernah disimpan di database).
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$id    = get_int('id');
$token = flash_token_get('qr_token_' . $id);

$st = db()->prepare(
    "SELECT q.*, v.nama, v.kode_tamu, v.telepon, pc.nama_perusahaan
     FROM visitor_qr_codes q
     JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
     JOIN visitors v ON v.id = rv.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
     WHERE q.id = ?"
);
$st->execute([$id]);
$qr = $st->fetch();

if (!$qr) {
    flash_set('danger', 'QR tidak ditemukan.');
    redirect('admin/qr/index.php');
}

$qrImage = null;
if ($token) {
    $qrImage = qr_png_datauri($token, 6);
}

$page_title = 'Tampil QR';
$active_menu = 'qr';
require __DIR__ . '/../../includes/header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url('admin/qr/index.php')) ?>" class="text-decoration-none">QR Code</a></li>
    <li class="breadcrumb-item active">QR #<?= (int) $qr['id'] ?></li>
  </ol>
</nav>

<div class="row justify-content-center">
  <div class="col-md-7 col-lg-5">
    <div class="panel p-4 text-center">

      <!-- Header laporan cetak (hanya tampil saat print) -->
      <div class="print-only mb-3 text-start">
        <h1 class="h5 fw-bold mb-0"><?= e(setting('nama_instansi', APP_NAME)) ?></h1>
        <p class="small mb-0">QR Code Kunjungan &middot; <?= e(format_tgl_lengkap(date('Y-m-d H:i:s'))) ?></p>
        <hr>
      </div>

      <h2 class="h6 fw-bold text-navy mb-1"><i class="bi bi-qr-code me-2"></i>QR Code Kunjungan</h2>
      <p class="text-muted small">Untuk: <b><?= e($qr['nama']) ?></b> (<?= e($qr['kode_tamu']) ?>)</p>

      <?php if ($qrImage): ?>
        <div class="qr-box my-3">
          <img src="<?= e($qrImage) ?>" alt="QR Code <?= e($qr['nama']) ?>" style="width:240px;height:240px">
        </div>
        <table class="table table-sm small text-start mb-3">
          <tr><th style="width:40%">Token (prefix)</th><td><code><?= e(substr($token, 0, 16)) ?>&hellip;</code></td></tr>
          <tr><th>Instansi</th><td><?= e($qr['nama_perusahaan'] ?: '-') ?></td></tr>
          <tr><th>Berlaku mulai</th><td><?= e(format_tgl_lengkap($qr['valid_from'])) ?></td></tr>
          <tr><th>Berlaku sampai</th><td><?= e($qr['valid_until'] ? format_tgl_lengkap($qr['valid_until']) : 'Tanpa batas') ?></td></tr>
          <tr><th>Status</th><td><?= badge_status($qr['status']) ?></td></tr>
        </table>

        <div class="alert alert-warning py-2 small text-start no-print">
          <i class="bi bi-exclamation-triangle me-1"></i>
          Token hanya ditampilkan <b>sekali ini saja</b> dan tidak dapat dilihat kembali.
          Cetak atau unduh gambar QR sekarang, lalu serahkan kepada tamu.
        </div>

        <div class="d-flex gap-2 justify-content-center no-print">
          <button type="button" class="btn btn-navy" onclick="window.print()">
            <i class="bi bi-printer me-1"></i>Cetak QR
          </button>
          <a href="<?= e($qrImage) ?>" download="qr-<?= e($qr['kode_tamu']) ?>-<?= (int) $qr['id'] ?>.png"
             class="btn btn-outline-navy">
            <i class="bi bi-download me-1"></i>Unduh PNG
          </a>
          <a href="<?= e(url('admin/qr/index.php')) ?>" class="btn btn-outline-secondary">Selesai</a>
        </div>

      <?php else: ?>
        <div class="alert alert-secondary py-3 text-start">
          <h3 class="h6"><i class="bi bi-shield-lock me-1"></i>Gambar QR tidak dapat ditampilkan ulang</h3>
          <p class="small mb-2">
            Token mentah QR ini hanya disimpan sementara di sesi saat penerbitan dan sengaja
            tidak disimpan di database demi keamanan (database hanya menyimpan hash SHA-256).
          </p>
          <p class="small mb-0">
            Solusi: <a href="<?= e(url('admin/qr/index.php')) ?>">cabut QR ini</a> lalu terbitkan QR baru
            untuk tamu yang sama.
          </p>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
