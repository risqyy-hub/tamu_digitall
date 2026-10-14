<?php
/**
 * actions/profil_process.php
 * Perubahan data diri & password pengguna login.
 */
require_once __DIR__ . '/../includes/functions.php';

require_login();
require_post();
verify_csrf();

$action = req_str('action');
$pdo    = db();
$user   = current_user();
$back   = is_admin() ? 'admin/profil.php' : 'petugas/profil.php';

/* ================= UBAH DATA DIRI ================= */
if ($action === 'update_profile') {
    $nama    = req_str('nama');
    $email   = req_str('email');
    $telepon = req_str('telepon');

    if (mb_strlen($nama) < 3) {
        flash_set('warning', 'Nama wajib diisi minimal 3 karakter.');
        redirect($back);
    }
    if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        flash_set('warning', 'Format email tidak valid.');
        redirect($back);
    }
    if ($email !== '' && $email !== $user['email']) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $st->execute([$email, $user['id']]);
        if ($st->fetch()) {
            flash_set('warning', 'Email sudah dipakai akun lain.');
            redirect($back);
        }
    }

    $st = $pdo->prepare('UPDATE users SET nama = ?, email = ?, telepon = ? WHERE id = ?');
    $st->execute([$nama, $email ?: null, $telepon ?: null, $user['id']]);

    log_aktivitas('Memperbarui profil sendiri');
    flash_set('success', 'Profil berhasil diperbarui.');
    redirect($back);
}

/* ================= GANTI PASSWORD ================= */
if ($action === 'change_password') {
    $lama  = isset($_POST['password_lama']) && is_string($_POST['password_lama']) ? $_POST['password_lama'] : '';
    $baru  = isset($_POST['password_baru']) && is_string($_POST['password_baru']) ? $_POST['password_baru'] : '';
    $ulang = isset($_POST['password_ulang']) && is_string($_POST['password_ulang']) ? $_POST['password_ulang'] : '';

    // Ambil hash password langsung dari database (current_user tidak memuat kolom password)
    $st = $pdo->prepare('SELECT password FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    $hash = (string) $st->fetchColumn();

    if ($lama === '' || !password_verify($lama, $hash)) {
        flash_set('danger', 'Password lama salah.');
        redirect($back);
    }
    if (strlen($baru) < 8) {
        flash_set('warning', 'Password baru minimal 8 karakter.');
        redirect($back);
    }
    if ($baru !== $ulang) {
        flash_set('warning', 'Konfirmasi password baru tidak sama.');
        redirect($back);
    }

    $st = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
    $st->execute([password_hash($baru, PASSWORD_DEFAULT), $user['id']]);

    log_aktivitas('Mengganti password sendiri');
    flash_set('success', 'Password berhasil diganti. Gunakan password baru saat login berikutnya.');
    redirect($back);
}

flash_set('danger', 'Aksi tidak dikenal.');
redirect($back);
