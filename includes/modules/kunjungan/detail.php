<?php
/**
 * includes/modules/kunjungan/detail.php
 * Detail kunjungan + jaminan + aksi check-out.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base = $AREA . '/kunjungan';
$id   = get_int('id');

$st = db()->prepare(
    "SELECT vi.*,
            v.nama AS nama_tamu, v.kode_tamu, v.jenis_identitas, v.no_identitas, v.telepon, v.alamat,
            pc.nama_perusahaan,
            u1.nama AS petugas_masuk, u2.nama AS petugas_keluar,
            q.token_hash AS qr_hash
     FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     LEFT JOIN users u1 ON u1.id = vi.petugas_masuk_id
     LEFT JOIN users u2 ON u2.id = vi.petugas_keluar_id
     LEFT JOIN visitor_qr_codes q ON q.id = vi.qr_id
     WHERE vi.id = ?"
);
$st->execute([$id]);
$visit = $st->fetch();

if (!$visit) {
    flash_set('danger', 'Kunjungan tidak ditemukan.');
    redirect($base . '/index.php');
}

$st = db()->prepare(
    "SELECT g.*, u1.nama AS diterima_nama, u2.nama AS dikembalikan_nama
     FROM guarantees g
     LEFT JOIN users u1 ON u1.id = g.diterima_oleh
     LEFT JOIN users u2 ON u2.id = g.dikembalikan_oleh
     WHERE g.visit_id = ? ORDER BY g.created_at ASC"
);
$st->execute([$id]);
$jaminan = $st->fetchAll();

$aktifCount = 0;
foreach ($jaminan as $g) {
    if ($g['status'] === 'aktif') {
        $aktifCount++;
    }
}

$durasi = $visit['durasi_menit'] !== null
    ? (int) $visit['durasi_menit']
    : durasi_berjalan($visit['jam_masuk']);

$page_title = 'Detail Kunjungan';
$active_menu = 'kunjungan';
require __DIR__ . '/../../header.php';
?>

<nav aria-label="breadcrumb" class="mb-3">
  <ol class="breadcrumb small mb-0">
    <li class="breadcrumb-item"><a href="<?= e(url($base . '/index.php')) ?>" class="text-decoration-none">Kunjungan</a></li>
    <li class="breadcrumb-item active"><?= e($visit['kode_kunjungan']) ?></li>
  </ol>
</nav>

<div class="row g-3">
  <!-- ============ Info kunjungan ============ -->
  <div class="col-lg-5">
    <div class="panel p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-card-list me-2"></i>Info Kunjungan</h2>
        <?= badge_status($visit['status']) ?>
      </div>
      <table class="table table-sm small mb-0">
        <tr><th style="width:42%">No. Kunjungan</th><td class="fw-semibold"><?= e($visit['kode_kunjungan']) ?></td></tr>
        <tr><th>Tamu</th><td><?= e($visit['nama_tamu']) ?> (<?= e($visit['kode_tamu']) ?>)</td></tr>
        <tr><th>Identitas</th><td><?= e($visit['jenis_identitas']) ?><?= $visit['no_identitas'] ? ' &middot; ' . e($visit['no_identitas']) : '' ?></td></tr>
        <tr><th>Telepon</th><td><?= e($visit['telepon'] ?: '-') ?></td></tr>
        <tr><th>Instansi</th><td><?= e($visit['nama_perusahaan'] ?: ($visit['instansi'] ?: '-')) ?></td></tr>
        <tr><th>QR Digunakan</th>
          <td>
            <?php if ($visit['qr_id']): ?>
              <i class="bi bi-qr-code text-navy"></i> Ya &middot;
              <code class="small"><?= e(substr($visit['qr_hash'], 0, 12)) ?>&hellip;</code>
            <?php else: ?>
              Tidak (input manual)
            <?php endif; ?>
          </td>
        </tr>
        <tr><th>Tujuan</th><td><?= e($visit['tujuan']) ?></td></tr>
        <tr><th>Hasil Kunjungan</th><td><?= tampil_hasil_kunjungan($visit['hasil_kunjungan'] ?? null) ?></td></tr>
        <tr><th>Pegawai Ditemui</th><td><?= e($visit['pegawai_ditemui']) ?></td></tr>
        <tr><th>Keperluan</th><td><?= e($visit['keperluan'] ?: '-') ?></td></tr>
        <tr><th>Jam Masuk</th><td><?= e(format_tgl_lengkap($visit['jam_masuk'])) ?></td></tr>
        <tr><th>Jam Keluar</th><td><?= e(format_tgl_lengkap($visit['jam_keluar'])) ?></td></tr>
        <tr><th>Durasi</th>
          <td <?= $visit['status'] === 'berlangsung' ? 'data-durasi-start="' . e(date('c', strtotime($visit['jam_masuk']))) . '"' : '' ?>>
            <?= e(format_durasi($durasi)) ?>
          </td>
        </tr>
        <tr><th>Petugas Masuk</th><td><?= e($visit['petugas_masuk'] ?: '-') ?></td></tr>
        <tr><th>Petugas Keluar</th><td><?= e($visit['petugas_keluar'] ?: '-') ?></td></tr>
      </table>
    </div>
  </div>

  <!-- ============ Jaminan & check-out ============ -->
  <div class="col-lg-7">
    <!-- Hasil kunjungan (diisi / diperbarui oleh petugas yang berwenang) -->
    <div class="panel p-3">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <h2 class="h6 fw-bold text-navy mb-0"><i class="bi bi-journal-text me-2"></i>Hasil Kunjungan</h2>
        <?php if (!hasil_kunjungan_ada($visit['hasil_kunjungan'] ?? null)): ?>
          <span class="badge text-bg-light border small">Belum ada hasil</span>
        <?php endif; ?>
      </div>
      <form method="post" action="<?= e(url('actions/kunjungan_process.php')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="simpan_hasil">
        <input type="hidden" name="id" value="<?= (int) $visit['id'] ?>">
        <label class="form-label small fw-semibold" for="hasilKunjungan">Catatan Hasil</label>
        <textarea name="hasil_kunjungan" id="hasilKunjungan" class="form-control" rows="4"
                  maxlength="<?= (int) HASIL_KUNJUNGAN_MAX ?>"
                  placeholder="Contoh: Perbaikan belum selesai karena alat belum lengkap, teknisi akan kembali besok."><?= e($visit['hasil_kunjungan'] ?? '') ?></textarea>
        <div class="form-text">
          Catat hasil pertemuan atau pekerjaan yang dilakukan. Bersifat opsional &mdash;
          kunjungan yang masih berlangsung boleh belum memiliki hasil.
        </div>
        <button type="submit" class="btn btn-navy btn-sm mt-2">
          <i class="bi bi-save me-1"></i>Simpan Hasil
        </button>
      </form>
    </div>

    <div class="panel mt-3">
      <div class="panel-header p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
        <h2 class="h6 fw-bold text-navy mb-0">
          <i class="bi bi-box-seam me-2"></i>Jaminan (<?= count($jaminan) ?>)
        </h2>
        <?php if ($visit['status'] === 'berlangsung'): ?>
          <a href="<?= e(url($AREA . '/jaminan/tambah.php?visit_id=' . $id)) ?>" class="btn btn-outline-navy btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Tambah Jaminan
          </a>
        <?php endif; ?>
      </div>

      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead>
            <tr>
              <th>Barang</th><th class="text-center">Jml</th><th>Status</th>
              <th>Diterima</th><th>Dikembalikan</th><th class="text-end no-print"></th>
            </tr>
          </thead>
          <tbody>
          <?php if (!$jaminan): ?>
            <tr><td colspan="6">
              <div class="empty-state py-3"><i class="bi bi-box"></i>Tidak ada jaminan pada kunjungan ini.</div>
            </td></tr>
          <?php endif; ?>
          <?php foreach ($jaminan as $g): ?>
            <tr>
              <td>
                <span class="fw-semibold"><?= e($g['barang']) ?></span>
                <?php if ($g['bukti_file']): ?>
                  <a href="<?= e(asset('uploads/bukti-jaminan/' . $g['bukti_file'])) ?>" target="_blank" class="ms-1 small">
                    <i class="bi bi-paperclip"></i> bukti
                  </a>
                <?php endif; ?>
                <?php if ($g['keterangan']): ?>
                  <br><span class="text-muted small"><?= e($g['keterangan']) ?></span>
                <?php endif; ?>
              </td>
              <td class="text-center"><?= (int) $g['jumlah'] ?></td>
              <td><?= badge_status($g['status']) ?></td>
              <td class="small"><?= e(format_tgl_waktu($g['diterima_at'])) ?><br><span class="text-muted"><?= e($g['diterima_nama'] ?: '-') ?></span></td>
              <td class="small"><?= e(format_tgl_waktu($g['dikembalikan_at'])) ?><br><span class="text-muted"><?= e($g['dikembalikan_nama'] ?: '-') ?></span></td>
              <td class="text-end no-print">
                <?php if ($g['status'] === 'aktif' && $visit['status'] === 'berlangsung'): ?>
                  <form method="post" action="<?= e(url('actions/jaminan_process.php')) ?>" class="d-inline"
                        data-confirm="Kembalikan jaminan <?= e($g['barang']) ?> kepada tamu?">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="kembalikan">
                    <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                    <button class="btn btn-success btn-sm" type="submit">
                      <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
                    </button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Aksi check-out -->
    <div class="panel p-3 mt-3">
      <h2 class="h6 fw-bold text-navy mb-2"><i class="bi bi-box-arrow-right me-2"></i>Check-out</h2>

      <?php if ($visit['status'] === 'selesai'): ?>
        <div class="alert alert-secondary py-2 mb-0 small">
          <i class="bi bi-check-circle me-1"></i>
          Kunjungan ini sudah selesai pada <?= e(format_tgl_lengkap($visit['jam_keluar'])) ?>
          (durasi <?= e(format_durasi((int) $visit['durasi_menit'])) ?>).
        </div>
      <?php else: ?>
        <?php if ($aktifCount > 0): ?>
          <div class="alert alert-warning py-2 small">
            <i class="bi bi-exclamation-triangle me-1"></i>
            <b>Check-out belum dapat dilakukan.</b> Masih ada <b><?= $aktifCount ?> jaminan</b>
            yang belum dikembalikan. Kembalikan seluruh jaminan terlebih dahulu.
          </div>
        <?php endif; ?>
        <form method="post" action="<?= e(url('actions/checkout_process.php')) ?>"
              <?= $aktifCount > 0 ? 'data-confirm="Masih ada jaminan aktif. Check-out akan ditolak server. Lanjutkan?"' : 'data-confirm="Selesaikan kunjungan ' . e($visit['kode_kunjungan']) . ' sekarang?"' ?>>
          <?= csrf_field() ?>
          <input type="hidden" name="id" value="<?= (int) $visit['id'] ?>">
          <div class="mb-3">
            <label class="form-label small fw-semibold" for="hasilCheckout">Hasil Kunjungan (opsional)</label>
            <textarea name="hasil_kunjungan" id="hasilCheckout" class="form-control" rows="3"
                      maxlength="<?= (int) HASIL_KUNJUNGAN_MAX ?>"
                      placeholder="Contoh: Pembahasan selesai dan dokumen penawaran diminta untuk dilengkapi."><?= e($visit['hasil_kunjungan'] ?? '') ?></textarea>
            <div class="form-text">
              Disimpan bersama check-out. Boleh dikosongkan &mdash; catatan yang sudah ada
              tidak akan berubah. Untuk menyimpan tanpa check-out, gunakan panel
              <b>Hasil Kunjungan</b> di atas.
            </div>
          </div>
          <button type="submit" class="btn btn-navy" <?= $aktifCount > 0 ? 'disabled' : '' ?>>
            <i class="bi bi-box-arrow-right me-1"></i>Check-out Sekarang
          </button>
          <?php if ($aktifCount > 0): ?>
            <span class="small text-muted ms-2">Tombol dinonaktifkan - server tetap memvalidasi saat dikirim.</span>
          <?php endif; ?>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
