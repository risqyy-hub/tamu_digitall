<?php
/**
 * actions/user_process.php
 * Kelola akun petugas/admin (admin only).
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');
require_post();
verify_csrf();

$action = req_str('action');
$pdo    = db();
$back   = 'admin/users/index.php';

/* ================= TAMBAH ================= */
if ($action === 'tambah') {
    $nama     = req_str('nama');
    $username = req_str('username');
    $email    = req_str('email');
    $telepon  = req_str('telepon');
    $role     = req_str('role', 'petugas');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama wajib diisi minimal 3 karakter.');
        redirect('admin/users/tambah.php');
    }
    if (!preg_match('/^[a-zA-Z0-9._]{3,50}$/', $username)) {
        flash_set('warning', 'Username 3-50 karakter, hanya huruf, angka, titik, dan underscore.');
        redirect('admin/users/tambah.php');
    }
    if (strlen($password) < 8) {
        flash_set('warning', 'Password wajib diisi minimal 8 karakter.');
        redirect('admin/users/tambah.php');
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect('admin/users/tambah.php');
    }
    if (!in_array($role, ['admin', 'petugas'], true)) {
        $role = 'petugas';
    }

    $st = $pdo->prepare('SELECT id FROM users WHERE username = ?');
    $st->execute([$username]);
    if ($st->fetch()) {
        flash_set('warning', 'Username "' . $username . '" sudah terpakai.');
        redirect('admin/users/tambah.php');
    }
    if ($email !== '') {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        if ($st->fetch()) {
            flash_set('warning', 'Email "' . $email . '" sudah terpakai.');
            redirect('admin/users/tambah.php');
        }
    }

    $st = $pdo->prepare(
        "INSERT INTO users (nama, username, email, telepon, password, role, status) VALUES (?,?,?,?,?,?,'aktif')"
    );
    $st->execute([$nama, $username, $email ?: null, $telepon ?: null, password_hash($password, PASSWORD_DEFAULT), $role]);
    $id = (int) $pdo->lastInsertId();

    log_aktivitas('Menambahkan akun ' . $username . ' (role: ' . $role . ')');
    flash_set('success', 'Akun "' . $username . '" berhasil ditambahkan.');
    redirect($back);
}

/* ================= UBAH ================= */
if ($action === 'edit') {
    $id       = req_int('id');
    $nama     = req_str('nama');
    $email    = req_str('email');
    $telepon  = req_str('telepon');
    $role     = req_str('role', 'petugas');
    $status   = req_str('status', 'aktif');
    $password = isset($_POST['password']) && is_string($_POST['password']) ? trim($_POST['password']) : '';

    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();

    if (!$user) {
        flash_set('danger', 'Akun tidak ditemukan.');
        redirect($back);
    }
    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama wajib diisi minimal 3 karakter.');
        redirect('admin/users/edit.php?id=' . $id);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect('admin/users/edit.php?id=' . $id);
    }
    if (!in_array($role, ['admin', 'petugas'], true)) {
        $role = 'petugas';
    }
    if (!in_array($status, ['aktif', 'nonaktif'], true)) {
        $status = 'aktif';
    }
    // Cegah admin menonaktifkan / menurunkan role akunnya sendiri
    if ($id === current_user_id()) {
        $status = 'aktif';
        $role   = $user['role'];
        if ($password !== '') {
            flash_set('warning', 'Password diri sendiri diganti melalui menu Profil.');
            redirect('admin/users/edit.php?id=' . $id);
        }
    }
    if ($email !== '' && $email !== $user['email']) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $st->execute([$email, $id]);
        if ($st->fetch()) {
            flash_set('warning', 'Email sudah dipakai akun lain.');
            redirect('admin/users/edit.php?id=' . $id);
        }
    }
    if ($password !== '' && strlen($password) < 8) {
        flash_set('warning', 'Password baru minimal 8 karakter.');
        redirect('admin/users/edit.php?id=' . $id);
    }

    if ($password !== '') {
        $st = $pdo->prepare('UPDATE users SET nama=?, email=?, telepon=?, role=?, status=?, password=? WHERE id=?');
        $st->execute([$nama, $email ?: null, $telepon ?: null, $role, $status, password_hash($password, PASSWORD_DEFAULT), $id]);
    } else {
        $st = $pdo->prepare('UPDATE users SET nama=?, email=?, telepon=?, role=?, status=? WHERE id=?');
        $st->execute([$nama, $email ?: null, $telepon ?: null, $role, $status, $id]);
    }

    log_aktivitas('Mengubah akun ' . $user['username']);
    flash_set('success', 'Akun "' . $user['username'] . '" berhasil diperbarui.');
    redirect($back);
}

/* ================= HAPUS ================= */
if ($action === 'hapus') {
    $id = req_int('id');

    if ($id === current_user_id()) {
        flash_set('warning', 'Anda tidak dapat menghapus akun sendiri.');
        redirect($back);
    }

    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$id]);
    $user = $st->fetch();

    if (!$user) {
        flash_set('warning', 'Akun tidak ditemukan.');
        redirect($back);
    }

    // Kunjungan yang pernah dicatat tetap tersimpan (petugas -> NULL, nama tersimpan di log)
    $st = $pdo->prepare('DELETE FROM users WHERE id = ?');
    $st->execute([$id]);

    log_aktivitas('Menghapus akun ' . $user['username']);
    flash_set('success', 'Akun "' . $user['username'] . '" dihapus. Riwayat kunjungan tetap tersimpan.');
    redirect($back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
