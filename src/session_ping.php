<?php
// Lightweight keep-alive endpoint: including bootstrap.php refreshes
// $_SESSION['last_activity'], extending the idle timeout without navigating
// away from (and losing) whatever form the user is currently filling in.
// Deliberately doesn't use auth_validate.php's redirect-to-login-on-failure
// behavior: this is called from JS, and a 401 lets the caller show "your
// session already expired" instead of silently following a redirect.
require_once 'includes/bootstrap.php';

header('Content-Type: application/json');

if (empty($_SESSION['user_logged_in'])) {
    http_response_code(401);
    echo json_encode(['ok' => false]);
    exit;
}

echo json_encode(['ok' => true]);
