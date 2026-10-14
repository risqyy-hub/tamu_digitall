<?php
/**
 * petugas/tamu/index.php - Halaman wrapper, implementasi ada di includes/modules/tamu/index.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../../includes/modules/tamu/index.php';
