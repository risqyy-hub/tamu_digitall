<?php
/**
 * actions/mitra_process.php
 * CRUD perusahaan mitra (admin).
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');
require_post();
verify_csrf();

$action = req_str('action');
$pdo    = db();
$back   = 'admin/mitra/index.php';

/* ================= TAMBAH ================= */
if ($action === 'tambah') {
    $nama = req_str('nama_perusahaan');
    $alamat = req_str('alamat');
    $telepon = req_str('telepon');
    $email = req_str('email');
    $picNama = req_str('pic_nama');
    $picTelp = req_str('pic_telepon');
    $status = req_str('status', 'aktif');

    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama perusahaan wajib diisi minimal 3 karakter.');
        redirect('admin/mitra/tambah.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect('admin/mitra/tambah.php');
    }
    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $status = 'aktif';
    }

    $kode = gen_kode_unique('partner_companies', 'kode_mitra', 'MT');
    $st = $pdo->prepare(
        'INSERT INTO partner_companies (kode_mitra, nama_perusahaan, alamat, telepon, email, pic_nama, pic_telepon, status)
         VALUES (?,?,?,?,?,?,?,?)'
    );
    $st->execute([$kode, $nama, $alamat ?: null, $telepon ?: null, $email ?: null, $picNama ?: null, $picTelp ?: null, $status]);
    $id = (int) $pdo->lastInsertId();

    log_aktivitas('Menambahkan mitra ' . $nama . ' (' . $kode . ')');
    flash_set('success', 'Perusahaan mitra "' . $nama . '" berhasil ditambahkan.');
    redirect($back);
}

/* ================= UBAH ================= */
if ($action === 'edit') {
    $id = req_int('id');
    $nama = req_str('nama_perusahaan');
    $alamat = req_str('alamat');
    $telepon = req_str('telepon');
    $email = req_str('email');
    $picNama = req_str('pic_nama');
    $picTelp = req_str('pic_telepon');
    $status = req_str('status', 'aktif');

    $st = $pdo->prepare('SELECT * FROM partner_companies WHERE id = ?');
    $st->execute([$id]);
    $mitra = $st->fetch();

    if (!$mitra) {
        flash_set('danger', 'Data mitra tidak ditemukan.');
        redirect($back);
    }
    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama perusahaan wajib diisi minimal 3 karakter.');
        redirect('admin/mitra/edit.php?id=' . $id);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect('admin/mitra/edit.php?id=' . $id);
    }
    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $status = 'aktif';
    }

    $st = $pdo->prepare(
        'UPDATE partner_companies
         SET nama_perusahaan = ?, alamat = ?, telepon = ?, email = ?, pic_nama = ?, pic_telepon = ?, status = ?
         WHERE id = ?'
    );
    $st->execute([$nama, $alamat ?: null, $telepon ?: null, $email ?: null, $picNama ?: null, $picTelp ?: null, $status, $id]);

    log_aktivitas('Mengubah data mitra ' . $mitra['nama_perusahaan']);
    flash_set('success', 'Data mitra berhasil diperbarui.');
    redirect('admin/mitra/detail.php?id=' . $id);
}

/* ================= HAPUS ================= */
if ($action === 'hapus') {
    $id = req_int('id');

    $st = $pdo->prepare('SELECT * FROM partner_companies WHERE id = ?');
    $st->execute([$id]);
    $mitra = $st->fetch();

    if (!$mitra) {
        flash_set('warning', 'Data mitra tidak ditemukan.');
        redirect($back);
    }

    $st = $pdo->prepare('SELECT COUNT(*) FROM visits WHERE mitra_id = ?');
    $st->execute([$id]);
    $jumlahKunjungan = (int) $st->fetchColumn();

    if ($jumlahKunjungan > 0) {
        flash_set('warning', 'Mitra "' . $mitra['nama_perusahaan'] . '" tidak dapat dihapus karena direferensikan '
            . $jumlahKunjungan . ' kunjungan. Ubah status menjadi nonaktif saja.');
        redirect('admin/mitra/detail.php?id=' . $id);
    }

    $st = $pdo->prepare('DELETE FROM partner_companies WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus mitra ' . $mitra['nama_perusahaan']);
    flash_set('success', 'Perusahaan mitra "' . $mitra['nama_perusahaan'] . '" dihapus.');
    redirect($back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
