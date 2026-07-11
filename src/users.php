<?php
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';
require_once BASE_PATH . '/lib/Users/Users.php';

$db = getDbInstance();
$users = new Users();

if (!in_array($_SESSION['type'], ['super', 'admin'], true))
    $users->failure('Only "super admin" and "admin" accounts can access the user management page', 'Location: index.php');

$select = array('id', 'username', 'type');
$search_fields = array('username');
require_once BASE_PATH . '/includes/search_order.php';
$page = filter_input(INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS) ?? 1;
$db->pageLimit = 15;

// An admin only sees the read-only 'user' accounts they created themselves.
if ($_SESSION['type'] === 'admin') {
    $db->where('owner_admin_id', $_SESSION['user_id']);
    $db->where('type', 'user');
}

$rows = $db->arraybuilder()->paginate('users', $page, $select);
$total_pages = $db->totalPages;
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
            <h1 class="m-0 text-dark">Users</h1>
          </div><!-- /.col -->
          
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
                <li class="breadcrumb-item">
                  <a href="user.php" class="btn btn-success"><i class="fa fa-plus"></i> Add new</a>
                </li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div><!-- /.content-header -->
    
    <!-- Flash message-->
    <?php include BASE_PATH . '/includes/flash_messages.php'; ?>
    <!-- /.Flash message-->
    
    <!-- Filters -->
    <?php   $options = $users->setOrderingValues();
            include BASE_PATH . '/forms/filters.php'; ?>
    <!-- /.Filters -->

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">
          
            <!-- Table -->
            <?php include BASE_PATH . '/forms/table_users.php'; ?>
            <!-- /.Table -->

        </div><!-- /.container-fluid -->
    </section>
  </div><!-- /.content-wrapper -->

    <!-- Footer and scripts -->
    <?php include './includes/footer.php'; ?>
    <!-- /.Footer and scripts -->
</body>
</html>
