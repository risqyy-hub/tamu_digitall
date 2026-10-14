<?php
/**
 * admin/tamu/detail.php - Halaman wrapper, implementasi ada di includes/modules/tamu/detail.php
 */
require_once __DIR__ . '/../../includes/functions.php';
require_role('admin');

$AREA = 'admin';
require __DIR__ . '/../../includes/modules/tamu/detail.php';
