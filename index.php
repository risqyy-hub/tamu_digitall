<?php
/**
 * index.php
 * Entry point - dialihkan sesuai role pengguna.
 */
require_once __DIR__ . '/includes/functions.php';

require_login();

if (is_admin()) {
    redirect('admin/dashboard.php');
}
redirect('petugas/dashboard.php');
