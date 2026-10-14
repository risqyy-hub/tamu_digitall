<?php
/**
 * includes/modules/kunjungan/aktif.php
 * Monitoring kunjungan yang sedang berlangsung.
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

require_role('petugas');

$base = $AREA . '/kunjungan';

$st = db()->query(
    "SELECT vi.*, v.nama AS nama_tamu, v.kode_tamu, v.telepon,
            pc.nama_perusahaan, u.nama AS petugas_masuk,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id AND g.status = 'aktif') AS jaminan_aktif,
            (SELECT COUNT(*) FROM guarantees g WHERE g.visit_id = vi.id) AS jaminan_total
     FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     LEFT JOIN users u ON u.id = vi.petugas_masuk_id
     WHERE vi.status = 'berlangsung'
     ORDER BY vi.jam_masuk ASC"
);
$rows = $st->fetchAll();
$totalAktif = count($rows);

$page_title = 'Kunjungan Aktif';
$active_menu = 'aktif';
require __DIR__ . '/../../header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-hourglass-split me-2"></i>Kunjungan Aktif</h1>
    <small class="text-muted"><?= $totalAktif ?> tamu sedang berada di kantor</small>
  </div>
  <div class="d-flex gap-2">
    <a href="<?= e(url($base . '/tambah.php')) ?>" class="btn btn-navy btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Tambah Kunjungan
    </a>
  </div>
</div>

<div class="panel">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead>
        <tr>
          <th>No. Kunjungan</th>
          <th>Tamu</th>
          <th>Instansi</th>
          <th>Tujuan / Pegawai</th>
          <th>Jam Masuk</th>
          <th class="text-center">Durasi</th>
          <th class="text-center">Jaminan</th>
          <th class="text-end no-print">Aksi</th>
        </tr>
      </thead>
      <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="8">
          <div class="empty-state">
            <i class="bi bi-check2-circle"></i>Tidak ada kunjungan aktif saat ini.
          </div>
        </td></tr>
      <?php endif; ?>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="small"><?= e($r['kode_kunjungan']) ?></td>
          <td>
            <span class="fw-semibold"><?= e($r['nama_tamu']) ?></span><br>
            <span class="text-muted small"><?= e($r['telepon'] ?: $r['kode_tamu']) ?></span>
          </td>
          <td class="small"><?= e($r['nama_perusahaan'] ?: ($r['instansi'] ?: '-')) ?></td>
          <td class="small"><?= e($r['tujuan']) ?><br><span class="text-muted">&rarr; <?= e($r['pegawai_ditemui']) ?></span></td>
          <td class="small"><?= e(format_waktu($r['jam_masuk'])) ?></td>
          <td class="text-center small" data-durasi-start="<?= e(date('c', strtotime($r['jam_masuk']))) ?>">
            <?= e(format_durasi(durasi_berjalan($r['jam_masuk']))) ?>
          </td>
          <td class="text-center">
            <?php if ($r['jaminan_total'] == 0): ?>
              <span class="badge text-bg-light text-muted">-</span>
            <?php elseif ($r['jaminan_aktif'] > 0): ?>
              <span class="badge text-bg-warning"><?= (int) $r['jaminan_aktif'] ?> aktif</span>
            <?php else: ?>
              <span class="badge text-bg-success"><i class="bi bi-check-lg"></i> lunas</span>
            <?php endif; ?>
          </td>
          <td class="text-end no-print">
            <a href="<?= e(url($base . '/detail.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-navy btn-sm" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <form method="post" action="<?= e(url('actions/checkout_process.php')) ?>" class="d-inline"
                  data-confirm="Check-out kunjungan <?= e($r['kode_kunjungan']) ?> (<?= e($r['nama_tamu']) ?>)?">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn <?= $r['jaminan_aktif'] > 0 ? 'btn-outline-secondary' : 'btn-navy' ?> btn-sm"
                      type="submit" <?= $r['jaminan_aktif'] > 0 ? 'disabled title="Masih ada jaminan aktif"' : '' ?>>
                <i class="bi bi-box-arrow-right me-1"></i>Check-out
              </button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
