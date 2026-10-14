<?php
/**
 * includes/functions.php
 * Helper universal. Cukup require file ini di setiap halaman:
 *
 *   require_once __DIR__ . '/../includes/functions.php';
 *
 * File ini otomatis memuat config/database.php, auth.php, dan csrf.php.
 */

if (!defined('APP_FUNCTIONS_LOADED')) {
    define('APP_FUNCTIONS_LOADED', true);
    define('HASIL_KUNJUNGAN_MAX', 4000); // batas karakter catatan hasil kunjungan

    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/auth.php';
    require_once __DIR__ . '/csrf.php';

    auth_boot();
}

/* ============================================================
 *  URL, OUTPUT & REDIRECT
 * ============================================================ */

/** Bangun URL relatif terhadap BASE_URL. */
function url(string $path = ''): string
{
    return BASE_URL . '/' . ltrim($path, '/');
}

/** Path asset. */
function asset(string $path): string
{
    return url('assets/' . ltrim($path, '/'));
}

/** Escape output (anti XSS). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Redirect lalu hentikan eksekusi. */
function redirect(string $path): void
{
    if (preg_match('#^https?://#i', $path) || strpos($path, '/') === 0) {
        header('Location: ' . $path);
    } else {
        header('Location: ' . url($path));
    }
    exit;
}

