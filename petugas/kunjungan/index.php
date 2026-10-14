<?php
/**
 * petugas/kunjungan/index.php - Halaman wrapper, implementasi ada di includes/modules/kunjungan/index.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../../includes/modules/kunjungan/index.php';
