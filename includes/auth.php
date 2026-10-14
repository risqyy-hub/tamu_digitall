<?php
/**
 * includes/auth.php
 * Sesi login, guard role, dan info pengguna aktif.
 */

if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}

const AUTH_IDLE_TIMEOUT = 7200; // sesi kedaluwarsa setelah 2 jam tidak aktif

/** Konfigurasi & memulai sesi (aman untuk HTTPS & localhost). */
function auth_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');

    session_name('BTDSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();

    if (isset($_SESSION['user_id'])) {
        $last = (int) ($_SESSION['last_activity'] ?? 0);
        if ($last > 0 && (time() - $last) > AUTH_IDLE_TIMEOUT) {
            auth_destroy_session();
            return;
        }
        $_SESSION['last_activity'] = time();
    }
}

/** Hancurkan sesi login. */
function auth_destroy_session(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}

/** Data pengguna login (hanya yang berstatus aktif). Di-cache per request. */
function current_user(): ?array
{
    static $cache = false;
    static $user = null;

    if ($cache !== false) {
        return $user;
    }
    $cache = true;

    $id = (int) ($_SESSION['user_id'] ?? 0);
    if ($id <= 0) {
        return null;
    }

    try {
        $stmt = db()->prepare('SELECT id, nama, username, email, telepon, role, foto, status FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch() ?: null;
    } catch (PDOException $e) {
        $user = null;
    }

    // Akun dinonaktifkan -> paksa keluar
    if ($user && $user['status'] !== 'aktif') {
        auth_destroy_session();
        $user = null;
    }

    return $user;
}

function current_user_id(): ?int
{
    $u = current_user();
    return $u ? (int) $u['id'] : null;
}

function current_role(): ?string
{
    $u = current_user();
    return $u ? $u['role'] : null;
}

function is_admin(): bool
{
    return current_role() === 'admin';
}

/** Wajib login; selanjutnya dialihkan ke halaman login. */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['ok' => false, 'error' => 'Sesi berakhir, silakan login ulang.']);
            exit;
        }
        flash_set('warning', 'Silakan login terlebih dahulu.');
        redirect('login.php');
    }
    return $user;
}

/**
 * Guard role:
 *  - require_role('admin')   -> hanya admin
 *  - require_role('petugas') -> admin ATAU petugas
 */
function require_role(string $role): array
{
    $user = require_login();

    $ok = ($role === 'admin')
        ? ($user['role'] === 'admin')
        : in_array($user['role'], ['admin', 'petugas'], true);

    if (!$ok) {
        http_response_code(403);
        echo '<!doctype html><html lang="id"><head><meta charset="utf-8"><title>Akses Ditolak</title>
              <style>body{font-family:Segoe UI,sans-serif;background:#f5f7fb;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0}
              .box{background:#fff;padding:2rem 2.5rem;border-radius:12px;box-shadow:0 6px 24px rgba(11,42,91,.08);text-align:center;max-width:420px}
              h1{color:#b02a37;font-size:1.2rem}.btn{display:inline-block;margin-top:1rem;padding:.5rem 1.2rem;background:#0b2a5b;color:#fff;text-decoration:none;border-radius:8px}</style>
              </head><body><div class="box"><h1>403 - Akses Ditolak</h1>
              <p>Maaf, halaman ini khusus pengguna dengan hak akses <b>' . htmlspecialchars($role, ENT_QUOTES, 'UTF-8') . '</b>.</p>
              <a class="btn" href="' . htmlspecialchars(url('index.php'), ENT_QUOTES, 'UTF-8') . '">Kembali ke Dashboard</a></div></body></html>';
        exit;
    }

    return $user;
}
