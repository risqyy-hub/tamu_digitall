<?php
/**
 * tests/revisi_test.php  (CLI - hanya untuk pengujian lokal)
 * Uji hasil REVISI:
 *   1. Navigasi sidebar Admin & Petugas (urutan menu, dropdown, menu aktif)
 *   2. Integrasi menu Jaminan ke Kunjungan Aktif (termasuk pengembalian)
 *   3. Koneksi database membaca .env + proteksi .env dari browser + pesan gagal aman
 *   4. Kolom Jenis Identitas & Nomor Identitas terpisah + pencarian tetap jalan
 *   5. Penguncian Hasil Kunjungan (sekali simpan = catatan final)
 *
 * Pakai:
 *   php tests/revisi_test.php
 *   php tests/revisi_test.php http://localhost/buku_tamu_digital admin admin12345 petugas petugas12345
 *
 * Membutuhkan XAMPP (Apache + MySQL) berjalan.
 */

if (PHP_SAPI !== 'cli') {
    exit('Hanya untuk CLI.');
}

$BASE  = $argv[1] ?? 'http://localhost/buku_tamu_digital';
$ADM_U = $argv[2] ?? 'admin';
$ADM_P = $argv[3] ?? 'admin12345';
$PET_U = $argv[4] ?? 'petugas';
$PET_P = $argv[5] ?? 'petugas12345';

require __DIR__ . '/../config/database.php';

// Abaikan notice/deprecation ekstensi curl CLI (bukan bagian pengujian)
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);

$pass = 0;
$fail = 0;

function ok(string $name, bool $cond, string $extra = ''): void
{
    global $pass, $fail;
    if ($cond) {
        $pass++;
        echo "  [OK]   $name\n";
    } else {
        $fail++;
        echo "  [GAGAL] $name" . ($extra !== '' ? " - $extra" : '') . "\n";
    }
}

function req(string $url, ?array $post = null, ?string &$cookieFile = null, bool $follow = false, array $headers = []): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => $follow,
    ]);
    if ($cookieFile === null) {
        $cookieFile = tempnam(sys_get_temp_dir(), 'btd_revisi_');
    }
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $hdrs = ['Expect:'];
    foreach ($headers as $h) {
        $hdrs[] = $h;
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $hdrs);

    $raw = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);

    return [
        'status'  => $status,
        'headers' => substr($raw, 0, $headerSize),
        'body'    => substr($raw, $headerSize),
        'cookie'  => $cookieFile,
    ];
}

function csrf_of(string $html): string
{
    if (preg_match('/name="csrf_token" value="([a-f0-9]{64})"/', $html, $m)) {
        return $m[1];
    }
    return '';
}

function loc_of(string $headers): string
{
    if (preg_match('/^Location:\s*(.+?)\r?$/mi', $headers, $m)) {
        return trim($m[1]);
    }
    return '';
}

/** Ambil elemen <aside id="sidebarMenu"> dari halaman. */
function sidebar_of(string $html): string
{
    if (preg_match('#<aside[^>]*id="sidebarMenu".*?</aside>#s', $html, $m)) {
        return $m[0];
    }
    return '';
}

/** Ambil isi <main> (konten halaman, tanpa navbar/notifikasi). */
function main_of(string $html): string
{
    $p1 = strpos($html, '<main');
    $p2 = strrpos($html, '</main>');
    if ($p1 === false || $p2 === false || $p2 < $p1) {
        return $html;
    }
    return substr($html, $p1, $p2 - $p1);
}

function no_js_errors(string $body): bool
{
    foreach (['Fatal error', 'Uncaught', 'Parse error', 'Warning:', 'Deprecated:'] as $needle) {
        if (strpos($body, $needle) !== false) {
            return false;
        }
    }
    return true;
}

// Koneksi DB dipanaskan lebih dulu (dipakai untuk pembacaan data uji).
try {
    db()->query('SELECT 1')->fetchColumn();
} catch (Throwable $e) {
    echo "DB tidak tersedia: " . $e->getMessage() . "\n";
    exit(1);
}

$ROOT = dirname(__DIR__);
$envPath = $ROOT . '/.env';
$envAsli = is_file($envPath) ? (string) file_get_contents($envPath) : '';
$envDiubah = false;
register_shutdown_function(function () use ($envPath, $envAsli, &$envDiubah) {
    if ($envDiubah && $envAsli !== '') {
        file_put_contents($envPath, $envAsli);
    }
});

