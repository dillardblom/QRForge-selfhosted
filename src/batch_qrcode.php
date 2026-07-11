<?php
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';
require_once BASE_PATH . '/lib/DynamicQrcode/DynamicQrcode.php';

if ($_SESSION['type'] === 'user') {
    $_SESSION['failure'] = 'The "user" role is read-only and cannot create qr codes.';
    header('Location: index.php');
    exit;
}

$dynamic_qrcode_instance = new DynamicQrcode();

$results = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    $id_owner = $_SESSION['type'] === 'super' ? ($_POST['id_owner'] ?? '') : $_SESSION['user_id'];

    if (!isset($_FILES['csv_file']) || $_FILES['csv_file']['error'] !== UPLOAD_ERR_OK) {
        $_SESSION['failure'] = 'Please choose a CSV file to upload.';
        header('Location: batch_qrcode.php');
        exit;
    }

    $handle = fopen($_FILES['csv_file']['tmp_name'], 'r');
    $rows = [];
    if ($handle !== false) {
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = $row;
        }
        fclose($handle);
    }

    // Skip an optional header row.
    if (!empty($rows) && strtolower(trim($rows[0][0] ?? '')) === 'filename') {
        array_shift($rows);
    }

    $successes = [];
    $failures = [];
    $created_ids = [];

    foreach ($rows as $index => $row) {
        $line_number = $index + 1;
        $filename = $row[0] ?? '';
        $link = $row[1] ?? '';

        $result = $dynamic_qrcode_instance->addQrcodeBatchRow($filename, $link, $id_owner);

        if ($result['ok']) {
            $successes[] = $filename;
            $created_ids[] = $result['id'];
        } else {
            $failures[] = ['line' => $line_number, 'filename' => $filename, 'error' => $result['error']];
        }
    }

    $zip_filename = null;

    if (!empty($created_ids)) {
        $db = getDbInstance();
        $files = [];

        foreach ($created_ids as $id) {
            $db->where('id', $id);
            $row = $db->getOne('dynamic_qrcodes');
            if ($row !== null) {
                $files[] = SAVED_QRCODE_DIRECTORY . $row['qrcode'];
            }
        }

        $zip_filename = 'qrcodes_' . uniqid() . '.zip';
        $zip_path = SAVED_QRCODE_DIRECTORY . 'zip/' . $zip_filename;
        @unlink($zip_path);

        $zip = new ZipArchive();
        $zip->open($zip_path, ZipArchive::CREATE);
        foreach ($files as $file) {
            $content = @file_get_contents($file);
            if ($content !== false) {
                $zip->addFromString(basename($file), $content);
            }
        }
        $zip->close();

        $_SESSION['generated_zips'][] = $zip_filename;

        audit_log('batch_qrcode_created', 'dynamic_qrcodes', implode(',', $created_ids));
    }

    $results = [
        'successes' => $successes,
        'failures' => $failures,
        'zip_filename' => $zip_filename,
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
    <title>QRForge</title>
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
              <h1 class="m-0 text-dark">Batch-create dynamic qr codes</h1>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>
    <!-- /.content-header -->

    <!-- Flash messages -->
    <?php include BASE_PATH.'/includes/flash_messages.php'; ?>
    <!-- /.Flash messages -->

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <?php if ($results !== null): ?>
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Result</h3>
                </div>
                <div class="card-body">
                    <p><strong><?php echo count($results['successes']); ?></strong> qr code(s) created,
                       <strong><?php echo count($results['failures']); ?></strong> row(s) failed.</p>

                    <?php if ($results['zip_filename']): ?>
                    <a href="qrcode_zip_download.php?file=<?php echo rawurlencode($results['zip_filename']); ?>" class="btn btn-primary">
                        <i class="fa fa-download"></i> Download all as ZIP
                    </a>
                    <?php endif; ?>

                    <?php if (!empty($results['failures'])): ?>
                    <table class="table table-striped table-bordered mt-3">
                        <thead>
                            <tr>
                                <th>Line</th>
                                <th>Filename</th>
                                <th>Error</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($results['failures'] as $failure): ?>
                            <tr>
                                <td><?php echo (int) $failure['line']; ?></td>
                                <td><?php echo htmlspecialchars($failure['filename']); ?></td>
                                <td><?php echo htmlspecialchars($failure['error']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Upload a CSV file</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
<?php echo csrf_field(); ?>
                    <div class="card-body">
                        <p>The CSV needs two columns: <code>filename,link</code>. An optional header row
                           starting with "filename" is skipped automatically. Each row creates one dynamic
                           qr code (PNG, default colors/size) redirecting to the given link.</p>

                        <div class="form-group">
                            <label for="csv_file">CSV file</label>
                            <input type="file" name="csv_file" id="csv_file" accept=".csv,text/csv" required="required" class="form-control">
                        </div>

                        <?php if ($_SESSION['type'] === 'super'): ?>
                        <div class="form-group">
                            <label for="id_owner">Owner</label>
                            <select name="id_owner" class="form-control">
                                <option value="" selected>All</option>
                                <?php
                                require_once BASE_PATH . '/lib/Users/Users.php';
                                $users_instance = new Users();
                                $users = $users_instance->getAllUsers();
                                foreach ($users as $user) {
                                    ?>
                                    <option value="<?php echo $user["id"]; ?>"><?php echo htmlspecialchars($user["username"]); ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary">Upload and generate</button>
                    </div>
                </form>
            </div>

        </div><!--/. container-fluid -->
    </section><!-- /.content -->
  </div><!-- /.content-wrapper -->

<!-- Footer and scripts -->
<?php include './includes/footer.php'; ?>
</body>
</html>
