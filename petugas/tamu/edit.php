<?php
/**
 * petugas/tamu/edit.php - Halaman wrapper, implementasi ada di includes/modules/tamu/edit.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('petugas');

$AREA = 'petugas';
require __DIR__ . '/../../includes/modules/tamu/edit.php';
