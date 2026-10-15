<?php
/**
 * config/database.php
 * Koneksi PDO (MySQL lokal / Supabase PostgreSQL) + konstanta aplikasi.
 * Nilai dibaca dari file .env di root project (lihat .env.example).
 */

/* ------------------------------------------------------------
 *  Loader .env sederhana (tanpa library eksternal)
 * ------------------------------------------------------------ */
$envFile = dirname(__DIR__) . '/.env';
if (is_file($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        $pos = strpos($line, '=');
        if ($pos === false) {
            continue;
        }
        $key = trim(substr($line, 0, $pos));
        $val = trim(substr($line, $pos + 1));
        // buang tanda kutip ganda/satu
        if ($val !== '' && ($val[0] === '"' || $val[0] === "'") && substr($val, -1) === $val[0]) {
            $val = substr($val, 1, -1);
        }
        // Selalu timpa nilai yang sudah ada: .env adalah sumber utama konfigurasi,
        // sehingga perubahan file .env langsung terbaca pada request berikutnya
        // (termasuk pada worker PHP yang prosesnya berjalan terus / persistent).
        putenv($key . '=' . $val);
        $_ENV[$key] = $val;
    }
}

function env(string $key, string $default = ''): string
{
    $v = getenv($key);
    return ($v === false || $v === '') ? $default : $v;
}

/* ------------------------------------------------------------
 *  Konfigurasi Database (semua kredensial dibaca dari file .env)
 *  Variabel utama : DB_HOST, DB_PORT, DB_DATABASE, DB_USERNAME,
 *                   DB_PASSWORD, DB_CHARSET (opsional)
 *  Nama lama      : DB_NAME, DB_USER, DB_PASS masih didukung sebagai cadangan
 *                   agar konfigurasi .env lama tetap terbaca.
 * ------------------------------------------------------------ */
define('DB_DRIVER', env('DB_DRIVER', 'mysql'));                 // mysql | pgsql
define('DB_HOST',   env('DB_HOST', '127.0.0.1'));
define('DB_PORT',   env('DB_PORT', DB_DRIVER === 'pgsql' ? '5432' : '3306'));
define('DB_NAME',   env('DB_DATABASE', env('DB_NAME', DB_DRIVER === 'pgsql' ? 'postgres' : 'buku_tamu_digital')));
define('DB_USER',   env('DB_USERNAME', env('DB_USER', DB_DRIVER === 'pgsql' ? 'postgres' : 'root')));
define('DB_PASS',   env('DB_PASSWORD', env('DB_PASS', '')));
define('DB_SSLMODE', env('DB_SSLMODE', DB_DRIVER === 'pgsql' ? 'require' : ''));

// Charset hanya dipakai bila bentuknya aman (huruf/angka/underscore).
$charset = env('DB_CHARSET', 'utf8mb4');
if (!preg_match('/^[A-Za-z0-9_]+$/', $charset)) {
    $charset = 'utf8mb4';
}
define('DB_CHARSET', $charset);

/* ------------------------------------------------------------
 *  Konfigurasi Aplikasi
 * ------------------------------------------------------------ */
define('APP_NAME',  'Buku Tamu Digital');
define('APP_ROOT',  realpath(dirname(__DIR__)));
define('APP_DEBUG', env('APP_DEBUG', '0') === '1');
define('MAX_UPLOAD_MB', 2);

date_default_timezone_set(env('APP_TIMEZONE', 'Asia/Jakarta'));

// APP_URL dikosongkan -> deteksi otomatis dari nama folder project di htdocs.
// Contoh: folder "buku_tamu_digital" -> BASE_URL = "/buku_tamu_digital"
$appUrl = rtrim(env('APP_URL', ''), '/');
if ($appUrl !== '') {
    define('BASE_URL', $appUrl);
} elseif (PHP_SAPI !== 'cli' && !empty($_SERVER['SCRIPT_NAME'])) {
    define('BASE_URL', '/' . basename(APP_ROOT));
} else {
    define('BASE_URL', '');
}

error_reporting(E_ALL);
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');

/* ------------------------------------------------------------
 *  Koneksi PDO (singleton)
 * ------------------------------------------------------------ */
function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    if (DB_DRIVER === 'pgsql') {
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;sslmode=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_SSLMODE !== '' ? DB_SSLMODE : 'require'
        );
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=%s',
            DB_HOST,
            DB_PORT,
            DB_NAME,
            DB_CHARSET
        );
    }

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        if (DB_DRIVER === 'pgsql') {
            $pdo->exec("SET TIME ZONE 'Asia/Jakarta'");
        } else {
            // Samakan zona waktu server MySQL dengan PHP (Asia/Jakarta)
            $pdo->exec("SET time_zone = '+07:00'");
        }
    } catch (PDOException $e) {
        // Jangan bocorkan kredensial ke pengguna biasa
        error_log('[DB] Koneksi gagal: ' . $e->getMessage());
        http_response_code(500);
        $detail = APP_DEBUG
            ? '<pre style="white-space:pre-wrap">' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8') . '</pre>'
            : '<p>Periksa kembali file <code>.env</code> (salin dari <code>.env.example</code>).</p>';
        echo '<!doctype html><meta charset="utf-8"><title>Koneksi Database Gagal</title>
              <div style="max-width:640px;margin:60px auto;font-family:Segoe UI,sans-serif">
              <h2 style="color:#0b2a5b">Koneksi database gagal</h2>' . $detail . '
              <ul>
                <li>Pastikan layanan <b>MySQL</b> XAMPP sudah berjalan (untuk driver mysql).</li>
                <li>Pastikan database <code>' . htmlspecialchars(DB_NAME, ENT_QUOTES, 'UTF-8') . '</code> sudah dibuat (impor <code>database.sql</code>).</li>
                <li>Untuk Supabase: isi <code>DB_DRIVER=pgsql</code> beserta host, port, nama database, user, dan password di <code>.env</code>.</li>
              </ul></div>';
        exit;
    }

    return $pdo;
}
