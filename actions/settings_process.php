<?php
/**
 * actions/settings_process.php
 * Simpan konfigurasi aplikasi (admin).
 */
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');
require_post();
verify_csrf();

if (req_str('action') !== 'simpan') {
    flash_set('danger', 'Aksi tidak dikenal.');
    redirect('admin/pengaturan.php');
}

$namaInstansi   = req_str('nama_instansi');
$alamatInstansi = req_str('alamat_instansi');
$kontakInstansi = req_str('kontak_instansi');
$jamBuka        = req_str('jam_buka');
$jamTutup       = req_str('jam_tutup');
$qrDays         = req_int('qr_default_days', 30);

if ($namaInstansi === '') {
    flash_set('warning', 'Nama instansi wajib diisi.');
    redirect('admin/pengaturan.php');
}
if (!preg_match('/^\d{2}:\d{2}$/', $jamBuka)) {
    $jamBuka = '08:00';
}
if (!preg_match('/^\d{2}:\d{2}$/', $jamTutup)) {
    $jamTutup = '16:00';
}
if ($qrDays < 1 || $qrDays > 3650) {
    $qrDays = 30;
}

set_setting('nama_instansi', $namaInstansi);
set_setting('alamat_instansi', $alamatInstansi);
set_setting('kontak_instansi', $kontakInstansi);
set_setting('jam_buka', $jamBuka);
set_setting('jam_tutup', $jamTutup);
set_setting('qr_default_days', (string) $qrDays);

log_aktivitas('Memperbarui pengaturan aplikasi');
flash_set('success', 'Pengaturan aplikasi berhasil disimpan.');
redirect('admin/pengaturan.php');
