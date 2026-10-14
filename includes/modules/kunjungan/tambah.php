<?php
/**
 * includes/modules/kunjungan/tambah.php
 * Form check-in kunjungan.
 *  - Alur QR : ?token=TOKEN -> validasi server -> identitas terisi otomatis (readonly).
 *  - Manual  : pilih tamu terdaftar ATAU isi tamu baru (walk-in).
 * Identitas TIDAK pernah dipercaya dari browser; backend selalu mengambil ulang dari DB.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base   = $AREA . '/kunjungan';
$token  = get_str('token');
$prefId = get_int('visitor_id');

/* ---------- Validasi token QR di sisi server ---------- */
$qrError = null;
$qrValid = null;

if ($token !== '') {
    $st = db()->prepare(
        "SELECT q.id AS qr_id, q.status AS qr_status, q.valid_from, q.valid_until,
                rv.id AS reg_id, rv.status AS reg_status, rv.mitra_id,
                v.id AS visitor_id, v.nama, v.jenis_identitas, v.no_identitas, v.telepon,
                pc.nama_perusahaan, pc.status AS mitra_status
         FROM visitor_qr_codes q
         JOIN registered_visitors rv ON rv.id = q.registered_visitor_id
         JOIN visitors v ON v.id = rv.visitor_id
         LEFT JOIN partner_companies pc ON pc.id = rv.mitra_id
         WHERE q.token_hash = ?"
    );
    $st->execute([hash_token($token)]);
    $qr = $st->fetch();

    if (!$qr) {
        $qrError = 'QR tidak dikenali atau tidak terdaftar pada sistem.';
    } elseif ($qr['qr_status'] === 'dicabut') {
        $qrError = 'QR ini sudah dicabut oleh administrator dan tidak dapat digunakan.';
    } elseif ($qr['valid_from'] !== null && strtotime($qr['valid_from']) > time()) {
        $qrError = 'QR ini belum berlaku. Masa berlaku mulai ' . format_tgl_waktu($qr['valid_from']) . '.';
    } elseif ($qr['valid_until'] !== null && strtotime($qr['valid_until']) < time()) {
        $qrError = 'QR ini sudah kedaluwarsa (berlaku s.d. ' . format_tgl_waktu($qr['valid_until']) . ').';
    } elseif ($qr['reg_status'] !== 'aktif') {
        $qrError = 'Registrasi tamu terkait QR ini sudah tidak aktif.';
    } elseif ($qr['mitra_id'] && $qr['mitra_status'] === 'nonaktif') {
        $qrError = 'Perusahaan mitra terkait QR ini berstatus nonaktif.';
    } else {
        $qrValid = $qr;
    }
}

/* ---------- Data form ---------- */
$visitorList = db()->query(
    'SELECT id, kode_tamu, nama, no_identitas FROM visitors ORDER BY created_at DESC LIMIT 200'
)->fetchAll();

$mitraList = db()->query(
    "SELECT id, nama_perusahaan FROM partner_companies WHERE status = 'aktif' ORDER BY nama_perusahaan"
)->fetchAll();

$tujuanSaran   = ['Bidang Umum', 'Bidang Keuangan', 'Bidang SDM', 'Bidang Teknologi Informasi', 'Direksi', 'Lainnya'];
$pegawaiSaran  = [];

$page_title = 'Tambah Kunjungan';
$active_menu = 'kunjungan';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Kunjungan</a></li>
    <li class="breadcrumb-item active">Tambah Kunjungan</li>
  </ol>
</nav>

<?php if ($token !== '' && $qrError): ?>
  <div class="alert alert-danger">
    <h2 class="h6 alert-heading"><i class="bi bi-exclamation-triangle me-1"></i>QR Tidak Dapat Digunakan</h2>
    <p class="mb-2"><?= e($qrError) ?></p>
    <div class="d-flex gap-2">
      <a href="<?= e(url($base . '/tambah.php')) ?>" class="btn btn-sm btn-outline-danger">
        <i class="bi bi-pencil me-1"></i>Lanjutkan dengan Pendaftaran Manual
      </a>
      <?php if ($AREA === 'petugas'): ?>
        <a href="<?= e(url('petugas/qr/scanner.php')) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-qr-code-scan me-1"></i>Scan Ulang
        </a>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<?php if ($qrValid): ?>
  <div class="alert alert-success py-2">
    <i class="bi bi-qr-code me-1"></i>
    <b>Check-in via QR Code.</b> Identitas tamu terverifikasi dari database:
    <b><?= e($qrValid['nama']) ?></b><?= $qrValid['nama_perusahaan'] ? ' &middot; ' . e($qrValid['nama_perusahaan']) : '' ?>
  </div>
<?php endif; ?>