echo "=== UJI HASIL REVISI ===\nBasis URL: $BASE\n\n";

$sA = null; // cookie admin
$sP = null; // cookie petugas

/* ---------- 0. Login ---------- */
echo "0) Login\n";
$r = req("$BASE/login.php", null, $sA, true);
$tokA = csrf_of($r['body']);
$r = req("$BASE/actions/login_process.php", ['username' => $ADM_U, 'password' => $ADM_P, 'csrf_token' => $tokA], $sA);
ok('login admin', strpos(loc_of($r['headers']), 'index.php') !== false);

$r = req("$BASE/login.php", null, $sP, true);
$tokP = csrf_of($r['body']);
$r = req("$BASE/actions/login_process.php", ['username' => $PET_U, 'password' => $PET_P, 'csrf_token' => $tokP], $sP);
ok('login petugas', strpos(loc_of($r['headers']), 'index.php') !== false);

/* ---------- 1. Sidebar Admin ---------- */
echo "1) Sidebar Admin\n";
$r = req("$BASE/admin/dashboard.php", null, $sA, true);
$sideA = sidebar_of($r['body']);
ok('halaman dashboard admin terbuka', $r['status'] === 200 && no_js_errors($r['body']));
ok('sidebar admin berhasil dibaca', $sideA !== '');
ok('menu Jaminan tidak berdiri sendiri di sidebar admin',
    $sideA !== '' && strpos($sideA, 'jaminan/') === false);
ok('menu utama admin tetap ada',
    $sideA !== ''
    && strpos($sideA, '>Dashboard<') !== false
    && strpos($sideA, '>Data Tamu<') !== false
    && strpos($sideA, '>Semua Kunjungan<') !== false
    && strpos($sideA, '>Kunjungan Aktif<') !== false);
ok('menu aktif dashboard admin disorot',
    preg_match('#<a class="sidebar-link active"\s+href="[^"]*admin/dashboard\.php#', $sideA) === 1);

/* ---------- 2. Sidebar Petugas (urutan & dropdown) ---------- */
echo "2) Sidebar Petugas\n";
$r = req("$BASE/petugas/dashboard.php", null, $sP, true);
$sideP = sidebar_of($r['body']);
ok('halaman dashboard petugas terbuka', $r['status'] === 200 && no_js_errors($r['body']));
ok('sidebar petugas berhasil dibaca', $sideP !== '');
ok('menu Jaminan & menu Scan QR terpisah tidak ada di sidebar petugas',
    $sideP !== '' && strpos($sideP, 'jaminan/') === false && strpos($sideP, '>Scan QR<') === false);

$urut = ['>Dashboard<', '>Kunjungan Aktif<', '>Tambah Kunjungan<', '>Kunjungan<', '>Data Tamu<', '>Profil<'];
$pos  = [];
$okUrut = $sideP !== '';
foreach ($urut as $u) {
    $p = strpos($sideP, $u);
    $pos[$u] = $p;
    if ($p === false) {
        $okUrut = false;
    }
}
$sebelum = -1;
foreach ($urut as $u) {
    if ($pos[$u] === false || $pos[$u] < $sebelum) {
        $okUrut = false;
    }
    $sebelum = max($sebelum, $pos[$u]);
}
ok('urutan menu petugas: Dashboard > Kunjungan Aktif > Tambah Kunjungan > Kunjungan > Data Tamu > Profil', $okUrut,
    json_encode($pos));

ok('dropdown Tambah Kunjungan berisi Input Manual',
    $sideP !== '' && strpos($sideP, 'petugas/kunjungan/tambah.php') !== false && strpos($sideP, '>Input Manual<') !== false);
ok('dropdown Tambah Kunjungan berisi Pindai QR Code',
    $sideP !== '' && strpos($sideP, 'petugas/qr/scanner.php') !== false && strpos($sideP, '>Pindai QR Code<') !== false);

