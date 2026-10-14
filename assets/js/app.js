/**
 * assets/js/app.js
 * Skrip global Buku Tamu Digital.
 * Catatan: JavaScript hanya pelengkap UX; keamanan tetap divalidasi di server.
 */

document.addEventListener('DOMContentLoaded', function () {

  /* --- Default SweetAlert2 --- */
  if (window.Swal) {
    Swal.mixin({
      buttonsStyling: false,
      customClass: {
        confirmButton: 'btn btn-navy mx-1',
        cancelButton:  'btn btn-outline-secondary mx-1'
      }
    });
  }

  /* --- Auto-dismiss alert bootstrap --- */
  document.querySelectorAll('.alert-dismissible').forEach(function (el) {
    setTimeout(function () {
      try { bootstrap.Alert.getOrCreateInstance(el).close(); } catch (e) {}
    }, 6000);
  });

  /* --- Konfirmasi aksi penting via SweetAlert2 --- */
  document.querySelectorAll('form[data-confirm]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      if (form.dataset.confirmDone === '1') return; // lolos setelah konfirmasi
      e.preventDefault();
      var pesan = form.dataset.confirm || 'Lanjutkan aksi ini?';
      var judul = form.dataset.confirmTitle || 'Konfirmasi';
      if (window.Swal) {
        Swal.fire({
          title: judul,
          text: pesan,
          icon: 'question',
          showCancelButton: true,
          confirmButtonText: 'Ya, lanjutkan',
          cancelButtonText: 'Batal'
        }).then(function (r) {
          if (r.isConfirmed) {
            form.dataset.confirmDone = '1';
            form.submit();
          }
        });
      } else if (confirm(pesan)) {
        form.dataset.confirmDone = '1';
        form.submit();
      }
    });
  });

  /* --- Ajax: tandai request sebagai XMLHttpRequest --- */
  document.addEventListener('ajaxSend', function (e, xhr) {
    try { xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest'); } catch (err) {}
  });

  /* --- Durasi berjalan kunjungan aktif (diperbarui tiap 30 detik) --- */
  function tickDurasi() {
    document.querySelectorAll('[data-durasi-start]').forEach(function (el) {
      var start = new Date(el.getAttribute('data-durasi-start')).getTime();
      if (isNaN(start)) return;
      var menit = Math.max(0, Math.floor((Date.now() - start) / 60000));
      var j = Math.floor(menit / 60), m = menit % 60;
      el.textContent = j > 0 ? j + ' jam ' + m + ' menit' : m + ' menit';
    });
  }
  tickDurasi();
  setInterval(tickDurasi, 30000);

  /* --- Chart.js: font global konsisten --- */
  if (window.Chart) {
    Chart.defaults.font.family = '"Inter", "Segoe UI", system-ui, sans-serif';
    Chart.defaults.color = '#5E6E86';
  }
});
