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
            pc.nama_perusahaan, u.nama AS petugas_masuk
     FROM visits vi
     JOIN visitors v ON v.id = vi.visitor_id
     LEFT JOIN partner_companies pc ON pc.id = vi.mitra_id
     LEFT JOIN users u ON u.id = vi.petugas_masuk_id
     WHERE vi.status = 'berlangsung'
     ORDER BY vi.jam_masuk ASC"
);
$rows = $st->fetchAll();
$totalAktif = count($rows);

/* Detail jaminan per kunjungan (dikelompokkan per visit_id agar tidak tertukar). */
$jaminanPerVisit = [];
$stJml = db()->query(
    "SELECT g.id, g.visit_id, g.barang, g.jumlah, g.status, g.keterangan,
            g.diterima_at, g.diterima_oleh, g.dikembalikan_at, g.dikembalikan_oleh
     FROM guarantees g
     JOIN visits vi ON vi.id = g.visit_id
     WHERE vi.status = 'berlangsung'
     ORDER BY g.created_at ASC, g.id ASC"
);
foreach ($stJml as $g) {
    $jaminanPerVisit[(int) $g['visit_id']][] = $g;
}

$jaminanAktifTotal = 0;
foreach ($jaminanPerVisit as $list) {
    foreach ($list as $g) {
        if ($g['status'] === 'aktif') {
            $jaminanAktifTotal++;
        }
    }
}

$page_title = 'Kunjungan Aktif';
$active_menu = 'aktif';
require __DIR__ . '/../../header.php';
?>

