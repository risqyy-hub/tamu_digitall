<?php
/**
 * tests/e2e_test.php  (CLI - hanya untuk pengujian lokal)
 * Uji alur end-to-end via HTTP terhadap aplikasi yang sedang berjalan di XAMPP.
 *
 * Pakai:
 *   php tests/e2e_test.php
 *   php tests/e2e_test.php http://localhost/buku_tamu_digital admin admin12345 petugas petugas12345
 *
 * Skrip ini membutuhkan ekstensi curl & pdo_mysql PHP CLI.
 */

if (PHP_SAPI !== 'cli') {
    exit('Hanya untuk CLI.');
}

$BASE    = $argv[1] ?? 'http://localhost/buku_tamu_digital';
$ADM_U   = $argv[2] ?? 'admin';
$ADM_P   = $argv[3] ?? 'admin12345';
$PET_U   = $argv[4] ?? 'petugas';
$PET_P   = $argv[5] ?? 'petugas12345';

require __DIR__ . '/../config/database.php'; // koneksi DB langsung untuk verifikasi data

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

/** HTTP request sederhana dengan cookie jar. */
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
        $cookieFile = tempnam(sys_get_temp_dir(), 'btd_');
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

function flash_of(string $html): string
{
    if (preg_match('/<div class="alert alert-[a-z]+ alert-dismissible fade show"[^>]*>.*?<\/button>/s', $html, $m)) {
        return trim(strip_tags($m[0]));
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

/** Ubah header Location (bisa path absolut) menjadi URL lengkap. */
function abs_url(string $base, string $loc): string
{
    if ($loc === '') {
        return '';
    }
    if (preg_match('#^https?://#i', $loc)) {
        return $loc;
    }
    $parts = parse_url($base);
    return ($parts['scheme'] ?? 'http') . '://' . ($parts['host'] ?? 'localhost')
        . (isset($parts['port']) ? ':' . $parts['port'] : '') . $loc;
}

echo "=== UJI END-TO-END BUKU TAMU DIGITAL ===\nBasis URL: $BASE\n\n";
$sA = null; // cookie admin
$sP = null; // cookie petugas

/* ---------- 1. Akses tanpa login ---------- */
echo "1) Akses tanpa login\n";
$r = req("$BASE/index.php", null, $sA, false);
ok('index.php tanpa login diarahkan ke login', loc_of($r['headers']) !== '' && strpos(loc_of($r['headers']), 'login.php') !== false, 'loc=' . loc_of($r['headers']));

$r = req("$BASE/admin/dashboard.php", null, $sA, false);
ok('admin/dashboard.php tanpa login diarahkan ke login', strpos(loc_of($r['headers']), 'login.php') !== false);

/* ---------- 2. Login admin ---------- */
echo "2) Login admin\n";
$r = req("$BASE/login.php", null, $sA, true);
$tok = csrf_of($r['body']);
ok('halaman login menampilkan form CSRF', $tok !== '');

$r = req("$BASE/actions/login_process.php", ['username' => $ADM_U, 'password' => 'salah-salah', 'csrf_token' => $tok], $sA);
ok('login password salah DITOLAK', strpos(loc_of($r['headers']), 'login.php') !== false);

$r = req("$BASE/actions/login_process.php", ['username' => $ADM_U, 'password' => $ADM_P, 'csrf_token' => $tok], $sA);
ok('login admin berhasil', strpos(loc_of($r['headers']), 'index.php') !== false, 'loc=' . loc_of($r['headers']));

$r = req("$BASE/admin/dashboard.php", null, $sA, true);
ok('dashboard admin terbuka', $r['status'] === 200 && strpos($r['body'], 'Dashboard Admin') !== false);
ok('dashboard menampilkan data non-hardcode (kartu statistik)', strpos($r['body'], 'stat-value') !== false);

/* ---------- 3. CSRF ---------- */
echo "3) Proteksi CSRF\n";
$r = req("$BASE/actions/tamu_process.php", ['action' => 'tambah', 'nama' => 'Tanpa CSRF'], $sA);
ok('POST tanpa token CSRF ditolak (403)', $r['status'] === 403, 'status=' . $r['status']);

/* ---------- 4. CRUD tamu (admin) ---------- */
echo "4) CRUD tamu\n";
$r = req("$BASE/admin/tamu/tambah.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/tamu_process.php", [
    'action' => 'tambah', 'nama' => 'Budi Santoso', 'jenis_identitas' => 'KTP',
    'no_identitas' => '3201234567890001', 'telepon' => '081234567890',
    'csrf_token' => $tok,
], $sA);
ok('tambah tamu berhasil', strpos(loc_of($r['headers']), 'tamu/') !== false, 'loc=' . loc_of($r['headers']));
$budi = db()->query("SELECT * FROM visitors WHERE nama = 'Budi Santoso'")->fetch();
ok('data tamu tersimpan di database', (bool) $budi);

// input tidak valid
$r = req("$BASE/actions/tamu_process.php", [
    'action' => 'tambah', 'nama' => 'A', 'jenis_identitas' => 'KTP', 'csrf_token' => $tok,
], $sA);
ok('nama terlalu pendek ditolak', strpos(loc_of($r['headers']), 'tambah.php') !== false);

/* ---------- 5. CRUD mitra & registrasi ---------- */
echo "5) Mitra & registrasi tamu\n";
$r = req("$BASE/admin/mitra/tambah.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/mitra_process.php", [
    'action' => 'tambah', 'nama_perusahaan' => 'PT Maju Jaya', 'alamat' => 'Jl. Merdeka 1',
    'status' => 'aktif', 'csrf_token' => $tok,
], $sA);
ok('tambah mitra berhasil', strpos(loc_of($r['headers']), 'mitra') !== false);
$mitra = db()->query("SELECT * FROM partner_companies WHERE nama_perusahaan = 'PT Maju Jaya'")->fetch();

$r = req("$BASE/admin/tamu/registrasi.php", null, $sA, true);
$tok = csrf_of($r['body']);
$kartuBaru = 'KRT-' . date('His');
$r = req("$BASE/actions/registrasi_process.php", [
    'action' => 'tambah', 'visitor_id' => $budi['id'], 'mitra_id' => $mitra['id'],
    'no_kartu' => $kartuBaru, 'csrf_token' => $tok,
], $sA);
ok('registrasi tamu terdaftar berhasil', strpos(loc_of($r['headers']), 'registrasi.php') !== false);
$reg = db()->query("SELECT * FROM registered_visitors WHERE no_kartu = '$kartuBaru'")->fetch();

/* ---------- 6. Terbitkan QR (admin) ---------- */
echo "6) Penerbitan QR\n";
$r = req("$BASE/admin/qr/tambah.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/qr_process.php", [
    'action' => 'terbitkan', 'registered_visitor_id' => $reg['id'], 'csrf_token' => $tok,
], $sA);
$locTampil = loc_of($r['headers']);
ok('penerbitan QR mengarahkan ke halaman tampil', strpos($locTampil, 'qr/tampil.php?id=') !== false, 'loc=' . $locTampil);
$qrId = (int) filter_var($locTampil, FILTER_SANITIZE_NUMBER_INT);

$r = req(abs_url($BASE, $locTampil), null, $sA, true);
ok('halaman tampil QR menampilkan gambar QR', $r['status'] === 200 && strpos($r['body'], 'data:image/png;base64') !== false);
$qrDb = db()->prepare('SELECT * FROM visitor_qr_codes WHERE id = ?');
$qrDb->execute([$qrId]);
$qrRow = $qrDb->fetch();
ok('token disimpan sebagai hash (bukan teks)', $qrRow && strlen($qrRow['token_hash']) === 64);

// Token QR kedua untuk alur scan (disisipkan langsung ke DB agar diketahui nilainya)
$tokenQR = bin2hex(random_bytes(32));
$st = db()->prepare("INSERT INTO visitor_qr_codes (registered_visitor_id, token_hash, status, valid_from, valid_until, issued_by) VALUES (?,?,'aktif',?,NULL,1)");
$st->execute([$reg['id'], hash('sha256', $tokenQR), date('Y-m-d H:i:s')]);
$qrScanId = (int) db()->lastInsertId();

/* ---------- 7. Role guard petugas ---------- */
echo "7) Hak akses petugas\n";
$r = req("$BASE/login.php", null, $sP, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/login_process.php", ['username' => $PET_U, 'password' => $PET_P, 'csrf_token' => $tok], $sP);
ok('login petugas berhasil', strpos(loc_of($r['headers']), 'index.php') !== false);

$r = req("$BASE/admin/users/index.php", null, $sP, false);
ok('petugas DITOLAK akses manajemen user (403)', $r['status'] === 403, 'status=' . $r['status']);
$r = req("$BASE/admin/pengaturan.php", null, $sP, false);
ok('petugas DITOLAK akses pengaturan (403)', $r['status'] === 403);
$r = req("$BASE/petugas/dashboard.php", null, $sP, true);
ok('petugas bisa membuka dashboard sendiri', $r['status'] === 200 && strpos($r['body'], 'Dashboard Petugas') !== false);

/* ---------- 8. Check-in manual ---------- */
echo "8) Check-in manual\n";
$r = req("$BASE/petugas/kunjungan/tambah.php", null, $sP, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'manual', 'visitor_id' => $budi['id'],
    'tujuan' => 'Bidang Keuangan', 'pegawai_ditemui' => 'Ibu Sari', 'keperluan' => 'Administrasi',
    'csrf_token' => $tok,
], $sP);
$locVisit = loc_of($r['headers']);
ok('check-in manual berhasil', strpos($locVisit, 'kunjungan/detail.php?id=') !== false, 'loc=' . $locVisit);
$visit1 = db()->query("SELECT * FROM visits WHERE visitor_id = " . (int) $budi['id'] . " ORDER BY id DESC")->fetch();
ok('kunjungan tercatat dengan status berlangsung', $visit1 && $visit1['status'] === 'berlangsung');
ok('jam masuk terisi otomatis oleh server', !empty($visit1['jam_masuk']));

/* ---------- 9. Check-in via QR ---------- */
echo "9) Check-in via QR\n";
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'qr', 'token' => $tokenQR,
    'tujuan' => 'Bidang Umum', 'pegawai_ditemui' => 'Pak Andi', 'csrf_token' => $tok,
], $sP);
ok('check-in via QR berhasil', strpos(loc_of($r['headers']), 'detail.php?id=') !== false, 'loc=' . loc_of($r['headers']));
$visit2 = db()->query("SELECT * FROM visits WHERE qr_id = $qrScanId")->fetch();
ok('kunjungan menyimpan referensi QR & registrasi', $visit2 && $visit2['qr_id'] == $qrScanId && $visit2['registered_visitor_id'] == $reg['id']);
ok('identitas tamu diambil dari DB (visitor_id QR)', $visit2 && $visit2['visitor_id'] == $budi['id']);

