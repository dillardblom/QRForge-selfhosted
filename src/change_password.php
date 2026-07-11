<?php
require_once 'includes/bootstrap.php';

if (empty($_SESSION['user_logged_in'])) {
    header('Location: login.php');
    exit;
}

$forced = !empty($_SESSION['must_change_password']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    $db = getDbInstance();
    $db->where('id', $_SESSION['user_id']);
    $user = $db->getOne('users');

    if ($user === NULL || !password_verify($current_password, $user['password'])) {
        $_SESSION['failure'] = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 10) {
        $_SESSION['failure'] = 'New password must be at least 10 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $_SESSION['failure'] = 'New password and confirmation do not match.';
    } elseif ($new_password === $current_password) {
        $_SESSION['failure'] = 'New password must be different from the current password.';
    } else {
        $db = getDbInstance();
        $db->where('id', $_SESSION['user_id']);
        $db->update('users', [
            'password' => password_hash($new_password, PASSWORD_DEFAULT),
            'must_change_password' => 0,
            'password_changed_at' => date('Y-m-d H:i:s'),
        ]);

        $_SESSION['must_change_password'] = false;
        audit_log('password_changed');

        $_SESSION['success'] = 'Password updated successfully.';
        header('Location: index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<title>Change password - QRForge</title>
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
            ? 'You must change your password before continuing.'
            : 'Change your password'; ?>
      </p>

      <?php include './includes/flash_messages.php'; ?>

      <form method="POST" action="change_password.php">
        <?php echo csrf_field(); ?>
        <div class="input-group mb-3">
          <input type="password" name="current_password" class="form-control" placeholder="Current password" required="required" autocomplete="current-password">
        </div>
        <div class="input-group mb-3">
          <input type="password" name="new_password" class="form-control" placeholder="New password (min. 10 characters)" required="required" minlength="10" autocomplete="new-password">
        </div>
        <div class="input-group mb-3">
          <input type="password" name="confirm_password" class="form-control" placeholder="Confirm new password" required="required" minlength="10" autocomplete="new-password">
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Update password</button>
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
