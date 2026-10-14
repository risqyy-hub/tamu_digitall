<?php
/**
 * petugas/tamu/tambah.php - Halaman wrapper, implementasi ada di includes/modules/tamu/tambah.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../../includes/modules/tamu/tambah.php';
