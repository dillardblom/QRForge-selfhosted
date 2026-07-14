<?php
/**
 * Centrale bootstrap voor elke entrypoint: config laden, sessie starten met
 * verharde instellingen, sessie-timeout en verplichte wachtwoordwijziging afdwingen.
 *
 * Vervangt de losse "session_start(); require_once 'config/config.php';" aanroepen.
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/security.php';

qr_session_start();
qr_enforce_session_timeout();
qr_enforce_password_change();
qr_enforce_email_set();
