<?php
/**
 * petugas/profil.php - Halaman profil petugas (implementasi di includes/modules/profil.php)
 */
require_once __DIR__ . '/../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../includes/modules/profil.php';
