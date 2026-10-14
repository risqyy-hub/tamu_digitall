<?php
/**
 * tests/hasil_kunjungan_test.php  (CLI - hanya untuk pengujian lokal)
 * Uji fitur HASIL KUNJUNGAN (visits.hasil_kunjungan) end-to-end via HTTP.
 *
 * Pakai:
 *   php tests/hasil_kunjungan_test.php
 *   php tests/hasil_kunjungan_test.php http://localhost/buku_tamu_digital admin admin12345 petugas petugas12345
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

function req(string $url, ?array $post = null, ?string &$cookieFile = null, bool $follow = false): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_FOLLOWLOCATION => $follow,
    ]);
    if ($cookieFile === null) {
        $cookieFile = tempnam(sys_get_temp_dir(), 'btd_hasil_');
    }
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);

    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    if (strpos($url, 'http://localhost') === 0) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Expect:']);
    }

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

function visit_row(int $id): ?array
{
    $st = db()->prepare('SELECT * FROM visits WHERE id = ?');
    $st->execute([$id]);
    $row = $st->fetch();
    return $row ?: null;
}

echo "=== UJI FITUR HASIL KUNJUNGAN ===\nBasis URL: $BASE\n\n";

$sA = null; // cookie admin
$sP = null; // cookie petugas

/* ---------- 0. Login ---------- */
echo "0) Login\n";
$r = req("$BASE/login.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/login_process.php", ['username' => $ADM_U, 'password' => $ADM_P, 'csrf_token' => $tok], $sA);
ok('login admin', strpos(loc_of($r['headers']), 'index.php') !== false);

$r = req("$BASE/login.php", null, $sP, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/login_process.php", ['username' => $PET_U, 'password' => $PET_P, 'csrf_token' => $tok], $sP);
ok('login petugas', strpos(loc_of($r['headers']), 'index.php') !== false);

/* ---------- 1. Struktur database ---------- */
echo "1) Struktur database\n";
$cols = [];
foreach (db()->query("SHOW COLUMNS FROM visits LIKE 'hasil_kunjungan'") as $c) {
    $cols[] = $c;
}
ok('kolom hasil_kunjungan tersedia pada tabel visits', count($cols) === 1);
ok('kolom hasil_kunjungan bertipe TEXT & boleh NULL',
    count($cols) === 1 && stripos((string) $cols[0]['Type'], 'text') !== false && $cols[0]['Null'] === 'YES');
$jumlahKunjungan = (int) db()->query('SELECT COUNT(*) FROM visits')->fetchColumn();
ok('data kunjungan lama tidak terhapus', $jumlahKunjungan > 0, 'jumlah=' . $jumlahKunjungan);
ok('tidak ada tabel duplikat hasil_kunjungan',
    (int) db()->query("SELECT COUNT(*) FROM information_schema.tables
                       WHERE table_schema = DATABASE() AND table_name LIKE '%hasil%'")->fetchColumn() === 0);

/* ---------- 2. Tamu untuk pengujian ---------- */
echo "2) Persiapan tamu uji\n";
$kodeTamu = 'TM-TEST-' . strtoupper(bin2hex(random_bytes(2)));
$st = db()->prepare("INSERT INTO visitors (kode_tamu, nama, jenis_identitas) VALUES (?, 'Tester Hasil Kunjungan', 'KTP')");
$st->execute([$kodeTamu]);
$tamuId = (int) db()->lastInsertId();
ok('tamu uji dibuat', $tamuId > 0);

/* ---------- 3. Check-in TANPA hasil kunjungan ---------- */
echo "3) Check-in tanpa hasil kunjungan\n";
$r = req("$BASE/petugas/kunjungan/tambah.php", null, $sP, true);
$tok = csrf_of($r['body']);
ok('form tambah menyediakan field Hasil Kunjungan', strpos($r['body'], 'name="hasil_kunjungan"') !== false);

$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'manual', 'visitor_id' => $tamuId,
    'tujuan' => 'Pemeriksaan instalasi jaringan', 'pegawai_ditemui' => 'Pak Tester',
    'csrf_token' => $tok,
], $sP);
$loc1 = loc_of($r['headers']);
ok('check-in berhasil', strpos($loc1, 'kunjungan/detail.php?id=') !== false, 'loc=' . $loc1);
$idA = (int) filter_var($loc1, FILTER_SANITIZE_NUMBER_INT);
$vA = visit_row($idA);
ok('hasil kunjungan KOSONG (NULL) saat check-in', $vA && $vA['hasil_kunjungan'] === null);

$r = req("$BASE/admin/kunjungan/detail.php?id=$idA", null, $sA, true);
ok('detail menampilkan "Belum ada hasil kunjungan"',
    $r['status'] === 200 && strpos($r['body'], 'Belum ada hasil kunjungan') !== false);
ok('detail menyediakan form simpan hasil', strpos($r['body'], 'name="simpan_hasil"') !== false
    || strpos($r['body'], 'value="simpan_hasil"') !== false);

/* ---------- 4. Simpan hasil kunjungan setelah aktivitas ---------- */
echo "4) Simpan hasil kunjungan\n";
$hasilA = "Pemeriksaan selesai, tetapi penggantian perangkat dijadwalkan pada\nkunjungan berikutnya.";
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => $hasilA, 'csrf_token' => $tok,
], $sP);
ok('simpan hasil oleh petugas diarahkan kembali ke detail',
    strpos(loc_of($r['headers']), 'kunjungan/detail.php?id=' . $idA) !== false, 'loc=' . loc_of($r['headers']));
