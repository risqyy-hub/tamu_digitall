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
// Petugas: menyorot submenu "Tambah Kunjungan" di sidebar; admin tetap pada "Semua Kunjungan".
$active_menu = ($AREA === 'petugas') ? 'tambah' : 'kunjungan';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Kunjungan</a></li>
    <li class="breadcrumb-item active">Tambah Kunjungan</li>
  </ol>
</nav>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-door-open me-2"></i>Tambah Kunjungan</h1>
    <small class="text-muted">Catat kedatangan tamu dan lengkapi informasi kunjungan sebelum check-in.</small>
  </div>
  <a href="<?= e(url($base . '/index.php')) ?>" class="btn btn-outline-navy btn-sm">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

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

<div class="panel checkin-card">
  <div class="checkin-card-header p-3 p-md-4">
    <span class="checkin-head-icon"><i class="bi bi-clipboard-check"></i></span>
    <div>
      <h2 class="h6 fw-bold mb-0">Form Check-in Kunjungan</h2>
      <p class="mb-0">Lengkapi identitas tamu dan detail kunjungan di bawah ini.</p>
    </div>
  </div>

  <div class="p-3 p-md-4">
  <form method="post" action="<?= e(url('actions/kunjungan_process.php')) ?>" id="formCheckin" novalidate>
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="checkin">
    <?php if ($qrValid): ?>
      <input type="hidden" name="mode" value="qr">
      <input type="hidden" name="token" value="<?= e($token) ?>">
    <?php endif; ?>

    <div class="row g-4">
      <div class="col-lg-8">

    <!-- ================= Identitas Tamu ================= -->
    <div class="checkin-step">
      <span class="step-badge">1</span>
      <div>
        <h3 class="checkin-step-title"><i class="bi bi-person me-1"></i>Identitas Tamu</h3>
        <p class="checkin-step-desc mb-0">
          <?= $qrValid
              ? 'Identitas terverifikasi otomatis dari QR Code yang dipindai.'
              : 'Pilih tamu terdaftar dari database, atau daftarkan tamu baru (walk-in).' ?>
        </p>
      </div>
    </div>

    <?php if ($qrValid): ?>
      <div class="row g-3 mb-1">
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Nama Tamu</label>
          <input type="text" class="form-control" value="<?= e($qrValid['nama']) ?>" readonly data-summary="tamu">
        </div>
        <div class="col-md-6">
          <label class="form-label small fw-semibold">Instansi / Perusahaan</label>
          <input type="text" class="form-control"
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
      <ul class="nav nav-pills checkin-tabs mb-3" role="tablist">
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
    <div class="checkin-step checkin-step-divider">
      <span class="step-badge">2</span>
      <div>
        <h3 class="checkin-step-title"><i class="bi bi-card-checklist me-1"></i>Detail Kunjungan</h3>
        <p class="checkin-step-desc mb-0">Lengkapi tujuan, pegawai yang ditemui, dan informasi pendukung kunjungan.</p>
      </div>
    </div>
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
        <textarea name="keperluan" class="form-control" rows="3" maxlength="1000"
                  placeholder="Uraian singkat keperluan kunjungan"><?= e($_POST['keperluan'] ?? '') ?></textarea>
      </div>
      <div class="col-12">
        <label class="form-label small fw-semibold">Hasil Kunjungan (opsional)</label>
        <textarea name="hasil_kunjungan" class="form-control" rows="3" maxlength="<?= (int) HASIL_KUNJUNGAN_MAX ?>"
                  placeholder="Contoh: Perbaikan belum selesai karena alat belum lengkap, teknisi akan kembali besok."><?= e($_POST['hasil_kunjungan'] ?? '') ?></textarea>
        <div class="form-text">
          <i class="bi bi-info-circle me-1"></i>Catatan hasil pertemuan atau pekerjaan yang dilakukan.
          Boleh dikosongkan saat check-in dan dapat dilengkapi kapan saja melalui halaman detail kunjungan.
        </div>
      </div>
      <?php if (!$qrValid && $mitraList): ?>
        <div class="col-12">
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

      </div><!-- /.col-lg-8 -->

      <div class="col-lg-4">
        <aside class="checkin-summary" aria-label="Ringkasan check-in">
          <div class="checkin-summary-head"><i class="bi bi-clipboard-check"></i>Ringkasan Check-in</div>
          <ul class="checkin-summary-list">
            <li>
              <span class="cs-label">Tamu</span>
              <span class="cs-value is-empty" id="sumTamu">Belum dipilih</span>
            </li>
            <li>
              <span class="cs-label">Tujuan</span>
              <span class="cs-value is-empty" id="sumTujuan">Belum diisi</span>
            </li>
            <li>
              <span class="cs-label">Pegawai Ditemui</span>
              <span class="cs-value is-empty" id="sumPegawai">Belum diisi</span>
            </li>
          </ul>
          <div class="checkin-summary-foot">
            <i class="bi bi-shield-check me-1"></i>Data akan diverifikasi ulang oleh server saat disimpan.
          </div>
        </aside>
      </div>
    </div><!-- /.row -->

    <div class="checkin-actions">
      <div class="d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-navy">
          <i class="bi bi-door-open me-1"></i>Check-in Sekarang
        </button>
        <a href="<?= e(url($base . '/index.php')) ?>" class="btn btn-cancel">
          <i class="bi bi-x-lg me-1"></i>Batal
        </a>
      </div>
      <span class="checkin-note">
        <i class="bi bi-clock me-1"></i>Waktu masuk diambil dari waktu server saat disimpan.
      </span>
    </div>
  </form>
  </div><!-- /.checkin-card-body -->
</div><!-- /.checkin-card -->

<script>
/* Panel ringkasan: hanya membaca nilai form yang sudah ada (tanpa logika bisnis). */
(function () {
  var form = document.getElementById('formCheckin');
  if (!form) return;
  var outN = document.getElementById('sumTamu');
  var outT = document.getElementById('sumTujuan');
  var outP = document.getElementById('sumPegawai');
  if (!outN || !outT || !outP) return;

  function visible(el) {
    return !!(el && (el.offsetWidth || el.offsetHeight || el.getClientRects().length));
  }
  function set(el, val, emptyText) {
    var v = (val || '').replace(/\s+/g, ' ').trim();
    el.textContent = v || emptyText;
    if (v) { el.classList.remove('is-empty'); } else { el.classList.add('is-empty'); }
  }
  function refresh() {
    try {
      var nama = '';
      var sel = form.querySelector('[name="visitor_id"]');
      if (sel && visible(sel) && sel.value !== '' && sel.selectedIndex > -1) {
        nama = sel.options[sel.selectedIndex].text;
      }
      if (!nama) {
        var baru = form.querySelector('[name="nama_baru"]');
        if (baru && visible(baru)) nama = baru.value;
      }
      if (!nama) {
        var qr = form.querySelector('[data-summary="tamu"]');
        if (qr) nama = qr.value;
      }
      set(outN, nama, 'Belum dipilih');
      var t = form.querySelector('[name="tujuan"]');
      set(outT, t ? t.value : '', 'Belum diisi');
      var p = form.querySelector('[name="pegawai_ditemui"]');
      set(outP, p ? p.value : '', 'Belum diisi');
    } catch (e) { /* ringkasan bersifat pelengkap, tidak boleh mengganggu form */ }
  }
  form.addEventListener('input', refresh);
  form.addEventListener('change', refresh);
  refresh();
})();
</script>

<?php require __DIR__ . '/../../footer.php'; ?>
