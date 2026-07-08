<?php
/**
 * Authenticated view/download endpoint for a generated qr code image.
 * Replaces the previous direct static URL under saved_qrcode/, which any visitor
 * could reach without logging in. Enforces the same visibility rules as the list
 * pages (dynamic_qrcodes.php / static_qrcodes.php).
 */
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';

$type = $_GET['type'] ?? '';
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!in_array($type, ['static', 'dynamic'], true) || !$id) {
    http_response_code(404);
    exit('Not found');
}

if ($_SESSION['type'] === 'user') {
    $view_flag = $type === 'dynamic' ? 'can_view_dynamic' : 'can_view_static';
    if (empty($_SESSION[$view_flag] ?? null)) {
        http_response_code(403);
        exit('Forbidden');
    }
}

$db = getDbInstance();
$db->where('id', $id);
qr_apply_owner_scope($db);
$row = $db->getOne("{$type}_qrcodes");

if ($row === null) {
    http_response_code(404);
    exit('Not found');
}

$path = SAVED_QRCODE_DIRECTORY . $row['qrcode'];

if (!is_file($path)) {
    http_response_code(404);
    exit('Not found');
}

$mime_types = [
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'gif' => 'image/gif',
    'svg' => 'image/svg+xml',
    'eps' => 'application/postscript',
];
$extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$mime = $mime_types[$extension] ?? 'application/octet-stream';

$is_download = isset($_GET['download']) && $_GET['download'] === '1';

header('Content-Type: ' . $mime);
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, max-age=0, no-cache');
header('Content-Disposition: ' . ($is_download ? 'attachment' : 'inline') . '; filename="' . basename($path) . '"');

readfile($path);
exit;