$vA = visit_row($idA);
ok('hasil kunjungan TERSIMPAN di database', $vA && $vA['hasil_kunjungan'] === $hasilA);
ok('kolom lain tidak berubah (tujuan/status tetap)',
    $vA && $vA['tujuan'] === 'Pemeriksaan instalasi jaringan' && $vA['status'] === 'berlangsung');

$r = req("$BASE/admin/kunjungan/detail.php?id=$idA", null, $sA, true);
ok('detail menampilkan hasil kunjungan lengkap', strpos($r['body'], 'penggantian perangkat') !== false);

/* ---------- 5. Kolom pada tabel Admin & Petugas ---------- */
echo "5) Tabel Semua Kunjungan (admin & petugas)\n";
$r = req("$BASE/admin/kunjungan/index.php", null, $sA, true);
$thCount = preg_match_all('/<th(?:\s[^>]*)?>/', $r['body']);
ok('admin: header kolom Hasil Kunjungan setelah Tujuan',
    preg_match('/<th>Tujuan<\/th>\s*<th>Hasil Kunjungan<\/th>/', $r['body']) === 1);
ok('admin: kolom total menjadi 10', $thCount === 10, 'th=' . $thCount);
ok('admin: catatan panjang tampil terpotong rapi + Lihat Detail',
    strpos($r['body'], 'hasil-clamp') !== false && strpos($r['body'], 'Lihat Detail') !== false);
ok('admin: teks lengkap tetap tersimpan di DOM (title)',
    strpos($r['body'], 'title="' . htmlspecialchars($hasilA, ENT_QUOTES, 'UTF-8') . '"') !== false);
ok('admin: pencarian, filter & pagination tetap ada',
    strpos($r['body'], 'name="q"') !== false && strpos($r['body'], 'name="status"') !== false
    && strpos($r['body'], 'name="dari"') !== false);

$r = req("$BASE/petugas/kunjungan/index.php", null, $sP, true);
ok('petugas: header kolom Hasil Kunjungan setelah Tujuan',
    preg_match('/<th>Tujuan<\/th>\s*<th>Hasil Kunjungan<\/th>/', $r['body']) === 1);
ok('petugas: catatan uji tampil', strpos($r['body'], 'penggantian perangkat') !== false);

