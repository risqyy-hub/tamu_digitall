<?php
/**
 * setup/create_admin.php  (CLI)
 * Membuat / mereset akun admin atau petugas dengan password hash bcrypt.
 *
 * Cara pakai:
 *   php setup/create_admin.php
 *   php setup/create_admin.php --username=admin --role=admin
 *   php setup/create_admin.php --username=admin --password=Rahasia1! --nama="Admin Utama"
 *   php setup/create_admin.php --username=admin --password=Rahasia1! --reset   # reset password
 *
 * Skrip ini TIDAK bisa dijalankan dari browser.
 */

if (PHP_SAPI !== 'cli') {
    exit('Skrip ini hanya dapat dijalankan dari terminal (CLI).');
}

require_once __DIR__ . '/../includes/functions.php';

/* ---------- Baca argumen ---------- */
$opts = [
    'username' => '',
    'password' => '',
    'nama'     => '',
    'email'    => '',
    'role'     => 'admin',
    'reset'    => false,
];

foreach (array_slice($argv, 1) as $arg) {
    if ($arg === '--reset') {
        $opts['reset'] = true;
        continue;
    }
    if (preg_match('/^--([a-z]+)=(.*)$/', $arg, $m) && array_key_exists($m[1], $opts)) {
        $opts[$m[1]] = trim($m[2]);
    }
}

/* ---------- Interaktif bila argumen kurang ---------- */
function prompt(string $label, string $default = ''): string
{
    $suffix = $default !== '' ? " [$default]" : '';
    echo $label . $suffix . ': ';
    $line = trim((string) fgets(STDIN));
    return $line !== '' ? $line : $default;
}

if ($opts['username'] === '') {
    echo "=== Pembuatan Akun Buku Tamu Digital ===\n";
    $opts['username'] = prompt('Username');
}
if ($opts['username'] === '') {
    exit("Username wajib diisi. Batal.\n");
}

if ($opts['password'] === '') {
    $opts['password'] = prompt('Password (min. 8 karakter)');
}
if ($opts['password'] === '' || strlen($opts['password']) < 8) {
    exit("Password wajib diisi, minimal 8 karakter. Batal.\n");
}

if ($opts['nama'] === '') {
    $opts['nama'] = prompt('Nama lengkap', 'Administrator');
}
if ($opts['email'] === '') {
    $opts['email'] = prompt('Email (opsional)', '');
}
$opts['role'] = in_array($opts['role'], ['admin', 'petugas'], true) ? $opts['role'] : 'admin';

/* ---------- Proses ke database ---------- */
try {
    $pdo = db();

    $st = $pdo->prepare('SELECT id, role FROM users WHERE username = ?');
    $st->execute([$opts['username']]);
    $existing = $st->fetch() ?: null;

    $hash = password_hash($opts['password'], PASSWORD_DEFAULT);

    if ($existing && !$opts['reset']) {
        exit("Username '{$opts['username']}' sudah terdaftar. Gunakan --reset untuk mengganti password.\n");
    }

    if ($existing) {
        $st = $pdo->prepare("UPDATE users SET password = ?, status = 'aktif' WHERE id = ?");
        $st->execute([$hash, $existing['id']]);
        $userId = (int) $existing['id'];
        echo "Password akun '{$opts['username']}' berhasil direset.\n";
    } else {
        $st = $pdo->prepare(
            "INSERT INTO users (nama, username, email, password, role, status) VALUES (?,?,?,?,?,'aktif')"
        );
        $st->execute([
            $opts['nama'] !== '' ? $opts['nama'] : $opts['username'],
            $opts['username'],
            $opts['email'] !== '' ? $opts['email'] : null,
            $hash,
            $opts['role'],
        ]);
        $userId = (int) $pdo->lastInsertId();
        echo "Akun '{$opts['username']}' (role: {$opts['role']}) berhasil dibuat.\n";
    }

    $st = $pdo->prepare(
        'INSERT INTO activity_logs (user_id, username, role, aktivitas, ip_address) VALUES (?,?,?,?,?)'
    );
    $st->execute([$userId, $opts['username'], $opts['role'], 'Setup akun via CLI', '127.0.0.1']);

    echo "Login: http://localhost/" . basename(APP_ROOT) . "/login.php\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Gagal: " . $e->getMessage() . "\n");
    exit(1);
}