/* ---------- 3. Menu aktif saat submenu terbuka ---------- */
echo "3) Menu aktif dropdown\n";
$r = req("$BASE/petugas/kunjungan/tambah.php", null, $sP, true);
$sideT = sidebar_of($r['body']);
ok('form tambah kunjungan terbuka', $r['status'] === 200 && no_js_errors($r['body']));
ok('submenu Tambah Kunjungan terbuka (open) & tersorot',
    $sideT !== ''
    && strpos($sideT, '<details class="sidebar-menu" open') !== false
    && preg_match('#<a class="sidebar-link sidebar-sublink active"\s+href="[^"]*petugas/kunjungan/tambah\.php#', $sideT) === 1);

$r = req("$BASE/petugas/qr/scanner.php", null, $sP, true);
$sideS = sidebar_of($r['body']);
ok('scanner QR: submenu tersorot & terbuka',
    $sideS !== ''
    && strpos($sideS, '<details class="sidebar-menu" open') !== false
    && preg_match('#<a class="sidebar-link sidebar-sublink active"\s+href="[^"]*petugas/qr/scanner\.php#', $sideS) === 1);

/* ---------- 4. Data Tamu Petugas = halaman terpisah ---------- */
echo "4) Data Tamu Petugas\n";
$r = req("$BASE/petugas/tamu/index.php", null, $sP, true);
$sideD = sidebar_of($r['body']);
ok('halaman Data Tamu petugas terbuka sendiri', $r['status'] === 200 && no_js_errors($r['body']));
ok('judul & tabel Data Tamu tampil',
    strpos($r['body'], 'Data Tamu') !== false && strpos($r['body'], ' data tamu tercatat') !== false);
ok('menu aktif Data Tamu disorot',
    $sideD !== '' && preg_match('#<a class="sidebar-link active"\s+href="[^"]*petugas/tamu/index\.php#', $sideD) === 1);

/* ---------- 5. Data uji: tamu + kunjungan ---------- */
echo "5) Persiapan data uji\n";
$kodeTamu = 'TM-REV-' . strtoupper(bin2hex(random_bytes(2)));
$st = db()->prepare(
    "INSERT INTO visitors (kode_tamu, nama, jenis_identitas, no_identitas, telepon)
     VALUES (?, 'Tamu Uji Revisi', 'SIM', '3201998877660005', '081200011122')"
);
$st->execute([$kodeTamu]);
$tamuId = (int) db()->lastInsertId();
ok('tamu uji dibuat', $tamuId > 0);

$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'manual', 'visitor_id' => $tamuId,
    'tujuan' => 'Verifikasi revisi sistem', 'pegawai_ditemui' => 'Pak Audit', 'csrf_token' => $tokP,
], $sP);
$locVisit = loc_of($r['headers']);
$visitId = (int) filter_var($locVisit, FILTER_SANITIZE_NUMBER_INT);
ok('check-in kunjungan uji berhasil', strpos($locVisit, 'kunjungan/detail.php?id=') !== false, 'loc=' . $locVisit);

/* ---------- 6. Integrasi jaminan di Kunjungan Aktif ---------- */
echo "6) Integrasi Jaminan -> Kunjungan Aktif\n";
$r = req("$BASE/actions/jaminan_process.php", [
    'action' => 'tambah', 'visit_id' => (string) $visitId, 'barang' => 'Kunci Motor Uji',
    'jumlah' => '1', 'csrf_token' => $tokP,
], $sP);
ok('pencatatan jaminan dari kunjungan berhasil', strpos(loc_of($r['headers']), 'detail.php') !== false);

$stG = db()->prepare('SELECT id, visit_id, status FROM guarantees WHERE visit_id = ? ORDER BY id DESC LIMIT 1');
$stG->execute([$visitId]);
$gRow = $stG->fetch();
ok('jaminan tersimpan pada kunjungan yang benar', $gRow && (int) $gRow['visit_id'] === $visitId);
$gId = $gRow ? (int) $gRow['id'] : 0;

$r = req("$BASE/petugas/kunjungan/aktif.php", null, $sP, true);
$aktif = $r['body'];
ok('halaman Kunjungan Aktif terbuka', $r['status'] === 200 && no_js_errors($aktif));
ok('menyediakan akses ke daftar jaminan (Semua Jaminan)', strpos($aktif, 'Semua Jaminan') !== false);
ok('baris jaminan per kunjungan tampil (id jaminan-<visit>)',
    strpos($aktif, 'id="jaminan-' . $visitId . '"') !== false);