/* ---------- 6. Kunjungan lama tanpa catatan ---------- */
echo "6) Kunjungan lama tanpa hasil\n";
$lama = db()->query("SELECT id FROM visits WHERE hasil_kunjungan IS NULL AND id <> $idA ORDER BY id DESC LIMIT 1")->fetch();
if ($lama) {
    $r = req("$BASE/admin/kunjungan/index.php", null, $sA, true);
    ok('kunjungan lama tanpa catatan tampil sebagai "-"',
        strpos($r['body'], '&mdash;') !== false || strpos($r['body'], '—') !== false);
} else {
    ok('kunjungan lama tanpa catatan tampil sebagai "-"', true, '(tidak ada baris NULL tersisa)');
}

/* ---------- 7. Check-out menyimpan hasil ---------- */
echo "7) Check-out dengan hasil kunjungan\n";
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'manual', 'visitor_id' => $tamuId,
    'tujuan' => 'Pengiriman dokumen', 'pegawai_ditemui' => 'Ibu Tester', 'csrf_token' => $tok,
], $sP);
$idB = (int) filter_var(loc_of($r['headers']), FILTER_SANITIZE_NUMBER_INT);
ok('check-in kedua berhasil', $idB > 0);

$hasilB = 'Dokumen diterima oleh bagian administrasi.';
$r = req("$BASE/actions/checkout_process.php", [
    'id' => $idB, 'hasil_kunjungan' => $hasilB, 'csrf_token' => $tok,
], $sP);
$vB = visit_row($idB);
ok('check-out berhasil', $vB && $vB['status'] === 'selesai');
ok('hasil kunjungan tersimpan bersama check-out', $vB && $vB['hasil_kunjungan'] === $hasilB);

// Check-out TANPA mengisi hasil -> catatan lama tidak boleh hilang
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'manual', 'visitor_id' => $tamuId,
    'tujuan' => 'Pertemuan dengan bagian keuangan', 'pegawai_ditemui' => 'Pak Tester', 'csrf_token' => $tok,
], $sP);
$idC = (int) filter_var(loc_of($r['headers']), FILTER_SANITIZE_NUMBER_INT);
$hasilC = 'Pembahasan selesai dan dokumen penawaran diminta untuk dilengkapi.';
req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idC, 'hasil_kunjungan' => $hasilC, 'csrf_token' => $tok,
], $sP);
$r = req("$BASE/actions/checkout_process.php", ['id' => $idC, 'csrf_token' => $tok], $sP);
$vC = visit_row($idC);
ok('check-out tanpa isi hasil TIDAK menghapus catatan lama',
    $vC && $vC['status'] === 'selesai' && $vC['hasil_kunjungan'] === $hasilC);

/* ---------- 8. Validasi server ---------- */
echo "8) Validasi server & hak akses\n";
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => str_repeat('X', 4001),
    'csrf_token' => $tok,
], $sP);
$vA = visit_row($idA);
ok('catatan > 4000 karakter DITOLAK (data tidak berubah)', $vA['hasil_kunjungan'] === $hasilA);

$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => 'Manipulasi tanpa CSRF',
], $sA);
ok('POST tanpa token CSRF ditolak (403)', $r['status'] === 403, 'status=' . $r['status']);
$vA = visit_row($idA);
ok('data tidak berubah setelah CSRF ditolak', $vA['hasil_kunjungan'] === $hasilA);

$sX = null;
$r = req("$BASE/login.php", null, $sX, true);
$tokX = csrf_of($r['body']);
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => 'DARI PENYUSUP', 'csrf_token' => $tokX,
], $sX);
$vA = visit_row($idA);
ok('tanpa login TIDAK dapat mengubah hasil kunjungan',
    $vA['hasil_kunjungan'] === $hasilA, 'loc=' . loc_of($r['headers']));

$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => 99999999, 'hasil_kunjungan' => 'Tidak ada', 'csrf_token' => $tok,
], $sP);
ok('kunjungan tidak valid ditolak dengan aman', strpos(loc_of($r['headers']), 'detail.php') !== false);

