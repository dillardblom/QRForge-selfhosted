<?php
//Note: This file should be included first in every php page.
require_once ('environment.php');

// Never display errors/warnings/deprecations in the response body: besides leaking
// internal file paths, it can inject output before session_start() runs and break
// login entirely (seen with PHP 8.4's new deprecation notices). Log them instead.
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
ini_set('log_errors', 'On');
define('BASE_PATH', dirname(dirname(__FILE__)));
define('CURRENT_PAGE', basename($_SERVER['REQUEST_URI']));
define('SCRIPT_NAME', ltrim(dirname($_SERVER['SCRIPT_NAME']), '/'));

if(SCRIPT_NAME === "")
    define('SCRIPT_FOLDER', "");
else
    define('SCRIPT_FOLDER', "/" . SCRIPT_NAME);

require_once BASE_PATH . '/lib/MysqliDb/MysqliDb.php';
require_once BASE_PATH . '/helpers/helpers.php';

/* SAVED QR CODES */
// Storage lives outside the document root so files can only be reached through the
// authenticated qrcode_image.php / qrcode_zip_download.php endpoints, never as a direct
// static URL. See db/migrations and the "saved_qrcode" hardening note in the OSS repo.
define('SAVED_QRCODE_DIRECTORY', dirname(BASE_PATH).'/qrcode-storage/');

//You can change the page name for the redirect and the search parameter (the default is "id")
define('READ_PATH', base_url().'/read.php?id=');


/**
 * Get instance of DB object
 */
function getDbInstance() {
    return new MysqliDb (Array (
        'host' => DATABASE_HOST,
        'username' => DATABASE_USER,
        'password' => DATABASE_PASSWORD,
        'db'=> DATABASE_NAME,
        'port' => DATABASE_PORT,
        'prefix' => DATABASE_PREFIX,
        'charset' => DATABASE_CHARSET));
}
