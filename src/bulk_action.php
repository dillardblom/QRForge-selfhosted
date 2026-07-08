<?php
require_once 'includes/bootstrap.php';
require_once BASE_PATH . '/includes/auth_validate.php';
require_once BASE_PATH . '/lib/DynamicQrcode/DynamicQrcode.php';
require_once BASE_PATH . '/lib/StaticQrcode/StaticQrcode.php';

header('Content-Type: application/json');
csrf_verify_header_or_die();

$allowed_types = ['dynamic', 'static'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDbInstance();
    $json = json_decode(file_get_contents('php://input'), true);

    if($json["action"] == "download") {
        $params = $json['params'];
        $files = [];

        if (isset($json['type']) && in_array($json['type'], $allowed_types, true)) {
            $type = $json['type'];
        } else {
            echo json_encode([
                'data' => 'Type action field in the request.',
                'status' => 400
            ]);
            exit();
        }

        if (count($params) == 0) {
            echo json_encode([
                'data' => 'No qrcodes were selected.',
                'status' => 400
            ]);
            exit();
        }

        if ($_SESSION['type'] === 'user') {
            $view_flag = $type === 'dynamic' ? 'can_view_dynamic' : 'can_view_static';
            if (empty($_SESSION[$view_flag] ?? null)) {
                http_response_code(403);
                echo json_encode(['data' => 'Not allowed to view this qr code type.', 'status' => 403]);
                exit();
            }
        }

        foreach ($params as $param) {
            $db->where('id', $param);
            qr_apply_owner_scope($db);
            $row = $db->getOne("{$type}_qrcodes");
            if ($row !== NULL) {
                $files[] = SAVED_QRCODE_DIRECTORY . $row['qrcode'];
            }
        }

        $zip = new ZipArchive();
        $zip_filename = 'qrcodes_' . uniqid() . '.zip';
        $zip_path = SAVED_QRCODE_DIRECTORY . 'zip/' . $zip_filename;
        @unlink($zip_path);
        $zip->open($zip_path, ZipArchive::CREATE);

        foreach ($files as $file) {
            $download_file = @file_get_contents($file, true);
            $zip->addFromString(basename($file), $download_file);
        }

        $zip->close();

        // Proof-of-generation: only this session may download this specific zip file.
        $_SESSION['generated_zips'][] = $zip_filename;

        audit_log('bulk_download', $type, implode(',', $params));

        echo json_encode([
            'data' => 'qrcode_zip_download.php?file=' . rawurlencode($zip_filename),
            'status' => 200
        ]);
        exit();
    } else if($json["action"] == "delete") {
        if ($_SESSION['type'] === 'user') {
            http_response_code(403);
            echo json_encode(['data' => 'The "user" role is read-only.', 'status' => 403]);
            exit();
        }

        $params = $json['params'];

        if (isset($json['type']) && in_array($json['type'], $allowed_types, true)) {
            $type = $json['type'];
        } else {
            echo json_encode([
                'data' => 'Type action field in the request.',
                'status' => 400
            ]);
            exit();
        }

        if (count($params) == 0) {
            echo json_encode([
                'data' => 'No qrcodes were selected.',
                'status' => 400
            ]);
            exit();
        }

        if($type == "dynamic")
            $instance = new DynamicQrcode();
        else
            $instance = new StaticQrcode();

        foreach ($params as $param) {
            $instance->deleteQrcode($param, true);
        }

        audit_log('bulk_delete', $type, implode(',', $params));

        echo json_encode([
            'action' => "delete",
            'data' => "Qrcode deleted",
            'status' => 200
        ]);
        exit();

    } else {
        echo json_encode(['data' => 'Action not allowed', 'status' => 400]);
        exit();
    }
} else {
    http_response_code(405);
    echo json_encode(['data' => 'Direct access to this script not allowed.', 'status' => 405]);
    exit();
}
