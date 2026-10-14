<?php
/**
 * petugas/qr/scanner.php
 * Scanner QR Check-in: kamera perangkat (jsQR) atau input token manual.
 * Setelah token terbaca, diarahkan ke form tambah kunjungan:
 *   {area}/kunjungan/tambah.php?token=TOKEN
 *
 * Izin kamera: hanya diizinkan browser pada konteks aman
 * (https:// atau http://localhost).
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$area = is_admin() ? 'admin' : 'petugas';

$page_title = 'Scan QR';
$active_menu = 'scanner';
require __DIR__ . '/../../includes/header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-qr-code-scan me-2"></i>Scanner QR Code</h1>
    <small class="text-muted">Pindai QR tamu terdaftar untuk check-in cepat</small>
  </div>
  <a href="<?= e(url($area . '/kunjungan/tambah.php')) ?>" class="btn btn-outline-navy btn-sm">
    <i class="bi bi-pencil me-1"></i>Check-in Manual
  </a>
</div>

<div class="row g-3">
  <div class="col-lg-6">
    <div class="panel p-3 p-md-4 text-center">
      <div class="scanner-frame position-relative d-inline-block">
        <video id="scanVideo" class="scanner-video" playsinline muted></video>
        <canvas id="scanCanvas" class="d-none"></canvas>
      </div>

      <div id="scanStatus" class="alert py-2 small mt-3 mb-0 alert-secondary">
        <i class="bi bi-camera-video me-1"></i>Tekan <b>Mulai Kamera</b> untuk memulai pemindaian.
      </div>

      <div class="d-flex gap-2 justify-content-center mt-3">
        <button id="scanStart" class="btn btn-navy" type="button">
          <i class="bi bi-camera-video me-1"></i>Mulai Kamera
        </button>
        <button id="scanStop" class="btn btn-outline-secondary" type="button" disabled>
          <i class="bi bi-stop-circle me-1"></i>Stop
        </button>
      </div>

      <p class="text-muted small mt-3 mb-0">
        <i class="bi bi-shield-lock me-1"></i>
        Token hasil pindai langsung dikirim ke server untuk divalidasi
        (status, masa berlaku, registrasi tamu) sebelum identitas tampil.
      </p>
    </div>
  </div>

  <div class="col-lg-6">
    <div class="panel p-3 p-md-4">
      <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-keyboard me-2"></i>Input Token Manual</h2>
      <p class="small text-muted">
        Jika kamera tidak tersedia, minta tamu menampilkan token (teks) pada QR-nya,
        lalu masukkan di sini.
      </p>
      <form method="get" action="<?= e(url($area . '/kunjungan/tambah.php')) ?>" class="row g-2">
        <div class="col-8">
          <input type="text" name="token" class="form-control" placeholder="Token QR (64 karakter hex)"
                 required minlength="16" maxlength="128">
        </div>
        <div class="col-4 d-grid">
          <button class="btn btn-navy" type="submit"><i class="bi bi-arrow-right-circle me-1"></i>Proses</button>
        </div>
      </form>

      <hr>

      <h2 class="h6 fw-bold text-navy mb-2"><i class="bi bi-info-circle me-2"></i>Catatan Teknis</h2>
      <ul class="small text-muted mb-0">
        <li>Izin kamera diminta browser saat tombol <b>Mulai Kamera</b> ditekan.</li>
        <li>Akses kamera hanya diizinkan pada <b>https://</b> atau <b>http://localhost</b>.</li>
        <li>Gunakan browser Chrome/Edge terbaru untuk hasil terbaik.</li>
        <li>QR kedaluwarsa, dicabut, atau milik registrasi nonaktif akan <b>ditolak server</b>.</li>
      </ul>
    </div>
  </div>
</div>

<script src="<?= e(asset('vendor/jsqr/jsQR.js')) ?>"></script>
<script src="<?= e(asset('js/scanner.js')) ?>"></script>
<script>
initQrScanner({
  onResult: function (text) {
    var area = <?= json_encode($area) ?>;
    // Jika QR berisi URL lengkap, langsung buka; jika tidak, perlakukan sebagai token
    var target;
    if (/^https?:\/\//i.test(text)) {
      target = text;
    } else {
      target = area + '/kunjungan/tambah.php?token=' + encodeURIComponent(text.trim());
    }
    window.location.href = target;
  }
});
</script>

<?php require __DIR__ . '/../../includes/footer.php'; ?>
