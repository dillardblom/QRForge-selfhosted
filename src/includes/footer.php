<!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->

  <!-- Main Footer -->
  <footer class="main-footer text-sm">
    <strong><a href="https://www.qrforge.eu" target="_blank">QRForge</a></strong> -
    <a href="./about.php">About</a> / credits
    <div class="float-right d-none d-sm-inline-block">
      <b>Version</b> 3.0
    </div>
  </footer>
</div>
<!-- ./wrapper -->

<!-- REQUIRED SCRIPTS -->
<!-- jQuery -->
<script src="plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap -->
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="dist/js/adminlte.js"></script>
<!-- AdminLTE Custom-->
<script src="dist/js/custom.js?nocache=<?php print rand();?>"></script>
<!-- Color picker -->
<script src="plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
<!-- date-range-picker -->
<script src="plugins/daterangepicker/daterangepicker.js"></script>
<!-- Overlay scrollbar -->
<script type="text/javascript" src="plugins/overlayScrollbars/js/OverlayScrollbars.js"></script>
<!-- PWA service worker -->
<script>
    if ('serviceWorker' in navigator) {
        navigator.serviceWorker.register('service-worker.js');
    }
</script>

<!--
    Idle-timeout warning: the session dies silently after
    <?php echo SESSION_IDLE_TIMEOUT; ?> seconds of inactivity (no PHP page
    load), which loses whatever form the user is filling in. This warns a
    couple of minutes before that happens and offers a "stay logged in"
    button that pings the server without navigating away.
-->
<div id="session-timeout-toast" class="toast" role="alert" aria-live="assertive" aria-atomic="true"
     style="position:fixed;bottom:20px;right:20px;z-index:2000;min-width:320px;display:none;">
    <div class="toast-header bg-warning">
        <i class="fa fa-clock mr-2"></i>
        <strong class="mr-auto">Session expiring soon</strong>
    </div>
    <div class="toast-body bg-white">
        <span id="session-timeout-message">You'll be logged out in a couple of minutes due to inactivity.</span>
        <div class="mt-2">
            <button type="button" id="session-timeout-extend" class="btn btn-sm btn-primary">Stay logged in</button>
        </div>
    </div>
</div>
<script>
(function () {
    var SESSION_IDLE_TIMEOUT = <?php echo (int) SESSION_IDLE_TIMEOUT; ?>;
    var WARNING_LEAD_TIME = 120; // show the warning this many seconds before expiry
    var toast = document.getElementById('session-timeout-toast');
    var message = document.getElementById('session-timeout-message');
    var extendBtn = document.getElementById('session-timeout-extend');
    var warnTimer = null;

    function showWarning() {
        toast.style.display = 'block';
    }

    function scheduleWarning() {
        clearTimeout(warnTimer);
        var delayMs = Math.max(0, (SESSION_IDLE_TIMEOUT - WARNING_LEAD_TIME) * 1000);
        warnTimer = setTimeout(showWarning, delayMs);
    }

    extendBtn.addEventListener('click', function () {
        fetch('session_ping.php', { method: 'GET', redirect: 'manual', credentials: 'same-origin' })
            .then(function (response) {
                // redirect: 'manual' turns a server-side redirect (session already
                // dead) into an opaque response instead of silently following it.
                if (response.type === 'opaqueredirect' || !response.ok) {
                    throw new Error('expired');
                }
                toast.style.display = 'none';
                scheduleWarning();
            })
            .catch(function () {
                message.textContent = 'Your session already expired - please copy any unsaved work before reloading.';
                extendBtn.style.display = 'none';
            });
    });

    scheduleWarning();
})();
</script>