<?php
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';
?>
<!DOCTYPE html>
<html lang="en">
    <title>Qrcode Generator</title>
    <head>
    <?php include './includes/head.php'; ?>
    </head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed layout-footer-fixed">
<div class="wrapper">
  <!-- Navbar -->
  <?php include './includes/navbar.php'; ?>
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <?php include './includes/sidebar.php'; ?>
  <!-- /.Main Sidebar Container -->

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
              <h1 class="m-0 text-dark">Scan a qr code</h1>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Scan from camera or upload an image</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">Decoding happens entirely in your browser - no image is uploaded to the server.</p>

                    <button type="button" id="start_camera_btn" class="btn btn-primary mb-3">
                        <i class="fa fa-camera"></i> Start camera
                    </button>
                    <button type="button" id="stop_camera_btn" class="btn btn-secondary mb-3" style="display:none;">
                        <i class="fa fa-stop"></i> Stop camera
                    </button>

                    <div id="camera_reader" style="max-width: 500px;"></div>

                    <div class="form-group mt-3">
                        <label for="qr_image_input">...or upload an image</label>
                        <input type="file" id="qr_image_input" accept="image/*" class="form-control">
                    </div>

                    <div id="scan_result_wrapper" class="mt-3" style="display:none;">
                        <label>Decoded content</label>
                        <div class="input-group">
                            <input type="text" id="scan_result" class="form-control" readonly>
                            <div class="input-group-append">
                                <button type="button" id="copy_result_btn" class="btn btn-outline-secondary"><i class="fa fa-copy"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!--/. container-fluid -->
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->

<!-- Footer and scripts -->
<?php include './includes/footer.php'; ?>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
    (function () {
        var resultInput = document.getElementById('scan_result');
        var resultWrapper = document.getElementById('scan_result_wrapper');
        var html5QrCode = new Html5Qrcode('camera_reader');
        var cameraRunning = false;

        function showResult(text) {
            resultInput.value = text;
            resultWrapper.style.display = '';
        }

        document.getElementById('start_camera_btn').addEventListener('click', function () {
            var startBtn = this;
            var stopBtn = document.getElementById('stop_camera_btn');

            html5QrCode.start(
                { facingMode: 'environment' },
                { fps: 10, qrbox: 250 },
                function (decodedText) {
                    showResult(decodedText);
                }
            ).then(function () {
                cameraRunning = true;
                startBtn.style.display = 'none';
                stopBtn.style.display = '';
            }).catch(function (err) {
                alert('Could not start the camera: ' + err);
            });
        });

        document.getElementById('stop_camera_btn').addEventListener('click', function () {
            var startBtn = document.getElementById('start_camera_btn');
            var stopBtn = this;

            if (!cameraRunning) {
                return;
            }

            html5QrCode.stop().then(function () {
                cameraRunning = false;
                startBtn.style.display = '';
                stopBtn.style.display = 'none';
            });
        });

        document.getElementById('qr_image_input').addEventListener('change', function (event) {
            var file = event.target.files[0];
            if (!file) {
                return;
            }

            html5QrCode.scanFile(file, false)
                .then(function (decodedText) {
                    showResult(decodedText);
                })
                .catch(function (err) {
                    alert('Could not find a qr code in this image: ' + err);
                });
        });

        document.getElementById('copy_result_btn').addEventListener('click', function () {
            navigator.clipboard.writeText(resultInput.value).catch(function () {
                resultInput.select();
                document.execCommand('copy');
            });
        });
    })();
</script>
</body>
</html>