/** Respons JSON (untuk kebutuhan AJAX). */
function json_out(array $data, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* ============================================================
 *  FLASH MESSAGE & TOKEN SEKALI PAKAI
 * ============================================================ */

function flash_set(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

/** Ambil (dan hapus) semua flash message. */
function flash_all(): array
{
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

/** Simpan nilai sekali pakai (mis. token QR hasil penerbitan). */
function flash_token_set(string $key, $value): void
{
    $_SESSION['flash_token'][$key] = $value;
}

function flash_token_get(string $key, $default = null)
{
    $v = $_SESSION['flash_token'][$key] ?? $default;
    unset($_SESSION['flash_token'][$key]);
    return $v;
}

/* ============================================================
 *  PENGAMBILAN INPUT (server-side validation)
 * ============================================================ */

function req_str(string $key, string $default = ''): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function req_int(string $key, int $default = 0): int
{
    return isset($_POST[$key]) ? (int) $_POST[$key] : $default;
}

function get_str(string $key, string $default = ''): string
{
    return isset($_GET[$key]) && is_string($_GET[$key]) ? trim($_GET[$key]) : $default;
}

function get_int(string $key, int $default = 0): int
{
    return isset($_GET[$key]) ? (int) $_GET[$key] : $default;
}

/* ============================================================
 *  FORMAT DATA
 * ============================================================ */

function format_tanggal($dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = is_numeric($dt) ? (int) $dt : strtotime((string) $dt);
    return $ts ? date('d/m/Y', $ts) : '-';
}

function format_waktu($dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = is_numeric($dt) ? (int) $dt : strtotime((string) $dt);
    return $ts ? date('H:i', $ts) : 'WIB';
}

function format_tgl_waktu($dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = is_numeric($dt) ? (int) $dt : strtotime((string) $dt);
    return $ts ? date('d/m/Y H:i', $ts) : '-';
}

function format_tgl_lengkap($dt): string
{
    if (!$dt) {
        return '-';
    }
    $ts = is_numeric($dt) ? (int) $dt : strtotime((string) $dt);
    return $ts ? date('d/m/Y H:i:s', $ts) : '-';
}

/** Format durasi menit -> "2 jam 15 menit". */
function format_durasi(?int $menit): string
{
    if ($menit === null || $menit < 0) {
        return '-';
    }
    $j = intdiv($menit, 60);
    $m = $menit % 60;
    if ($j === 0) {
        return $m . ' menit';
    }
    return $j . ' jam ' . $m . ' menit';
}

/** Durasi berjalan (menit) untuk kunjungan yang masih berlangsung. */
function durasi_berjalan(string $jamMasuk): int
{
    $ts = strtotime($jamMasuk);
    return $ts ? max(0, (int) floor((time() - $ts) / 60)) : 0;
}

/* ============================================================
 *  GENERATOR KODE UNIK
 * ============================================================ */

/** Contoh hasil: KT-251013-A1B2 (prefiks + tanggal + acak), dicek unik di tabel. */
function gen_kode_unique(string $table, string $column, string $prefix): string
{
    for ($i = 0; $i < 12; $i++) {
        $kode = $prefix . '-' . date('ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));
        $stmt = db()->prepare("SELECT COUNT(*) FROM {$table} WHERE {$column} = ?");
        $stmt->execute([$kode]);
        if ((int) $stmt->fetchColumn() === 0) {
            return $kode;
        }
    }
    // Cadangan: gunakan timestamp
    return $prefix . '-' . date('ymdHis');
}

/* ============================================================
 *  TOKEN QR (acak + hash)
 * ============================================================ */

/** Token acak 64 karakter hex (256 bit) - sulit ditebak. */
function random_token(): string
{
    return bin2hex(random_bytes(32));
}

/** Hash SHA-256 token untuk disimpan di database. */
function hash_token(string $token): string
{
    return hash('sha256', $token);
}

/** Hasilkan gambar QR (PNG base64 data URI) memakai chillerlan/php-qrcode. */
function qr_png_datauri(string $text, int $scale = 6): string
{
    require_once APP_ROOT . '/vendor/autoload.php';

    $options = new \chillerlan\QRCode\QROptions([
        'version'        => \chillerlan\QRCode\QRCode::VERSION_AUTO,
        'outputType'     => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG,
        'eccLevel'       => \chillerlan\QRCode\QRCode::ECC_L,
        'scale'          => $scale,
        'imageBase64'    => true,
        'quietzoneSize'  => 2,
    ]);

    $qr = new \chillerlan\QRCode\QRCode($options);
    return $qr->render($text);
}

/* ============================================================
 *  AUDIT LOG
 * ============================================================ */

function log_aktivitas(string $aktivitas, ?PDO $pdo = null): void
{
    try {
        $pdo = $pdo ?: db();
        $stmt = $pdo->prepare(
            'INSERT INTO activity_logs (user_id, username, role, aktivitas, ip_address, user_agent)
             VALUES (?,?,?,?,?,?)'
        );
        $stmt->execute([
            current_user_id(),
            $_SESSION['username'] ?? null,
            $_SESSION['role'] ?? null,
            mb_substr($aktivitas, 0, 255),
            $_SERVER['REMOTE_ADDR'] ?? null,
            mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        ]);
    } catch (Throwable $e) {
        error_log('[LOG] Gagal mencatat aktivitas: ' . $e->getMessage());
    }
}

/* ============================================================
 *  PENGATURAN APLIKASI (tabel settings)
 * ============================================================ */

function setting(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        $cache = [];
        try {
            foreach (db()->query('SELECT key_name, value FROM settings') as $row) {
                $cache[$row['key_name']] = (string) $row['value'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return $cache[$key] ?? $default;
}

function set_setting(string $key, string $value, ?PDO $pdo = null): void
{
    $pdo = $pdo ?: db();
    $sql = (DB_DRIVER === 'pgsql')
        ? 'INSERT INTO settings (key_name, value) VALUES (?,?) ON CONFLICT (key_name) DO UPDATE SET value = EXCLUDED.value'
        : 'INSERT INTO settings (key_name, value) VALUES (?,?) ON DUPLICATE KEY UPDATE value = VALUES(value)';
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$key, $value]);
}

/* ============================================================
 *  NOTIFIKASI
 * ============================================================ */

function notify(?int $userId, string $judul, string $pesan, string $tipe = 'info', ?PDO $pdo = null): void
{
    $pdo = $pdo ?: db();
    $stmt = $pdo->prepare('INSERT INTO notifications (user_id, judul, pesan, tipe) VALUES (?,?,?,?)');
    $stmt->execute([$userId, mb_substr($judul, 0, 150), $pesan, $tipe]);
}

/* ============================================================
 *  UPLOAD FILE (bukti jaminan)
 * ============================================================ */

/**
 * Validasi & simpan upload bukti jaminan.
 * Mengembalikan nama file tersimpan, atau null bila gagal (pesan di $error).
 */
function upload_bukti(array $file, ?string &$error = null): ?string
{
    $error = null;
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null; // tidak ada upload (opsional)
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Upload gagal (kode ' . $file['error'] . ').';
        return null;
    }
    if ($file['size'] > MAX_UPLOAD_MB * 1024 * 1024) {
        $error = 'Ukuran file melebihi ' . MAX_UPLOAD_MB . ' MB.';
        return null;
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    $map   = [
        'image/jpeg' => '.jpg',
        'image/png'  => '.png',
        'application/pdf' => '.pdf',
    ];
    if (!isset($map[$mime])) {
        $error = 'Format file harus JPG, PNG, atau PDF.';
        return null;
    }

    $dir = APP_ROOT . '/uploads/bukti-jaminan';
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }

    $nama = 'bukti-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . $map[$mime];
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $nama)) {
        $error = 'Gagal menyimpan file upload.';
        return null;
    }
    return $nama;
}

/* ============================================================
 *  CSV EXPORT (fputcsv + proteksi formula injection)
 * ============================================================ */

/** Awali sel teks berawalan karakter formula dengan tanda kutip. */
function csv_cell($value): string
{
    $s = trim((string) $value);
    if ($s !== '' && in_array($s[0], ['=', '+', '-', '@'], true)) {
        $s = "'" . $s;
    }
    return $s;
}

/** Kirim file CSV dan hentikan eksekusi. */
function send_csv(string $filename, array $header, array $rows): void
{
    while (ob_get_length() !== false && ob_get_length() > 0) {
        ob_end_clean();
    }
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');

    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF"); // BOM UTF-8 agar terbaca rapi di Excel
    fputcsv($out, $header);
    foreach ($rows as $row) {
        fputcsv($out, array_map('csv_cell', $row));
    }
    fclose($out);
    exit;
}

/* ============================================================
 *  PAGINATION
 * ============================================================ */

function pagination(int $total, int $page, int $perPage, string $baseUrl, array $params = []): string
{
    $pages = (int) max(1, ceil($total / $perPage));
    if ($pages <= 1) {
        return '';
    }
    $page = max(1, min($page, $pages));

    $mk = function (int $p, string $label, bool $disabled = false, bool $active = false) use ($baseUrl, $params, $page) {
        if ($disabled) {
            return '<li class="page-item disabled"><span class="page-link">' . $label . '</span></li>';
        }
        $params['page'] = $p;
        $qs = http_build_query($params);
        $cur = ($p === $page) ? ' active' : '';
        return '<li class="page-item' . $cur . '"><a class="page-link" href="' .
            e($baseUrl . '?' . $qs) . '">' . $label . '</a></li>';
    };

    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0">';
    $html .= $mk(max(1, $page - 1), '&laquo;', $page <= 1);

    $start = max(1, $page - 2);
    $end   = min($pages, $page + 2);
    if ($start > 1) {
        $html .= $mk(1, '1');
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }
    for ($p = $start; $p <= $end; $p++) {
        $html .= $mk($p, (string) $p, false, $p === $page);
    }
    if ($end < $pages) {
        if ($end < $pages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
        $html .= $mk($pages, (string) $pages);
    }

    $html .= $mk(min($pages, $page + 1), '&raquo;', $page >= $pages);
    $html .= '</ul></nav>';
    return $html;
}

/* ============================================================
 *  HASIL KUNJUNGAN
 * ============================================================ */

/** Apakah catatan hasil kunjungan terisi? (null / string kosong = belum ada) */
function hasil_kunjungan_ada($nilai): bool
{
    return trim((string) $nilai) !== '';
}

/**
 * Sel tabel untuk kolom Hasil Kunjungan (daftar kunjungan & riwayat).
 * Teks asli dari database tetap utuh di DOM - pemotongan visual dilakukan CSS
 * (.hasil-clamp), sehingga tidak ada data yang dipotong di sisi server.
 * Catatan kosong ditampilkan sebagai tanda "-"
 *
 * @param mixed  $nilai     visits.hasil_kunjungan
 * @param string $detailUrl opsional: tautan "Lihat Detail" utk catatan panjang
 */
function cell_hasil_kunjungan($nilai, string $detailUrl = ''): string
{
    $teks = (string) $nilai;
    if (trim($teks) === '') {
        return '<span class="text-muted">&mdash;</span>';
    }

    $html = '<span class="hasil-clamp" title="' . e($teks) . '">' . e($teks) . '</span>';
    if ($detailUrl !== '' && mb_strlen(trim($teks)) > 80) {
        $html .= '<br><a href="' . e($detailUrl) . '" class="small text-decoration-none">Lihat Detail</a>';
    }
    return $html;
}

/**
 * Tampilan lengkap hasil kunjungan (halaman detail / cetak).
 * Catatan kosong ditampilkan sebagai teks "Belum ada hasil kunjungan".
 */
function tampil_hasil_kunjungan($nilai): string
{
    if (trim((string) $nilai) === '') {
        return '<span class="text-muted">Belum ada hasil kunjungan</span>';
    }
    return nl2br(e($nilai));
}

/* ============================================================
 *  LAIN-LAIN
 * ============================================================ */

/** Badge status Bootstrap untuk berbagai status di aplikasi. */
function badge_status(string $status): string
{
    $map = [
        'aktif'        => 'success',
        'berlangsung'  => 'primary',
        'di_dalam'     => 'primary',
        'selesai'      => 'secondary',
        'nonaktif'     => 'secondary',
        'dicabut'      => 'danger',
        'dikembalikan' => 'success',
        'kedaluwarsa'  => 'warning',
        'menunggu'     => 'warning',
    ];
    $color = $map[$status] ?? 'secondary';
    return '<span class="badge text-bg-' . $color . '">' . e(ucfirst(str_replace('_', ' ', $status))) . '</span>';
}
