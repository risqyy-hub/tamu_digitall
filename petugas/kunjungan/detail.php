<?php
/**
 * petugas/kunjungan/detail.php - Halaman wrapper, implementasi ada di includes/modules/kunjungan/detail.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../../includes/modules/kunjungan/detail.php';
