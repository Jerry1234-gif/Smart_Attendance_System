<?php
require_once __DIR__ . '/includes/guard.php';
require_role('student');
render_header('Scan attendance QR', 'Point your camera at the QR code displayed by your lecturer.');
?>
<section class="content-grid two-col align-start"><article class="panel scanner-panel"><div class="panel-heading"><div><h2>QR code scanner</h2><p>Allow camera access when your browser asks.</p></div><span class="feature-icon">▦</span></div><div id="qr-reader" class="camera-box"></div><div id="scanner-status" class="alert alert-info">Preparing camera scanner…</div><div class="panel-actions"><button id="start-scan" type="button" class="button button-primary">Start camera</button><button id="stop-scan" type="button" class="button button-outline" disabled>Stop camera</button></div><p class="tiny-note">Camera scanning normally requires HTTPS on a real phone. It is permitted on localhost during local development.</p></article>
<aside class="panel"><h2>Camera not working?</h2><p class="muted">You can still record attendance with the six-digit backup code provided by your lecturer.</p><form action="<?= e(app_url('checkin.php')) ?>" method="post" class="form-stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><label>Attendance code<input class="code-input" type="text" name="attendance_code" inputmode="numeric" minlength="6" maxlength="6" pattern="[0-9]{6}" required placeholder="000000"></label><button class="button button-primary button-wide" type="submit">Submit code</button></form><div class="alert alert-info">You must be enrolled in the course and signed in as a student. A student can check in only once for each session.</div></aside></section>
<script src="<?= e(app_config('scanner_library_url')) ?>"></script><script>
(() => {
 const statusBox = document.getElementById('scanner-status');
 const startButton = document.getElementById('start-scan');
 const stopButton = document.getElementById('stop-scan');
 let scanner = null;
 const show = (message, type='info') => { statusBox.textContent = message; statusBox.className = 'alert alert-' + type; };
 startButton.addEventListener('click', async () => {
   if (typeof Html5Qrcode === 'undefined') { show('The camera scanning library could not load. Use the attendance-code box instead.', 'warning'); return; }
   try {
     scanner = scanner || new Html5Qrcode('qr-reader');
     await scanner.start({facingMode:'environment'}, {fps:10, qrbox:{width:230,height:230}}, (decodedText) => {
       try {
         const url = new URL(decodedText, window.location.origin);
         const expectedPath = <?= json_encode(parse_url(app_url('checkin.php'), PHP_URL_PATH)) ?>;
         if (url.origin !== window.location.origin || !url.pathname.endsWith('/checkin.php') || !url.searchParams.get('token')) {
           show('This QR code does not belong to this attendance application. Scan the code shown by your lecturer.', 'danger');
           return;
         }
         show('QR code recognised. Opening your attendance check-in…', 'success');
         scanner.stop().finally(() => window.location.href = url.toString());
       } catch (error) { show('The QR code could not be read as a valid check-in link.', 'danger'); }
     }, () => {});
     startButton.disabled = true; stopButton.disabled = false; show('Camera is active. Hold the QR code inside the scanning box.', 'success');
   } catch (error) {
     show('Camera could not start. Check browser permission and use HTTPS on a real phone, or enter the manual code.', 'warning');
   }
 });
 stopButton.addEventListener('click', async () => {
   if (scanner && scanner.isScanning) await scanner.stop();
   startButton.disabled = false; stopButton.disabled = true; show('Camera stopped. Press Start camera when you are ready.', 'info');
 });
 if (typeof Html5Qrcode === 'undefined') show('Camera scanning library not available. The manual code method is ready below.', 'warning');
})();
</script>
<?php render_footer(); ?>
