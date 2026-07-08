<?php
/**
 * Fase 1 security hardening: sessiebeheer, CSRF, rate limiting, audit log.
 * Wordt geladen via includes/bootstrap.php, dat als eerste in elke entrypoint hoort te staan.
 */

define('SESSION_IDLE_TIMEOUT', 30 * 60); // 30 minuten inactiviteit -> uitloggen
define('LOGIN_MAX_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_WINDOW', 15 * 60); // 15 minuten

/**
 * Start de sessie met verharde cookie-instellingen. Moet vóór elke output aangeroepen worden.
 */
function qr_session_start() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

    ini_set('session.gc_maxlifetime', (string) SESSION_IDLE_TIMEOUT);
    ini_set('session.use_strict_mode', '1');

    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => $is_https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);

    session_start();
}

function qr_client_ip() {
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

/**
 * Logt de gebruiker uit als de sessie te lang inactief is geweest.
 */
function qr_enforce_session_timeout() {
    if (empty($_SESSION['user_logged_in'])) {
        return;
    }

    $now = time();

    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        $_SESSION = [];
        $_SESSION['login_failure'] = 'Je sessie is verlopen wegens inactiviteit. Log opnieuw in.';
        header('Location: login.php');
        exit;
    }

    $_SESSION['last_activity'] = $now;
}

/**
 * Stuurt ingelogde gebruikers met een verplichte wachtwoordwijziging naar change_password.php,
 * behalve op de wijzigingspagina en logout zelf.
 */
function qr_enforce_password_change() {
    if (empty($_SESSION['user_logged_in']) || empty($_SESSION['must_change_password'])) {
        return;
    }

    $current_script = basename(parse_url($_SERVER['SCRIPT_NAME'], PHP_URL_PATH));
    $exempt = ['change_password.php', 'logout.php'];

    if (in_array($current_script, $exempt, true)) {
        return;
    }

    header('Location: change_password.php');
    exit;
}

/**
 * CSRF-bescherming
 */
function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
}

function csrf_is_valid($token) {
    return isset($_SESSION['csrf_token']) && is_string($token) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Voor klassieke form-POSTs: verwacht een verborgen veld "csrf_token".
 */
function csrf_verify_or_die() {
    if (!csrf_is_valid($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('403 Forbidden: invalid or missing CSRF token.');
    }
}

/**
 * Voor JSON/AJAX-endpoints (bv. bulk_action.php): verwacht header X-CSRF-Token.
 */
function csrf_verify_header_or_die() {
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_is_valid($token)) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode(['data' => 'Invalid or missing CSRF token', 'status' => 403]);
        exit;
    }
}

/**
 * Rate limiting op login
 */
function qr_record_login_attempt($username, $success) {
    $db = getDbInstance();
    $db->insert('login_attempts', [
        'username' => $username,
        'ip_address' => qr_client_ip(),
        'success' => $success ? 1 : 0,
        'attempted_at' => date('Y-m-d H:i:s'),
    ]);
}

function qr_is_login_locked_out($username) {
    $db = getDbInstance();
    $window_start = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_WINDOW);

    $db->where('username', $username);
    $db->where('success', 0);
    $db->where('attempted_at', $window_start, '>=');
    $count = $db->getValue('login_attempts', 'count(*)');

    return $count !== null && $count >= LOGIN_MAX_ATTEMPTS;
}

/**
 * Audit log
 */
function audit_log($action, $target_type = null, $target_id = null) {
    $db = getDbInstance();
    $db->insert('audit_log', [
        'user_id' => $_SESSION['user_id'] ?? null,
        'username' => $_SESSION['username'] ?? null,
        'action' => $action,
        'target_type' => $target_type,
        'target_id' => $target_id !== null ? (string) $target_id : null,
        'ip_address' => qr_client_ip(),
        'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
        'created_at' => date('Y-m-d H:i:s'),
    ]);
}
