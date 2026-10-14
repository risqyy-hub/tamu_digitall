/**
 * assets/js/scanner.js
 * Scanner QR via kamera perangkat menggunakan jsQR.
 * Izin kamera: browser hanya mengizinkan getUserMedia pada konteks aman
 * (https:// atau http://localhost). Di XAMPP: http://localhost/buku_tamu_digital/
 */

function initQrScanner(opts) {
  var video   = document.getElementById(opts.videoId || 'scanVideo');
  var canvas  = document.getElementById(opts.canvasId || 'scanCanvas');
  var status  = document.getElementById(opts.statusId || 'scanStatus');
  var btnStart = document.getElementById(opts.startId || 'scanStart');
  var btnStop  = document.getElementById(opts.stopId || 'scanStop');

  if (!video || !canvas) return null;

  var ctx = canvas.getContext('2d', { willReadFrequently: true });
  var stream = null;
  var rafId = null;
  var running = false;

  function setStatus(text, cls) {
    if (status) {
      status.className = 'alert py-2 ' + (cls || 'alert-info');
      status.innerHTML = text;
    }
  }

  function detectLoop() {
    if (!running) return;
    if (video.readyState === video.HAVE_ENOUGH_DATA) {
      canvas.width  = video.videoWidth;
      canvas.height = video.videoHeight;
      ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
      var imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
      var code = window.jsQR ? jsQR(imageData.data, imageData.width, imageData.height, {
        inversionAttempts: 'dontInvert'
      }) : null;

      if (code && code.data) {
        setStatus('<i class="bi bi-check-circle me-1"></i>QR terdeteksi! Memproses...', 'alert-success');
        stop();
        if (typeof opts.onResult === 'function') {
          opts.onResult(code.data);
        }
        return;
      }
    }
    rafId = requestAnimationFrame(detectLoop);
  }

  function stop() {
    running = false;
    if (rafId) cancelAnimationFrame(rafId);
    if (stream) {
      stream.getTracks().forEach(function (t) { t.stop(); });
      stream = null;
    }
    if (btnStart) btnStart.disabled = false;
    if (btnStop)  btnStop.disabled  = true;
  }

  function start() {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      setStatus('<i class="bi bi-exclamation-triangle me-1"></i>Perangkat/browser tidak mendukung akses kamera.', 'alert-danger');
      return;
    }
    setStatus('<i class="bi bi-hourglass-split me-1"></i>Meminta izin kamera...', 'alert-info');
    navigator.mediaDevices.getUserMedia({
      video: { facingMode: { ideal: 'environment' } },
      audio: false
    }).then(function (s) {
      stream = s;
      video.srcObject = s;
      video.setAttribute('playsinline', 'true');
      return video.play();
    }).then(function () {
      running = true;
      if (btnStart) btnStart.disabled = true;
      if (btnStop)  btnStop.disabled  = false;
      setStatus('<i class="bi bi-camera-video me-1"></i>Arahkan kamera ke QR Code tamu...', 'alert-info');
      detectLoop();
    }).catch(function (err) {
      var pesan = 'Gagal mengakses kamera.';
      if (err && (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError')) {
        pesan = 'Izin kamera ditolak. Izinkan kamera melalui ikon gembok di bilah alamat, lalu coba lagi.';
      } else if (err && err.name === 'NotFoundError') {
        pesan = 'Kamera tidak ditemukan di perangkat ini.';
      }
      setStatus('<i class="bi bi-exclamation-triangle me-1"></i>' + pesan, 'alert-danger');
    });
  }

  if (btnStart) btnStart.addEventListener('click', start);
  if (btnStop)  btnStop.addEventListener('click', function () {
    stop();
    setStatus('<i class="bi bi-stop-circle me-1"></i>Kamera dihentikan.', 'alert-secondary');
  });

  return { start: start, stop: stop };
}