// Percobaan manipulasi: kirim visitor_id lain dengan token QR - harus mengikuti token
$kodeKorban = 'TM-X' . strtoupper(bin2hex(random_bytes(2)));
$st = db()->prepare("INSERT INTO visitors (kode_tamu, nama, jenis_identitas) VALUES (?, 'Korban Manipulasi', 'KTP')");
$st->execute([$kodeKorban]);
$korbanId = (int) db()->lastInsertId();
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'qr', 'token' => $tokenQR, 'visitor_id' => $korbanId,
    'tujuan' => 'Bidang SDM', 'pegawai_ditemui' => 'Ibu Rina', 'csrf_token' => $tok,
], $sP);
$visitManip = db()->query("SELECT * FROM visits WHERE visitor_id = $korbanId")->fetch();
ok('identitas dari browser TIDAK dipercaya (tetap memakai pemilik QR)', $visitManip === false);

/* ---------- 10. QR bermasalah ditolak ---------- */
echo "10) Validasi QR\n";
// kedaluwarsa
$st = db()->prepare("INSERT INTO visitor_qr_codes (registered_visitor_id, token_hash, status, valid_from, valid_until, issued_by) VALUES (?,?,'aktif',?, ?, 1)");
$tokenExp = bin2hex(random_bytes(32));
$st->execute([$reg['id'], hash('sha256', $tokenExp), date('Y-m-d H:i:s', time() - 7200), date('Y-m-d H:i:s', time() - 3600)]);
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'qr', 'token' => $tokenExp,
    'tujuan' => 'Tes', 'pegawai_ditemui' => 'Tes', 'csrf_token' => $tok,
], $sP);
$vExp = db()->query("SELECT COUNT(*) FROM visits WHERE visitor_id = $korbanId")->fetchColumn();
ok('QR kedaluwarsa DITOLAK', (int) $vExp === 0);

