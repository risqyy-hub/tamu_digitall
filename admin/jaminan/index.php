<?php
/**
 * admin/jaminan/index.php - Halaman wrapper, implementasi ada di includes/modules/jaminan/index.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$AREA = 'admin';
require __DIR__ . '/../../includes/modules/jaminan/index.php';
