<?php

require_once 'includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	csrf_verify_or_die();

	// Login moves from username to email. Accept either during the transition -
	// existing pre-migration accounts have no email yet (see must_set_email/set_email.php),
	// so a plain username must keep working until they've set one.
	$identifier = filter_input(INPUT_POST, 'email');
	$password = filter_input(INPUT_POST, 'password');
	$remember = filter_input(INPUT_POST, 'remember');

	if (!$identifier || !$password) {
		$_SESSION['login_failure'] = 'Invalid email or password';
		header('Location: login.php');
		exit;
	}

	if (qr_is_login_locked_out($identifier)) {
		$_SESSION['login_failure'] = 'Too many failed login attempts. Try again in 15 minutes.';
		header('Location: login.php');
		exit;
	}

	// Get DB instance.
	$db = getDbInstance();

	$db->where('email', $identifier);
	$row = $db->getOne('users');

	if ($db->count < 1) {
		// Compatibility fallback for accounts that haven't set an email yet.
		$db = getDbInstance();
		$db->where('username', $identifier);
		$row = $db->getOne('users');
	}

	if ($db->count >= 1 && password_verify($password, $row['password']))
    {
		qr_record_login_attempt($identifier, true);

		// Voorkom session fixation: nieuwe sessie-id na een geslaagde login.
		session_regenerate_id(true);

		$_SESSION['user_logged_in'] = TRUE;
		$_SESSION['type'] = $row['type'];
        $_SESSION['user_id'] = $row['id'];
		$_SESSION['username'] = $row['username'];
		$_SESSION['must_change_password'] = !empty($row['must_change_password']);
		$_SESSION['must_set_email'] = !empty($row['must_set_email']);
		$_SESSION['can_view_static'] = !empty($row['can_view_static']);
		$_SESSION['can_view_dynamic'] = !empty($row['can_view_dynamic']);
		$_SESSION['scope_owner_id'] = qr_compute_scope_owner_id($row);
		$_SESSION['last_activity'] = time();

		audit_log('login_success');

		$user_id = $row['id'];

		if ($remember)
        {
			$series_id = randomString(16);
			$remember_token = getSecureRandomToken(20);
			$encryted_remember_token = password_hash($remember_token,PASSWORD_DEFAULT);

			$expiry_time = date('Y-m-d H:i:s', strtotime(' + 30 days'));
			$expires = strtotime($expiry_time);
			$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';

			$cookie_options = [
				'expires' => $expires,
				'path' => '/',
				'secure' => $is_https,
				'httponly' => true,
				'samesite' => 'Lax',
			];

			setcookie('series_id', $series_id, $cookie_options);
			setcookie('remember_token', $remember_token, $cookie_options);

			$db = getDbInstance();
			$db->where ('id',$user_id);

			$update_remember = array(
				'series_id'=> $series_id,
				'remember_token' => $encryted_remember_token,
				'expires' =>$expiry_time
			);
			$db->update('users', $update_remember);
		}
		// Authentication successfull redirect user
		header('Location: index.php');
		exit;
	}
    else
    {
		qr_record_login_attempt($identifier, false);
		$_SESSION['login_failure'] = 'Invalid email or password';
		header('Location: login.php');
		exit;
	}
}
else
{
	die('Method Not allowed');
}
