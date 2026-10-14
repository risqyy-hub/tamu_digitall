<?php
/**
 * includes/csrf.php
 * Token CSRF per sesi untuk semua form & aksi POST.
 */

if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

const CSRF_FIELD = 'csrf_token';

/** Ambil (atau buat) token CSRF sesi berjalan. */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/** Field input tersembunyi untuk form. */
function csrf_field(): string
{
    return '<input type="hidden" name="' . CSRF_FIELD . '" value="' . e(csrf_token()) . '">';
}

/** Tolak request bila token tidak cocok. */
function verify_csrf(): void
{
    $sent = $_POST[CSRF_FIELD] ?? '';
    if (!is_string($sent) || $sent === '' || !hash_equals(csrf_token(), $sent)) {
        http_response_code(403);
        exit('403 - Token keamanan (CSRF) tidak valid. Silakan kembali ke halaman sebelumnya dan coba lagi.');
    }
}

/** Tolak request yang bukan metode POST. */
function require_post(): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('405 - Metode tidak diizinkan. Gunakan POST.');
    }
}