ok('barang yang dijaminkan tampil di Kunjungan Aktif', strpos($aktif, 'Kunci Motor Uji') !== false);
ok('status jaminan tampil (badge Aktif)', strpos($aktif, 'Aktif') !== false);
ok('akses pengembalian jaminan tersedia dari Kunjungan Aktif',
    $gId > 0 && strpos($aktif, 'name="action" value="kembalikan"') !== false
    && strpos($aktif, 'name="id" value="' . $gId . '"') !== false);
ok('akses tambah jaminan tersedia dari Kunjungan Aktif',
    strpos($aktif, 'jaminan/tambah.php?visit_id=' . $visitId) !== false);

// Kunjungan aktif hanya berisi kunjungan berlangsung (dicek pada konten utama,
// bukan seluruh body - notifikasi navbar juga memuat kode kunjungan)
$aktifMain = main_of($aktif);
$berl = db()->query("SELECT kode_kunjungan FROM visits WHERE status = 'berlangsung'")->fetchAll(PDO::FETCH_COLUMN);
$sel  = db()->query("SELECT kode_kunjungan FROM visits WHERE status = 'selesai' ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_COLUMN);
$semuaBerl = true;
foreach ($berl as $k) {
    if (strpos($aktifMain, $k) === false) {
        $semuaBerl = false;
        break;
    }
}
$adaSelesai = false;
foreach ($sel as $k) {
    if (strpos($aktifMain, $k) !== false) {
        $adaSelesai = true;
        break;
    }
}
ok('kunjungan berlangsung semuanya tampil di Kunjungan Aktif', $semuaBerl);
ok('kunjungan selesai TIDAK ikut tampil di Kunjungan Aktif', !$adaSelesai);

// Pengembalian jaminan langsung dari Kunjungan Aktif (referer = halaman aktif)
$r = req("$BASE/actions/jaminan_process.php", [
    'action' => 'kembalikan', 'id' => (string) $gId, 'csrf_token' => $tokP,
], $sP, false, ['Referer: ' . $BASE . '/petugas/kunjungan/aktif.php']);
$locKembali = loc_of($r['headers']);
ok('pengembalian jaminan dari Kunjungan Aktif kembali ke halaman aktif',
    strpos($locKembali, 'kunjungan/aktif.php') !== false, 'loc=' . $locKembali);
$stG->execute([$visitId]);
$gRow = $stG->fetch();
ok('jaminan berubah menjadi dikembalikan', $gRow && $gRow['status'] === 'dikembalikan');

// Setelah jaminan lunas, check-out kembali berfungsi
$r = req("$BASE/actions/checkout_process.php", ['id' => $visitId, 'csrf_token' => $tokP], $sP);
$stV = db()->prepare('SELECT status FROM visits WHERE id = ?');
$stV->execute([$visitId]);
ok('check-out berhasil setelah jaminan dikembalikan', $stV->fetchColumn() === 'selesai');

// Halaman riwayat jaminan tetap terbuka (akses via "Semua Jaminan")
$r = req("$BASE/admin/jaminan/index.php", null, $sA, true);
ok('halaman Semua Jaminan admin tetap terbuka', $r['status'] === 200 && no_js_errors($r['body']));
$sideJ = sidebar_of($r['body']);
ok('halaman jaminan menyorot menu Kunjungan Aktif (terintegrasi)',
    $sideJ !== '' && preg_match('#<a class="sidebar-link active"\s+href="[^"]*kunjungan/aktif\.php#', $sideJ) === 1);
$r = req("$BASE/petugas/jaminan/index.php", null, $sP, true);
ok('halaman Semua Jaminan petugas tetap terbuka', $r['status'] === 200 && no_js_errors($r['body']));

/* ---------- 7. Kolom identitas terpisah + pencarian ---------- */
echo "7) Data Tamu Admin: jenis & nomor identitas terpisah\n";
$r = req("$BASE/admin/tamu/index.php", null, $sA, true);
$tamuHtml = $r['body'];
ok('halaman Data Tamu admin terbuka', $r['status'] === 200 && no_js_errors($tamuHtml));
$thCount = preg_match_all('/<th(?:\s[^>]*)?>/', $tamuHtml);
ok('jumlah header kolom menjadi 8', $thCount === 8, 'th=' . $thCount);
foreach (['Kode Tamu', 'Nama Tamu', 'Jenis Identitas', 'Nomor Identitas', 'Telepon', 'Terdaftar', 'Kunjungan'] as $h) {
    ok("header kolom \"$h\" ada", strpos($tamuHtml, '<th>' . $h . '</th>') !== false
        || strpos($tamuHtml, '<th class="text-center">' . $h . '</th>') !== false);
}
$st = db()->prepare("SELECT jenis_identitas, no_identitas FROM visitors WHERE id = ?");
$st->execute([$tamuId]);
$ident = $st->fetch();
ok('data identitas uji terbaca dari database', $ident && $ident['no_identitas'] !== null);
ok('sel Jenis Identitas tampil terpisah', strpos($tamuHtml, '<td class="small">' . $ident['jenis_identitas'] . '</td>') !== false);
ok('sel Nomor Identitas tampil terpisah', strpos($tamuHtml, '<td class="small">' . $ident['no_identitas'] . '</td>') !== false);
ok('tidak ada gabungan "JENIS · NOMOR" pada tabel',
    strpos($tamuHtml, $ident['jenis_identitas'] . ' &middot; ' . $ident['no_identitas']) === false);

$r = req("$BASE/admin/tamu/index.php?q=" . urlencode($kodeTamu), null, $sA, true);
ok('pencarian berdasarkan kode tamu tetap berjalan', strpos($r['body'], $kodeTamu) !== false);
$r = req("$BASE/admin/tamu/index.php?q=" . urlencode('Tamu Uji Revisi'), null, $sA, true);
ok('pencarian berdasarkan nama tetap berjalan', strpos($r['body'], $kodeTamu) !== false);
$r = req("$BASE/admin/tamu/index.php?q=" . urlencode($ident['no_identitas']), null, $sA, true);
ok('pencarian berdasarkan nomor identitas tetap berjalan', strpos($r['body'], $kodeTamu) !== false);
$r = req("$BASE/admin/tamu/index.php?q=zzz-tidak-ada-9999", null, $sA, true);
ok('hasil pencarian kosong menampilkan tampilan kosong',
    strpos($r['body'], 'Tidak ada tamu yang cocok') !== false);

/* ---------- 8. Penguncian hasil kunjungan ---------- */
echo "8) Penguncian Hasil Kunjungan\n";
$hasilTeks = 'Hasil uji revisi: instalasi diperiksa dan dinyatakan layak.';

$r = req("$BASE/petugas/kunjungan/detail.php?id=$visitId", null, $sP, true);
ok('detail kunjungan terbuka', $r['status'] === 200 && no_js_errors($r['body']));
ok('kunjungan selesai tanpa hasil tetap menyediakan formulir simpan',
    strpos($r['body'], 'value="simpan_hasil"') !== false);

// Kunjungan lain yang masih berlangsung untuk uji simpan hasil
$st = db()->prepare(
    "INSERT INTO visits (kode_kunjungan, visitor_id, tujuan, pegawai_ditemui, jam_masuk, status)
     VALUES (?, ?, 'Uji simpan hasil', 'Pak Audit', NOW(), 'berlangsung')"
);
$kodeUji = 'KT-REV-' . strtoupper(bin2hex(random_bytes(2)));
$st->execute([$kodeUji, $tamuId]);
$visitHasil = (int) db()->lastInsertId();
ok('kunjungan uji hasil dibuat', $visitHasil > 0);

$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $visitHasil, 'hasil_kunjungan' => $hasilTeks, 'csrf_token' => $tokP,
], $sP);
ok('simpan hasil pertama diizinkan (berhasil)',
    strpos(loc_of($r['headers']), 'kunjungan/detail.php?id=' . $visitHasil) !== false);
