<?php
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';
?>
<!DOCTYPE html>
<html lang="en">
    <title>About - QRForge</title>
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
              <h1 class="m-0 text-dark">About</h1>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">
            <div class="card card-primary">
                <div class="card-body text-center">
                    <img src="dist/img/brand/logo-stacked.svg" alt="QRForge" style="max-width: 220px; margin: 20px 0;">
                    <p class="text-muted">Self-hosted static and dynamic QR code generator. Version 3.0.</p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Commercial version</h3>
                </div>
                <div class="card-body">
                    <p>
                        This is the free, open-source (MIT) edition of QRForge. It runs unmodified
                        as a live, fully functional try-out at
                        <a href="https://qr.ensembia.com" target="_blank">qr.ensembia.com</a> -
                        <a href="https://qr.ensembia.com/register.php" target="_blank">register your
                        own free account</a> there (email + a self-hosted CAPTCHA, no third-party
                        service).
                    </p>
                    <p>
                        The commercial VIP edition (paid create-rights and logo-embedded QR codes)
                        is a separate product built on the same OSS core - see
                        <a href="https://www.qrforge.eu" target="_blank">www.qrforge.eu</a> for
                        pricing and details.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Self-hosting</h3>
                </div>
                <div class="card-body">
                    <p>
                        Prefer to run your own instance? QRForge is MIT-licensed and available on
                        GitHub:
                        <a href="https://github.com/dillardblom/QRForge-selfhosted" target="_blank">github.com/dillardblom/QRForge-selfhosted</a>.
                    </p>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Credits</h3>
                </div>
                <div class="card-body">
                    <p>
                        Originally forked from
                        <a href="https://github.com/giandonatoinverso/PHP-Dynamic-Qr-code" target="_blank">PHP Qrcode Generator by Giandonato Inverso</a>.
                    </p>
                    <p>
                        QR code rendering is powered by the
                        <a href="https://github.com/chillerlan/php-qrcode" target="_blank">chillerlan/php-qrcode</a>
                        library.
                    </p>
                </div>
            </div>
        </div><!--/. container-fluid -->
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->

<!-- Footer and scripts -->
<?php include './includes/footer.php'; ?>
</body>
</html>
