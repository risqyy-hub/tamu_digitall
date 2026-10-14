<?php
/**
 * includes/header.php
 * Bagian <head> + pembuka layout (navbar & sidebar).
 * Variabel yang dipakai: $page_title, $active_menu
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}
$page_title  = $page_title ?? APP_NAME;
$active_menu = $active_menu ?? '';
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($page_title) ?> &middot; <?= e(setting('nama_instansi', APP_NAME)) ?></title>
<link rel="stylesheet" href="<?= e(asset('vendor/bootstrap/css/bootstrap.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('vendor/bootstrap-icons/css/bootstrap-icons.min.css')) ?>">
<link rel="stylesheet" href="<?= e(asset('css/style.css')) ?>">
</head>
<body class="app-body">
<div class="app-wrapper">

<?php require __DIR__ . '/navbar.php'; ?>
<?php require __DIR__ . '/sidebar.php'; ?>

<main class="app-main">
  <div class="container-fluid px-3 px-lg-4 py-3">

  <?php foreach (flash_all() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
      <i class="bi <?= e([
          'success' => 'bi-check-circle-fill',
          'danger'  => 'bi-exclamation-triangle-fill',
          'warning' => 'bi-exclamation-circle-fill',
          'info'    => 'bi-info-circle-fill',
      ][$f['type']] ?? 'bi-info-circle-fill') ?> me-1"></i>
      <?= e($f['message']) ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
    </div>
  <?php endforeach; ?>