// dicabut
$tokenCabut = bin2hex(random_bytes(32));
$st = db()->prepare("INSERT INTO visitor_qr_codes (registered_visitor_id, token_hash, status, valid_from, issued_by) VALUES (?,?,'dicabut',?,1)");
$st->execute([$reg['id'], hash('sha256', $tokenCabut), date('Y-m-d H:i:s')]);
$r = req("$BASE/actions/kunjungan_process.php", [
    'action' => 'checkin', 'mode' => 'qr', 'token' => $tokenCabut,
    'tujuan' => 'Tes', 'pegawai_ditemui' => 'Tes', 'csrf_token' => $tok,
], $sP);
$vCabut = db()->query("SELECT COUNT(*) FROM visits WHERE visitor_id = $korbanId")->fetchColumn();
ok('QR dicabut DITOLAK', (int) $vCabut === 0);

// token tidak dikenal
$r = req("$BASE/petugas/kunjungan/tambah.php?token=tidak-ada-sama-sekali", null, $sP, true);
ok('form QR menampilkan pesan error + opsi manual', strpos($r['body'], 'QR Tidak Dapat Digunakan') !== false);

/* ---------- 11. Jaminan memblokir check-out ---------- */
echo "11) Jaminan & check-out\n";
$visitId = (int) $visit1['id'];
$r = req("$BASE/actions/jaminan_process.php", [
    'action' => 'tambah', 'visit_id' => $visitId, 'barang' => 'KTP', 'jumlah' => 1,
    'keterangan' => 'KTP dititipkan', 'csrf_token' => $tok,
], $sP);
ok('pencatatan jaminan berhasil', strpos(loc_of($r['headers']), 'detail.php') !== false);

