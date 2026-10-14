<?php
/**
 * includes/footer.php
 * Penutup layout + pustaka JavaScript lokal (assets/vendor).
 */
if (!defined('APP_FUNCTIONS_LOADED')) {
    exit('Akses langsung dilarang.');
}
?>
  </div><!-- /.container-fluid -->
</main>

<footer class="app-footer text-center text-muted small py-3 border-top bg-white">
  &copy; <?= date('Y') ?> <?= e(setting('nama_instansi', APP_NAME)) ?> &middot; Sistem Informasi Buku Tamu Digital
</footer>

</div><!-- /.app-wrapper -->

<script src="<?= e(asset('vendor/bootstrap/js/bootstrap.bundle.min.js')) ?>"></script>
<script src="<?= e(asset('vendor/sweetalert2/sweetalert2.all.min.js')) ?>"></script>
<script src="<?= e(asset('js/app.js')) ?>"></script>
</body>
</html>