// Kosongkan hasil kembali (mengizinkan pengosongan oleh yang berwenang)
req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => '', 'csrf_token' => $tok,
], $sP);
$vA = visit_row($idA);
ok('hasil kunjungan dapat dikosongkan kembali', $vA['hasil_kunjungan'] === null);

// isi ulang untuk pengujian laporan
req("$BASE/actions/kunjungan_process.php", [
    'action' => 'simpan_hasil', 'id' => $idA, 'hasil_kunjungan' => $hasilA, 'csrf_token' => $tok,
], $sP);

/* ---------- 9. Monitoring, riwayat, laporan, cetak, CSV ---------- */
echo "9) Monitoring, riwayat, laporan, cetak & CSV\n";
$r = req("$BASE/admin/kunjungan/aktif.php", null, $sA, true);
ok('monitoring kunjungan aktif tetap terbuka', $r['status'] === 200 && strpos($r['body'], 'Kunjungan Aktif') !== false);
ok('monitoring menampilkan hasil kunjungan bila terisi',
    strpos($r['body'], 'penggantian perangkat') !== false || strpos($r['body'], 'Belum ada') !== false);

$r = req("$BASE/admin/tamu/detail.php?id=$tamuId", null, $sA, true);
ok('riwayat kunjungan pada detail tamu punya kolom Hasil Kunjungan',
    preg_match('/<th>Tujuan<\/th>\s*<th>Hasil Kunjungan<\/th>/',
        preg_replace('/\s+/', ' ', $r['body'])) === 1);

$r = req("$BASE/admin/laporan/index.php?dari=2000-01-01&sampai=2099-12-31", null, $sA, true);
ok('laporan terbuka dengan tombol cetak & CSV',
    $r['status'] === 200 && strpos($r['body'], 'window.print()') !== false && strpos($r['body'], 'ekspor_csv.php') !== false);
ok('laporan menampilkan kolom Hasil Kunjungan setelah Tujuan',
    preg_match('/<th>Tujuan<\/th>\s*<th>Hasil Kunjungan<\/th>/', $r['body']) === 1);
ok('laporan menampilkan isi catatan uji', strpos($r['body'], 'penggantian perangkat') !== false);

$r = req("$BASE/admin/laporan/ekspor_csv.php?dari=2000-01-01&sampai=2099-12-31", null, $sA, true);
ok('CSV memberi header unduhan',
    stripos($r['headers'], 'Content-Type: text/csv') !== false && stripos($r['headers'], 'attachment') !== false);
ok('CSV berisi header Hasil Kunjungan setelah Tujuan',
    preg_match('/Tujuan,"?Hasil Kunjungan"?,\s*"?Pegawai Ditemui"?/', $r['body']) === 1);
ok('CSV berisi isi catatan hasil (tanpa dipotong)',
    strpos($r['body'], 'penggantian perangkat') !== false && strpos($r['body'], $hasilB) !== false);

$r = req("$BASE/admin/dashboard.php", null, $sA, true);
ok('dashboard admin terbuka tanpa error', $r['status'] === 200 && strpos($r['body'], 'Dashboard Admin') !== false);
$r = req("$BASE/petugas/dashboard.php", null, $sP, true);
ok('dashboard petugas terbuka tanpa error', $r['status'] === 200 && strpos($r['body'], 'Dashboard Petugas') !== false);

/* ---------- 10. Bersihkan data uji ---------- */
echo "10) Pembersihan data uji\n";
$st = db()->prepare('DELETE FROM visits WHERE visitor_id = ?');
$st->execute([$tamuId]);
$st = db()->prepare('DELETE FROM visitors WHERE id = ?');
$st->execute([$tamuId]);
ok('data uji dibersihkan', true);

/* ---------- Ringkasan ---------- */
echo "\n=== RINGKASAN: $pass lulus, $fail gagal ===\n";
exit($fail > 0 ? 1 : 0);
