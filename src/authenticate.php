<?php

require_once 'includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST')
{
	csrf_verify_or_die();

	$username = filter_input(INPUT_POST, 'username');
	$password = filter_input(INPUT_POST, 'password');
	$remember = filter_input(INPUT_POST, 'remember');

	if (!$username || !$password) {
		$_SESSION['login_failure'] = 'Invalid username or password';
		header('Location: login.php');
		exit;
	}

	if (qr_is_login_locked_out($username)) {
		$_SESSION['login_failure'] = 'Too many failed login attempts. Try again in 15 minutes.';
		header('Location: login.php');
		exit;
	}

	// Get DB instance.
	$db = getDbInstance();

	$db->where('username', $username);
	$row = $db->getOne('users');

	if ($db->count >= 1 && password_verify($password, $row['password']))
    {
		qr_record_login_attempt($username, true);

		// Voorkom session fixation: nieuwe sessie-id na een geslaagde login.
		session_regenerate_id(true);

		$_SESSION['user_logged_in'] = TRUE;
		$_SESSION['type'] = $row['type'];
        $_SESSION['user_id'] = $row['id'];
		$_SESSION['username'] = $row['username'];
		$_SESSION['must_change_password'] = !empty($row['must_change_password']);
		$_SESSION['can_view_static'] = !empty($row['can_view_static']);
		$_SESSION['can_view_dynamic'] = !empty($row['can_view_dynamic']);
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
		qr_record_login_attempt($username, false);
		$_SESSION['login_failure'] = 'Invalid username or password';
		header('Location: login.php');
		exit;
	}
}
else
{
	die('Method Not allowed');
}