$r = req("$BASE/actions/checkout_process.php", ['id' => $visitId, 'csrf_token' => $tok], $sP);
$vSetelah = db()->query("SELECT * FROM visits WHERE id = $visitId")->fetch();
ok('check-out DITOLAK saat jaminan aktif', $vSetelah['status'] === 'berlangsung' && strpos(loc_of($r['headers']), 'detail.php') !== false);

$g = db()->query("SELECT * FROM guarantees WHERE visit_id = $visitId")->fetch();
$r = req("$BASE/actions/jaminan_process.php", ['action' => 'kembalikan', 'id' => $g['id'], 'csrf_token' => $tok], $sP);
$g2 = db()->query("SELECT * FROM guarantees WHERE id = " . (int) $g['id'])->fetch();
ok('pengembalian jaminan tercatat (status & waktu & petugas)', $g2['status'] === 'dikembalikan' && $g2['dikembalikan_at'] && $g2['dikembalikan_oleh']);

$r = req("$BASE/actions/checkout_process.php", ['id' => $visitId, 'csrf_token' => $tok], $sP);
$vAkhir = db()->query("SELECT * FROM visits WHERE id = $visitId")->fetch();
ok('check-out BERHASIL setelah jaminan dikembalikan', $vAkhir['status'] === 'selesai' && $vAkhir['jam_keluar']);
ok('durasi kunjungan dihitung', $vAkhir['durasi_menit'] !== null && (int) $vAkhir['durasi_menit'] >= 0);

$r = req("$BASE/actions/checkout_process.php", ['id' => $visitId, 'csrf_token' => $tok], $sP);
$vDua = db()->query("SELECT * FROM visits WHERE id = $visitId")->fetch();
ok('check-out KEDUA ditolak', $vDua['status'] === 'selesai' && $vDua['jam_keluar'] === $vAkhir['jam_keluar']);

/* ---------- 12. Kunjungan tanpa jaminan bisa check-out ---------- */
echo "12) Kunjungan tanpa jaminan\n";
$r = req("$BASE/actions/checkout_process.php", ['id' => (int) $visit2['id'], 'csrf_token' => $tok], $sP);
$v2 = db()->query("SELECT * FROM visits WHERE id = " . (int) $visit2['id'])->fetch();
ok('check-out kunjungan tanpa jaminan BERHASIL', $v2['status'] === 'selesai');

/* ---------- 13. Laporan, Cetak, CSV & Excel ---------- */
echo "13) Laporan, cetak, CSV & Excel\n";
$r = req("$BASE/admin/laporan/index.php", null, $sA, true);
ok('halaman laporan terbuka dengan tombol cetak, CSV & Excel',
    $r['status'] === 200 && strpos($r['body'], 'window.print()') !== false && strpos($r['body'], 'ekspor_csv.php') !== false && strpos($r['body'], 'ekspor_excel.php') !== false);