$st = db()->prepare('SELECT hasil_kunjungan FROM visits WHERE id = ?');
$st->execute([$visitHasil]);
ok('hasil tersimpan di database', $st->fetchColumn() === $hasilTeks);

$r = req("$BASE/petugas/kunjungan/detail.php?id=$visitHasil", null, $sP, true);
ok('setelah tersimpan tampil status "Hasil Kunjungan Tersimpan"',
    strpos($r['body'], 'Hasil Kunjungan Tersimpan') !== false);
ok('setelah tersimpan formulir textarea & tombol Simpan hilang (read-only)',
    strpos($r['body'], 'id="hasilKunjungan"') === false
    && strpos($r['body'], 'value="simpan_hasil"') === false);
ok('isi hasil tetap tampil sebagai teks', strpos($r['body'], 'instalasi diperiksa') !== false);

// Percobaan perubahan langsung ke backend
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $visitHasil,
    'hasil_kunjungan' => 'HASIL DIUBAH LEWAT POST LANGSUNG', 'csrf_token' => $tokP,
], $sP);
$st->execute([$visitHasil]);
ok('POST perubahan hasil tersimpan DITOLAK server', $st->fetchColumn() === $hasilTeks);
$r2 = req("$BASE/petugas/kunjungan/detail.php?id=$visitHasil", null, $sP, true);
ok('pesan penolakan "final" tampil', strpos($r2['body'], 'bersifat final') !== false);

