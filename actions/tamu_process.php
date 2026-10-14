<?php
/**
 * actions/tamu_process.php
 * CRUD data tamu (tambah / ubah / hapus).
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('petugas');
require_post();
verify_csrf();

$area  = is_admin() ? 'admin' : 'petugas';
$base  = $area . '/tamu';
$action = req_str('action');
$pdo   = db();

/** Batasi parameter kembali agar tidak menjadi open redirect. */
function safe_return_path(string $path, string $fallback): string
{
    if ($path !== '' && preg_match('#^(admin|petugas)/[a-zA-Z0-9_\-/\.]+\??[a-zA-Z0-9_\-=&]*$#', $path)) {
        return $path;
    }
    return $fallback;
}

$JENIS = ['KTP', 'SIM', 'Paspor', 'Kartu Pegawai', 'Lainnya'];

/* ================= TAMBAH ================= */
if ($action === 'tambah') {
    $nama     = req_str('nama');
    $jenis    = req_str('jenis_identitas', 'KTP');
    $noId     = req_str('no_identitas');
    $telepon  = req_str('telepon');
    $email    = req_str('email');
    $alamat   = req_str('alamat');
    $return   = safe_return_path(req_str('return'), $base . '/index.php');

    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama tamu wajib diisi minimal 3 karakter.');
        redirect($base . '/tambah.php');
    }
    if (!in_array($jenis, $JENIS, true)) {
        $jenis = 'Lainnya';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect($base . '/tambah.php');
    }

    $kode = gen_kode_unique('visitors', 'kode_tamu', 'TM');
    $st = $pdo->prepare(
        'INSERT INTO visitors (kode_tamu, nama, jenis_identitas, no_identitas, telepon, email, alamat)
         VALUES (?,?,?,?,?,?,?)'
    );
    $st->execute([$kode, $nama, $jenis, $noId ?: null, $telepon ?: null, $email ?: null, $alamat ?: null]);
    $id = (int) $pdo->lastInsertId();

    log_aktivitas('Menambahkan tamu ' . $nama . ' (' . $kode . ')');
    flash_set('success', 'Tamu "' . $nama . '" berhasil ditambahkan dengan kode ' . $kode . '.');
    redirect($return);
}

/* ================= UBAH ================= */
if ($action === 'edit') {
    $id      = req_int('id');
    $nama    = req_str('nama');
    $jenis   = req_str('jenis_identitas', 'KTP');
    $noId    = req_str('no_identitas');
    $telepon = req_str('telepon');
    $email   = req_str('email');
    $alamat  = req_str('alamat');

    $st = $pdo->prepare('SELECT * FROM visitors WHERE id = ?');
    $st->execute([$id]);
    $tamu = $st->fetch();

    if (!$tamu) {
        flash_set('danger', 'Data tamu tidak ditemukan.');
        redirect($base . '/index.php');
    }
    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama tamu wajib diisi minimal 3 karakter.');
        redirect($base . '/edit.php?id=' . $id);
    }
    if (!in_array($jenis, $JENIS, true)) {
        $jenis = 'Lainnya';
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect($base . '/edit.php?id=' . $id);
    }

    $st = $pdo->prepare(
        'UPDATE visitors SET nama = ?, jenis_identitas = ?, no_identitas = ?, telepon = ?, email = ?, alamat = ?
         WHERE id = ?'
    );
    $st->execute([$nama, $jenis, $noId ?: null, $telepon ?: null, $email ?: null, $alamat ?: null, $id]);

    log_aktivitas('Mengubah data tamu ' . $tamu['nama'] . ' (' . $tamu['kode_tamu'] . ')');
    flash_set('success', 'Data tamu berhasil diperbarui.');
    redirect($base . '/detail.php?id=' . $id);
}

/* ================= HAPUS (admin) ================= */
if ($action === 'hapus') {
    if (!is_admin()) {
        flash_set('danger', 'Hanya admin yang dapat menghapus data tamu.');
        redirect($base . '/index.php');
    }
    $id = req_int('id');

    $st = $pdo->prepare('SELECT * FROM visitors WHERE id = ?');
    $st->execute([$id]);
    $tamu = $st->fetch();

    if (!$tamu) {
        flash_set('warning', 'Data tamu tidak ditemukan.');
        redirect($base . '/index.php');
    }

    $st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE visitor_id = ?');
    $st->execute([$id]);
    $jumlahKunjungan = (int) $st->fetchColumn();

    if ($jumlahKunjungan > 0) {
        flash_set('warning', 'Tamu "' . $tamu['nama'] . '" tidak dapat dihapus karena memiliki '
            . $jumlahKunjungan . ' riwayat kunjungan.');
        redirect($base . '/detail.php?id=' . $id);
    }

    // Hapus registrasi & QR terkait (otomatis via FK CASCADE), lalu tamunya
    $st = $pdo->prepare('DELETE FROM visitors WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus tamu ' . $tamu['nama'] . ' (' . $tamu['kode_tamu'] . ')');
    flash_set('success', 'Data tamu "' . $tamu['nama'] . '" dihapus.');
    redirect($base . '/index.php');
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($base . '/index.php');