<div class="page-header d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
  <div>
    <h1 class="h5 fw-bold text-navy mb-0"><i class="bi bi-hourglass-split me-2"></i>Kunjungan Aktif</h1>
    <small class="text-muted">
      <?= $totalAktif ?> tamu sedang berada di kantor
      &middot;
      <span class="<?= $jaminanAktifTotal > 0 ? 'text-warning fw-semibold' : '' ?>">
        <?= $jaminanAktifTotal ?> jaminan belum dikembalikan
      </span>
    </small>
  </div>
  <div class="d-flex gap-2 flex-wrap">
    <a href="<?= e(url($AREA . '/jaminan/index.php')) ?>" class="btn btn-outline-navy btn-sm"
       title="Riwayat & status seluruh jaminan">
      <i class="bi bi-box-seam me-1"></i>Semua Jaminan
    </a>
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
      <?php foreach ($rows as $r):
          $gList   = $jaminanPerVisit[(int) $r['id']] ?? [];
          $gTotal  = count($gList);
          $gAktif  = 0;
          $gBarang = [];
          foreach ($gList as $g) {
              if ($g['status'] === 'aktif') {
                  $gAktif++;
              }
              $gBarang[] = $g['barang'] . ' (' . (int) $g['jumlah'] . ')';
          }
      ?>
        <tr>
          <td class="small"><?= e($r['kode_kunjungan']) ?></td>
          <td>
            <span class="fw-semibold"><?= e($r['nama_tamu']) ?></span><br>
            <span class="text-muted small"><?= e($r['telepon'] ?: $r['kode_tamu']) ?></span>
          </td>
          <td class="small"><?= e($r['nama_perusahaan'] ?: ($r['instansi'] ?: '-')) ?></td>
          <td class="small">
            <?= e($r['tujuan']) ?><br><span class="text-muted">&rarr; <?= e($r['pegawai_ditemui']) ?></span>
            <?php if (hasil_kunjungan_ada($r['hasil_kunjungan'] ?? null)): ?>
              <br><span class="text-muted"><i class="bi bi-journal-text"></i>
                <span class="hasil-clamp" title="<?= e($r['hasil_kunjungan']) ?>"><?= e($r['hasil_kunjungan']) ?></span>
              </span>
            <?php endif; ?>
          </td>
          <td class="small"><?= e(format_waktu($r['jam_masuk'])) ?></td>
          <td class="text-center small" data-durasi-start="<?= e(date('c', strtotime($r['jam_masuk']))) ?>">
            <?= e(format_durasi(durasi_berjalan($r['jam_masuk']))) ?>
          </td>
          <td class="text-center">
            <?php if ($gTotal === 0): ?>
              <span class="badge text-bg-light text-muted">-</span>
            <?php else: ?>
              <button class="btn btn-outline-warning btn-sm d-inline-flex align-items-center gap-1 text-nowrap"
                      type="button" data-bs-toggle="collapse"
                      data-bs-target="#jaminan-<?= (int) $r['id'] ?>"
                      aria-expanded="false" aria-controls="jaminan-<?= (int) $r['id'] ?>"
                      title="Lihat detail jaminan kunjungan ini">
                <i class="bi bi-box-seam"></i><?= $gTotal ?> barang
                <?php if ($gAktif > 0): ?>
                  <span class="badge text-bg-warning"><?= $gAktif ?> aktif</span>
                <?php else: ?>
                  <span class="badge text-bg-success">lunas</span>
                <?php endif; ?>
              </button>
              <div class="small text-muted text-truncate" style="max-width:180px; margin-inline:auto"
                   title="<?= e(implode(', ', $gBarang)) ?>">
                <?= e(implode(', ', $gBarang)) ?>
              </div>
            <?php endif; ?>
          </td>
          <td class="text-end no-print text-nowrap">
            <a href="<?= e(url($base . '/detail.php?id=' . (int) $r['id'])) ?>"
               class="btn btn-outline-navy btn-sm btn-icon" title="Detail">
              <i class="bi bi-eye"></i>
            </a>
            <form method="post" action="<?= e(url('actions/checkout_process.php')) ?>" class="d-inline"
                  data-confirm="Check-out kunjungan <?= e($r['kode_kunjungan']) ?> (<?= e($r['nama_tamu']) ?>)?">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
              <button class="btn <?= $gAktif > 0 ? 'btn-outline-secondary' : 'btn-navy' ?> btn-sm"
                      type="submit" <?= $gAktif > 0 ? 'disabled title="Masih ada jaminan aktif"' : '' ?>>
                <i class="bi bi-box-arrow-right me-1"></i>Check-out
              </button>
            </form>
          </td>
        </tr>

        <!-- Detail jaminan kunjungan terkait (tersembunyi sampai tombol "jaminan" ditekan) -->
        <?php if ($gTotal > 0): ?>
        <tr class="jaminan-detail collapse" id="jaminan-<?= (int) $r['id'] ?>">
          <td colspan="8" class="bg-light">
            <div class="p-3">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-2">
                <h3 class="h6 fw-bold text-navy mb-0">
                  <i class="bi bi-box-seam me-2"></i>Jaminan <?= e($r['kode_kunjungan']) ?>
                  <span class="text-muted fw-normal small">
                    (<?= $gTotal ?> barang &middot; <?= $gAktif ?> belum dikembalikan)
                  </span>
                </h3>
                <div class="d-flex gap-2">
                  <a href="<?= e(url($AREA . '/jaminan/tambah.php?visit_id=' . (int) $r['id'])) ?>"
                     class="btn btn-outline-navy btn-sm">
                    <i class="bi bi-plus-lg me-1"></i>Tambah Jaminan
                  </a>
                  <a href="<?= e(url($base . '/detail.php?id=' . (int) $r['id'])) ?>"
                     class="btn btn-outline-navy btn-sm">
                    <i class="bi bi-eye me-1"></i>Detail Kunjungan
                  </a>
                </div>
              </div>

              <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Barang</th>
                      <th class="text-center" style="width:70px">Jml</th>
                      <th>Status</th>
                      <th>Diterima</th>
                      <th>Dikembalikan</th>
                      <th class="text-end no-print" style="width:150px">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($gList as $g): ?>
                      <tr>
                        <td>
                          <span class="fw-semibold"><?= e($g['barang']) ?></span>
                          <?php if ($g['keterangan']): ?>
                            <br><span class="text-muted small"><?= e(mb_substr($g['keterangan'], 0, 80)) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="text-center"><?= (int) $g['jumlah'] ?></td>
                        <td><?= badge_status($g['status']) ?></td>
                        <td class="small"><?= e(format_tgl_waktu($g['diterima_at'])) ?></td>
                        <td class="small"><?= e(format_tgl_waktu($g['dikembalikan_at'])) ?></td>
                        <td class="text-end no-print">
                          <?php if ($g['status'] === 'aktif'): ?>
                            <form method="post" action="<?= e(url('actions/jaminan_process.php')) ?>" class="d-inline"
                                  data-confirm="Kembalikan jaminan <?= e($g['barang']) ?> kepada tamu <?= e($r['nama_tamu']) ?>?">
                              <?= csrf_field() ?>
                              <input type="hidden" name="action" value="kembalikan">
                              <input type="hidden" name="id" value="<?= (int) $g['id'] ?>">
                              <button class="btn btn-success btn-sm" type="submit">
                                <i class="bi bi-arrow-counterclockwise me-1"></i>Kembalikan
                              </button>
                            </form>
                          <?php else: ?>
                            <span class="text-muted small">
                              <i class="bi bi-check2-circle"></i> Selesai
                            </span>
                          <?php endif; ?>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>

              <?php if ($gAktif > 0): ?>
                <div class="alert alert-warning py-2 small mb-0 mt-2">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Check-out baru dapat dilakukan setelah seluruh jaminan dikembalikan.
                </div>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <?php endif; ?>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require __DIR__ . '/../../footer.php'; ?>
