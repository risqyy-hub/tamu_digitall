<?php
/**
 * logout.php
 * Proses keluar - hanya menerima POST + token CSRF.
 */
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    log_aktivitas('Logout: ' . ($_SESSION['username'] ?? '-'));
}

auth_destroy_session();

// Sesi baru kecil agar flash message bisa tampil di halaman login
session_name('BTDSID');
session_start();
flash_set('success', 'Anda telah keluar dari aplikasi.');

redirect('login.php');