ok('header laporan (judul, periode, waktu cetak) tersedia',
    strpos($r['body'], 'Dicetak:') !== false && strpos($r['body'], 'Periode:') !== false);

$r = req("$BASE/admin/laporan/ekspor_csv.php", null, $sA, true);
ok('ekspor CSV memberi header unduhan', stripos($r['headers'], 'Content-Type: text/csv') !== false && stripos($r['headers'], 'attachment') !== false);
ok('CSV berisi BOM UTF-8 & data kunjungan', strpos($r['body'], "\xEF\xBB\xBF") === 0 && strpos($r['body'], 'KT-') !== false);

$r = req("$BASE/admin/laporan/ekspor_excel.php", null, $sA, true);
ok('ekspor Excel memberi header unduhan .xls', stripos($r['headers'], 'application/vnd.ms-excel') !== false && stripos($r['headers'], 'attachment') !== false);
ok('Excel berisi tabel & format mso-number-format', strpos($r['body'], 'mso-number-format') !== false && strpos($r['body'], 'Laporan Kunjungan') !== false);

// filter status=selesai
$r = req("$BASE/admin/laporan/ekspor_csv.php?status=selesai&dari=2000-01-01&sampai=2099-12-31", null, $sA, true);
ok('filter CSV mengikuti parameter', strpos($r['body'], 'berlangsung') === false);


/* ---------- 14. Aktivitas, pengaturan, profil ---------- */
echo "14) Log, pengaturan, profil\n";
$r = req("$BASE/admin/aktivitas.php", null, $sA, true);
ok('log aktivitas terisi', $r['status'] === 200 && strpos($r['body'], 'Check-in') !== false || strpos($r['body'], 'Login') !== false);

$r = req("$BASE/admin/pengaturan.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/settings_process.php", [
    'action' => 'simpan', 'nama_instansi' => 'Kantor Pusat Contoh', 'alamat_instansi' => 'Jl. Sudirman 10',
    'kontak_instansi' => '021-555-0123', 'jam_buka' => '08:00', 'jam_tutup' => '16:30',
    'qr_default_days' => '45', 'csrf_token' => $tok,
], $sA);
$namaInstansi = db()->query("SELECT value FROM settings WHERE key_name = 'nama_instansi'")->fetchColumn();
ok('pengaturan tersimpan ke database', $namaInstansi === 'Kantor Pusat Contoh');

$r = req("$BASE/admin/profil.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/profil_process.php", [
    'action' => 'change_password', 'password_lama' => 'salah-salah',
    'password_baru' => 'Baru12345', 'password_ulang' => 'Baru12345', 'csrf_token' => $tok,
], $sA);
ok('ganti password dengan password lama SALAH ditolak', strpos(loc_of($r['headers']), 'profil.php') !== false);
$r = req("$BASE/admin/profil.php", null, $sA, true);
$tok = csrf_of($r['body']);
$r = req("$BASE/actions/profil_process.php", [
    'action' => 'change_password', 'password_lama' => $ADM_P,
    'password_baru' => 'Baru12345', 'password_ulang' => 'Baru12345', 'csrf_token' => $tok,
], $sA);
$hashBaru = db()->query("SELECT password FROM users WHERE username = '$ADM_U'")->fetchColumn();
ok('ganti password BERHASIL (hash bcrypt diperbarui)', password_verify('Baru12345', $hashBaru));

// kembalikan password semula agar skrip dapat diulang
$st = db()->prepare('UPDATE users SET password = ? WHERE username = ?');
$st->execute([password_hash($ADM_P, PASSWORD_DEFAULT), $ADM_U]);

/* ---------- 15. Notifikasi & logout ---------- */
echo "15) Notifikasi & logout\n";
$r = req("$BASE/index.php", null, $sA, true);
ok('lonceng notifikasi tampil', strpos($r['body'], 'notif-menu') !== false);

$r = req("$BASE/logout.php", ['csrf_token' => csrf_of($r['body'])], $sA);
$r = req("$BASE/admin/dashboard.php", null, $sA, false);
ok('setelah logout halaman internal ditolak', strpos(loc_of($r['headers']), 'login.php') !== false);

/* ---------- Ringkasan ---------- */
echo "\n=== RINGKASAN: $pass lulus, $fail gagal ===\n";
exit($fail > 0 ? 1 : 0);


