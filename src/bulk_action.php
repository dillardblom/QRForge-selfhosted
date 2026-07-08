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

        foreach ($params as $param) {
            $db->where('id', $param);
            if ($_SESSION['type'] !== 'super') {
                $db->where('id_owner', $_SESSION['user_id']);
                $db->orWhere('id_owner', NULL, 'IS');
            }
            $row = $db->getOne("{$type}_qrcodes");
            if ($row !== NULL) {
                $files[] = SAVED_QRCODE_FOLDER . $row['qrcode'];
            }
        }

        $zip = new ZipArchive();
        $uniqid = uniqid();
        $relative_dir = SAVED_QRCODE_FOLDER . 'zip/qrcodes_' . $uniqid . '.zip';
        @unlink($relative_dir);
        $url_path = SAVED_QRCODE_URL . 'zip/qrcodes_' . $uniqid . '.zip';
        $zip->open($relative_dir, ZipArchive::CREATE);

        foreach ($files as $file) {
            $download_file = @file_get_contents($file, true);
            $zip->addFromString(basename($file), $download_file);
        }

        $zip->close();

        audit_log('bulk_download', $type, implode(',', $params));

        echo json_encode([
            'data' => $url_path,
            'status' => 200
        ]);
        exit();
    } else if($json["action"] == "delete") {
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
