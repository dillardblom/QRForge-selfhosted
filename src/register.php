<?php
require_once 'includes/bootstrap.php';
require_once 'lib/Users/Users.php';

if (!ALLOW_SELF_REGISTRATION) {
    header('Location: login.php');
    exit;
}

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === TRUE) {
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify_or_die();

    $email = trim($_POST['email'] ?? '');

    if (!captcha_is_valid($_POST['captcha'] ?? '')) {
        $_SESSION['failure'] = 'Incorrect CAPTCHA answer, please try again.';
    } else {
        $users = new Users();
        $result = $users->registerSelfUser($email);

        if ($result['ok']) {
            $_SESSION['success'] = 'Account created! Check your inbox for a temporary password.';
            header('Location: login.php');
            exit;
        }

        $_SESSION['failure'] = $result['error'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<title>Register - QRForge</title>
<?php include './includes/head.php'; ?>

<body class="login-page" style="min-height: 512.391px;">
    <div class="login-box">
  <div class="login-logo">
    <img src="dist/img/brand/logo.svg" alt="QRForge" style="max-width: 260px;">
  </div>

  <div class="card">
    <div class="card-body login-card-body">
      <p class="login-box-msg">Create your free account</p>

      <?php include './includes/flash_messages.php'; ?>

      <form method="POST" action="register.php">
        <?php echo csrf_field(); ?>
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email address" required="required">
        </div>
        <div class="mb-3 text-center">
          <img src="captcha.php" alt="CAPTCHA" id="captcha-image" style="cursor:pointer;" title="Click to refresh">
        </div>
        <div class="input-group mb-3">
          <input type="text" name="captcha" class="form-control" placeholder="Answer the sum above" required="required" autocomplete="off">
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" class="btn btn-primary btn-block">Create account</button>
          </div>
        </div>
      </form>

      <p class="mt-3 text-center"><a href="login.php">Back to login</a></p>
    </div>
  </div>
</div>

<script src="../../plugins/jquery/jquery.min.js"></script>
<script src="../../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../../dist/js/adminlte.js"></script>
<script>
    document.getElementById('captcha-image').addEventListener('click', function () {
        this.src = 'captcha.php?' + Date.now();
    });
</script>

</body>
</html>