<div class="panel p-3 p-md-4">
  <h2 class="h6 fw-bold text-navy mb-3"><i class="bi bi-door-open me-2"></i>Form Check-in Kunjungan</h2>

  <form method="post" action="<?= e(url('actions/kunjungan_process.php')) ?>" id="formCheckin" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="checkin">
    <?php if ($qrValid): ?>
      <input type="hidden" name="mode" value="qr">
      <input type="hidden" name="token" value="<?= e($token) ?>">
    <?php endif; ?>

    <!-- ================= Identitas Tamu ================= -->
    <h3 class="h6 border-bottom pb-2 mb-3">1. Identitas Tamu</h3>

    <?php if ($qrValid): ?>
      <div class="row g-3 mb-1">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Tamu</label>
          <input type="text" class="form-control bg-light" value="<?= e($qrValid['nama']) ?>" readonly>
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Instansi / Perusahaan</label>
          <input type="text" class="form-control bg-light"
                 value="<?= e($qrValid['nama_perusahaan'] ?: '-') ?>" readonly>
        </div>
        <div class="col-12">
          <span class="small text-muted">
            <i class="bi bi-info-circle me-1"></i>
            Identitas diambil dari database berdasarkan token QR. Silakan cocokkan dengan identitas fisik tamu.
          </span>
        </div>
      </div>

    <?php else: ?>
      <ul class="nav nav-pills mb-3" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active btn-sm" data-bs-toggle="pill" data-bs-target="#tabExisting"
                  type="button" role="tab">
            <i class="bi bi-search me-1"></i>Tamu Terdaftar / Data Ada
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link btn-sm" data-bs-toggle="pill" data-bs-target="#tabBaru"
                  type="button" role="tab">
            <i class="bi bi-person-plus me-1"></i>Tamu Baru (Walk-in)
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <div class="tab-pane fade show active" id="tabExisting" role="tabpanel">
          <label class="form-label small fw-semibold">Pilih Tamu</label>
          <select name="visitor_id" class="form-select" id="selectVisitor">
            <option value="">-- Pilih tamu dari data --</option>
            <?php foreach ($visitorList as $v): ?>
              <option value="<?= (int) $v['id'] ?>" data-instansi=""
                      <?= $prefId === (int) $v['id'] ? 'selected' : '' ?>>
                <?= e($v['nama']) ?> (<?= e($v['kode_tamu']) ?><?= $v['no_identitas'] ? ' - ' . e($v['no_identitas']) : '' ?>)
              </option>
            <?php endforeach; ?>
          </select>
          <div class="form-text">
            Tamu belum terdaftar? Buka tab <b>Tamu Baru</b> atau
            <a href="<?= e(url($AREA . '/tamu/tambah.php?return=' . urlencode($base . '/tambah.php'))) ?>">daftarkan di menu Data Tamu</a>.
          </div>
        </div>

        <div class="tab-pane fade" id="tabBaru" role="tabpanel">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
              <input type="text" name="nama_baru" class="form-control" maxlength="100"
                     placeholder="Nama sesuai identitas">
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">Jenis Identitas</label>
              <select name="jenis_identitas" class="form-select">
                <?php foreach (['KTP', 'SIM', 'Paspor', 'Kartu Pegawai', 'Lainnya'] as $j): ?>
                  <option value="<?= e($j) ?>"><?= e($j) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label small fw-semibold">No. Identitas</label>
              <input type="text" name="no_identitas_baru" class="form-control" maxlength="50" placeholder="NIK / no. SIM">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">No. Telepon</label>
              <input type="text" name="telepon_baru" class="form-control" maxlength="20" placeholder="08xxxxxxxxxx">
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Instansi (opsional)</label>
              <input type="text" name="instansi_baru" class="form-control" maxlength="150" placeholder="Nama instansi / perusahaan">
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ================= Detail Kunjungan ================= -->
    <h3 class="h6 border-bottom pb-2 mb-3 mt-4">2. Detail Kunjungan</h3>
    <div class="row g-3">
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Tujuan Kunjungan <span class="text-danger">*</span></label>
        <input type="text" name="tujuan" class="form-control" list="tujuanList" required maxlength="200"
               placeholder="mis. Bidang Keuangan" value="<?= e($_POST['tujuan'] ?? '') ?>">
        <datalist id="tujuanList">
          <?php foreach ($tujuanSaran as $t): ?><option value="<?= e($t) ?>"><?php endforeach; ?>
        </datalist>
      </div>
      <div class="col-md-6">
        <label class="form-label small fw-semibold">Pegawai yang Ditemui <span class="text-danger">*</span></label>
        <input type="text" name="pegawai_ditemui" class="form-control" required maxlength="120"
               placeholder="Nama pegawai yang dikunjungi"
               value="<?= e($_POST['pegawai_ditemui'] ?? '') ?>">
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Keperluan (opsional)</label>
        <textarea name="keperluan" class="form-control" rows="2" maxlength="1000"
                  placeholder="Uraian singkat keperluan kunjungan"><?= e($_POST['keperluan'] ?? '') ?></textarea>
      </div>
      <?php if (!$qrValid && $mitraList): ?>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Perusahaan Mitra (opsional)</label>
          <select name="mitra_id" class="form-select">
            <option value="">-- Tanpa mitra --</option>
            <?php foreach ($mitraList as $m): ?>
              <option value="<?= (int) $m['id'] ?>"><?= e($m['nama_perusahaan']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endif; ?>
    </div>

    <div class="mt-4 d-flex gap-2">
      <button type="submit" class="btn btn-navy px-4">
        <i class="bi bi-box-arrow-in-right me-1"></i>Check-in Sekarang
      </button>
      <a href="<?= e(url($base . '/index.php')) ?>" class="btn btn-outline-secondary">Batal</a>
      <span class="align-self-center small text-muted d-none d-md-inline">
        <i class="bi bi-clock me-1"></i>Waktu masuk diambil dari waktu server saat disimpan.
      </span>
    </div>
  </form>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
