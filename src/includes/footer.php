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