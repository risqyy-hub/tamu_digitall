<?php
/**
 * actions/login_process.php
 * Proses autentikasi admin & petugas.
 */
require_once __DIR__ . '/../includes/functions.php';

require_post();
verify_csrf();

// Sudah login? tidak perlu proses ulang
if (current_user()) {
    redirect('index.php');
}

$username = req_str('username');
$password = isset($_POST['password']) && is_string($_POST['password']) ? $_POST['password'] : '';

if ($username === '' || $password === '') {
    flash_set('warning', 'Username dan password wajib diisi.');
    redirect('login.php');
}

$stmt = db()->prepare('SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1');
$stmt->execute([$username, $username]);
$user = $stmt->fetch() ?: null;

if (!$user || !password_verify($password, $user['password'])) {
    usleep(600000); // perlambat brute-force sederhana
    log_aktivitas('Percobaan login gagal untuk: ' . $username);
    flash_set('danger', 'Username atau password salah.');
    redirect('login.php');
}

if ($user['status'] !== 'aktif') {
    log_aktivitas('Login ditolak (akun nonaktif): ' . $user['username']);
    flash_set('warning', 'Akun Anda telah dinonaktifkan. Hubungi administrator.');
    redirect('login.php');
}

// Regenerasi ID sesi setelah login (anti session fixation)
session_regenerate_id(true);

$_SESSION['user_id']        = (int) $user['id'];
$_SESSION['username']       = $user['username'];
$_SESSION['role']           = $user['role'];
$_SESSION['last_activity']  = time();

log_aktivitas('Login berhasil: ' . $user['username']);
flash_set('success', 'Selamat datang, ' . $user['nama'] . '.');

redirect('index.php');
