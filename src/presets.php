<?php
/**
 * AJAX endpoint for saved color/style presets (Fase 3, priority 2). Presets are
 * personal: scoped to the logged-in user's own id, never shared across accounts.
 */
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? $_POST['action'] ?? '';

if ($action === 'list') {
    $db = getDbInstance();
    $db->where('user_id', $_SESSION['user_id']);
    $db->orderBy('name', 'ASC');
    $presets = $db->get('qr_presets', null, ['id', 'name', 'foreground', 'background', 'level', 'size']);

    echo json_encode(['status' => 200, 'data' => $presets]);
    exit;
}

if ($action === 'save') {
    csrf_verify_header_or_die();

    $name = trim((string) ($_POST['name'] ?? ''));
    $foreground = trim((string) ($_POST['foreground'] ?? ''));
    $background = trim((string) ($_POST['background'] ?? ''));
    $level = $_POST['level'] ?? 'L';
    $size = filter_var($_POST['size'] ?? 200, FILTER_VALIDATE_INT);

    if ($name === '' || strlen($name) > 50) {
        echo json_encode(['status' => 400, 'data' => 'Preset name must be between 1 and 50 characters.']);
        exit;
    }

    if (!preg_match('/^#?[0-9a-fA-F]{6}$/', $foreground) || !preg_match('/^#?[0-9a-fA-F]{6}$/', $background)) {
        echo json_encode(['status' => 400, 'data' => 'Foreground/background must be valid hex colors.']);
        exit;
    }

    if (!in_array($level, ['L', 'M', 'Q', 'H'], true)) {
        $level = 'L';
    }

    if ($size === false) {
        $size = 200;
    }

    $db = getDbInstance();
    $last_id = $db->insert('qr_presets', [
        'user_id' => $_SESSION['user_id'],
        'name' => $name,
        'foreground' => $foreground,
        'background' => $background,
        'level' => $level,
        'size' => $size,
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    if (!$last_id) {
        echo json_encode(['status' => 500, 'data' => 'Could not save preset.']);
        exit;
    }

    echo json_encode(['status' => 200, 'data' => ['id' => $last_id, 'name' => $name]]);
    exit;
}

if ($action === 'delete') {
    csrf_verify_header_or_die();

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

    if (!$id) {
        echo json_encode(['status' => 400, 'data' => 'Invalid preset id.']);
        exit;
    }

    $db = getDbInstance();
    $db->where('id', $id);
    $db->where('user_id', $_SESSION['user_id']);
    $deleted = $db->delete('qr_presets');

    echo json_encode(['status' => $deleted ? 200 : 404, 'data' => $deleted ? 'Deleted' : 'Preset not found']);
    exit;
}

echo json_encode(['status' => 400, 'data' => 'Unknown action']);