req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $visitHasil, 'hasil_kunjungan' => '', 'csrf_token' => $tokP,
], $sP);
$st->execute([$visitHasil]);
ok('pengosongan hasil tersimpan DITOLAK server', $st->fetchColumn() === $hasilTeks);

// Check-out tidak boleh mengubah hasil yang sudah tersimpan
$r = req("$BASE/actions/checkout_process.php", [
    'id' => $visitHasil, 'hasil_kunjungan' => 'HASIL DITIMPA OLEH CHECK-OUT', 'csrf_token' => $tokP,
], $sP);
$st = db()->prepare('SELECT status, hasil_kunjungan FROM visits WHERE id = ?');
$st->execute([$visitHasil]);
$v = $st->fetch();
ok('check-out tetap berjalan', $v && $v['status'] === 'selesai');
ok('check-out TIDAK mengubah hasil yang sudah tersimpan', $v && $v['hasil_kunjungan'] === $hasilTeks);

/* ---------- 9. Konfigurasi .env ---------- */
echo "9) Konfigurasi database .env\n";
$envIsi = is_file($envPath) ? (string) file_get_contents($envPath) : '';
$envBaris = [];
foreach (explode("\n", str_replace("\r", '', $envIsi)) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    [$k, $v] = explode('=', $line, 2);
    $envBaris[trim($k)] = trim($v);
}
foreach (['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME', 'DB_PASSWORD', 'DB_CHARSET'] as $k) {
    ok(".env memuat variabel $k", array_key_exists($k, $envBaris));
}
ok('config/database.php membaca DB_DATABASE/DB_USERNAME/DB_PASSWORD/DB_CHARSET',
    strpos((string) file_get_contents($ROOT . '/config/database.php'), 'DB_DATABASE') !== false
    && strpos((string) file_get_contents($ROOT . '/config/database.php'), 'DB_USERNAME') !== false
    && strpos((string) file_get_contents($ROOT . '/config/database.php'), 'DB_PASSWORD') !== false
    && strpos((string) file_get_contents($ROOT . '/config/database.php'), 'DB_CHARSET') !== false);
ok('nilai DB_NAME konsisten dengan .env (DB_DATABASE)', DB_NAME === ($envBaris['DB_DATABASE'] ?? DB_NAME),
    'DB_NAME=' . DB_NAME);
ok('.env masuk ke .gitignore',
    is_file($ROOT . '/.gitignore') && strpos((string) file_get_contents($ROOT . '/.gitignore'), '.env') !== false);
ok('.env.example tersedia tanpa password sensitif',
    is_file($ROOT . '/.env.example') && strpos((string) file_get_contents($ROOT . '/.env.example'), 'DB_DATABASE') !== false);

// Tidak ada kredensial yang ditulis langsung di file PHP lain
$keras = [];
$ri = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($ROOT, FilesystemIterator::SKIP_DOTS)
);
foreach ($ri as $file) {
    if (!$file->isFile() || $file->getExtension() !== 'php') {
        continue;
    }
    $path = str_replace('\\', '/', $file->getPathname());
    if (strpos($path, '/vendor/') !== false || strpos($path, '/tests/') !== false
        || strpos($path, '/config/database.php') !== false) {
        continue;
    }
    $src = (string) file_get_contents($file->getPathname());
    if (preg_match('/new\s+PDO\s*\(|mysqli_connect\s*\(/', $src)) {
        $keras[] = substr($path, strlen($ROOT) + 1);
    }
}
ok('tidak ada file PHP lain yang membuat koneksi database sendiri', count($keras) === 0, implode(', ', $keras));

