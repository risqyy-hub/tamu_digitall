<?php
/**
 * admin/profil.php - Halaman profil (dipakai admin & petugas; implementasi di includes/modules/profil.php)
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('petugas');

$AREA = 'admin';
require __DIR__ . '/../includes/modules/profil.php';
