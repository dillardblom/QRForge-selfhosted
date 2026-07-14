<?php
require_once 'includes/bootstrap.php';

if (empty($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit;
}

$forced = !empty($_SESSION['must_set_email']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['failure'] = 'Please enter a valid email address.';
    } else {
        $db = getDbInstance();
        $db->where('email', $email);
        $db->where('id', $_SESSION['user_id'], '!=');
        $existing = $db->getOne('users');

        if (!empty($existing['email'])) {
            $_SESSION['failure'] = 'An account with this email already exists.';
        } else {
            $db = getDbInstance();
            $db->where('id', $_SESSION['user_id']);
            $db->update('users', [
                'email' => $email,
                'must_set_email' => 0,
            ]);

            $_SESSION['must_set_email'] = false;
            audit_log('email_set');

            $_SESSION['success'] = 'Email address saved.';
            header('Location: index.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<title>Set email - QRForge</title>
<?php include './includes/head.php'; ?>

<body class="login-page" style="min-height: 512.391px;">
    <div class="login-box">
  <div class="login-logo">
    <img src="dist/img/brand/logo.svg" alt="QRForge" style="max-width: 260px;">
  </div>

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">
        <?php echo $forced
            ? 'Please set an email address for your account before continuing. This will become your login.'
            : 'Set your email address'; ?>
      </p>

      <?php include './includes/flash_messages.php'; ?>

      <form method="POST" action="set_email.php">
        <?php echo csrf_field(); ?>
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email address" required="required">
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Save email</button>
          </div>
        </div>
      </form>

      <?php if (!$forced): ?>
        <p class="mt-3 text-center"><a href="index.php">Back to dashboard</a></p>
      <?php endif; ?>
    </div>
  </div>
</div>

<script src="../../plugins/jquery/jquery.min.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/adminlte.js"></script>

</body>
</html>