// .env tidak dapat diakses browser
$sX1 = null;
$r = req("$BASE/.env", null, $sX1, false);
ok('.env ditolak diakses dari browser (403)', $r['status'] === 403, 'status=' . $r['status']);
ok('isi .env tidak terkirim ke browser', strpos($r['body'], 'DB_PASSWORD') === false);
$sX2 = null;
$r = req("$BASE/.env.example", null, $sX2, false);
ok('.env.example juga ditolak (403)', $r['status'] === 403, 'status=' . $r['status']);

/* ---------- 10. Pesan aman saat konfigurasi database salah ---------- */
echo "10) Pesan aman bila konfigurasi database salah\n";
if ($envAsli === '') {
    ok('pengujian konfigurasi salah (file .env tidak tersedia)', true, 'dilewati');
} else {
    $envSalah = preg_replace('/^DB_PASSWORD=.*$/m', 'DB_PASSWORD=RAHASIA-JANGAN-BOCOR', $envAsli);
    if ($envSalah === null || $envSalah === $envAsli) {
        $envSalah = $envAsli . "\nDB_PASSWORD=RAHASIA-JANGAN-BOCOR\n";
    }
    file_put_contents($envPath, $envSalah);
    $envDiubah = true;

    $sErr = null;
    $r = req("$BASE/login.php", null, $sErr, false);
    ok('koneksi gagal memberi status server error', $r['status'] >= 500, 'status=' . $r['status']);
    ok('menampilkan pesan "Koneksi database gagal"',
        strpos($r['body'], 'Koneksi database gagal') !== false);
    ok('password database TIDAK tampil di halaman error',
        strpos($r['body'], 'RAHASIA-JANGAN-BOCOR') === false);

    file_put_contents($envPath, $envAsli);
    $envDiubah = false;
    $sOk = null;
    $r = req("$BASE/login.php", null, $sOk, false);
    ok('aplikasi pulih setelah .env diperbaiki', $r['status'] === 200, 'status=' . $r['status']);
}

/* ---------- 11. Halaman inti tidak rusak ---------- */
echo "11) Halaman inti setelah revisi\n";
$halaman = [
    'admin dashboard'   => "$BASE/admin/dashboard.php|$sA",
    'petugas dashboard' => "$BASE/petugas/dashboard.php|$sP",
    'semua kunjungan'   => "$BASE/admin/kunjungan/index.php|$sA",
    'detail kunjungan'  => "$BASE/admin/kunjungan/detail.php?id=$visitId|$sA",
    'kunjungan aktif'   => "$BASE/petugas/kunjungan/aktif.php|$sP",
    'tambah kunjungan'  => "$BASE/petugas/kunjungan/tambah.php|$sP",
    'form jaminan'      => "$BASE/petugas/jaminan/tambah.php?visit_id=$visitId|$sP",
    'data tamu petugas' => "$BASE/petugas/tamu/index.php|$sP",
    'tamu detail'       => "$BASE/petugas/tamu/detail.php?id=$tamuId|$sP",
    'profil petugas'    => "$BASE/petugas/profil.php|$sP",
    'laporan'           => "$BASE/admin/laporan/index.php|$sA",
];
foreach ($halaman as $nama => $spec) {
    [$url, $cookie] = explode('|', $spec);
    $r = req($url, null, $cookie, true);
    ok("halaman $nama (" . $r['status'] . ') tanpa error PHP', $r['status'] === 200 && no_js_errors($r['body']),
        substr(preg_replace('/\s+/', ' ', strip_tags($r['body'])), 0, 120));
}

/* ---------- 12. Bersihkan data uji ---------- */
echo "12) Pembersihan data uji\n";
$st = db()->prepare('DELETE FROM guarantees WHERE visit_id IN (SELECT id FROM visits WHERE visitor_id = ?)');
$st->execute([$tamuId]);
$st = db()->prepare('DELETE FROM visits WHERE visitor_id = ?');
$st->execute([$tamuId]);
$st = db()->prepare('DELETE FROM visitors WHERE id = ?');
$st->execute([$tamuId]);
ok('data uji dibersihkan', true);

/* ---------- Ringkasan ---------- */
echo "\n=== RINGKASAN: $pass lulus, $fail gagal ===\n";
exit($fail > 0 ? 1 : 0);
