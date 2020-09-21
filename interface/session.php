<?php
include '../server/server.php';

$action = $_POST['action'];

if(isset($_POST['return_to'])) {
	$r2prev = $_POST['return_to'];
	$r2login = '/login?action=login&return_to='.urlencode($r2prev);
} else {
	$r2prev = '/?';
	$r2login = '/login?action=login';
}

if($action === "login") {
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
	) {
		finalize($r2login, 'required_field', 'error');
	}

	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if ($login) {
		finalize($r2prev, 'login_success');
	} else {
		finalize($r2login, 'login_bad', 'error');
	}
}

elseif($action === "register") {
	$r2page = '/login?action=register';

	function valid_name(string $str) {
		$okay = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";

		foreach(str_split($str) as $c) {
			if(strpos($okay, $c) === False) {
				return False;
			}
		}
		
		return true;
	}

	// Set email if not already set
	if(!array_key_exists('email', $_POST)) {
		$_POST['email'] = '';
	}

	// Check all required fields filled
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
		|| !array_key_exists('password-confirm', $_POST)
		|| strlen($_POST['username']) == 0
		|| strlen($_POST['password']) == 0
		|| strlen($_POST['password-confirm']) == 0
	) {
		finalize($r2page, 'required_field', 'error');
	}
	
	// Confirm user password
	elseif(strlen($_POST['password']) < 6 || strlen($_POST['password']) > 72) {
		finalize($r2page, 'register_invalid_pass', 'error');
	}

	elseif($_POST['password'] != $_POST['password-confirm']) {
		finalize($r2page, 'register_match', 'error');
	}

	// Validate username
	elseif(!valid_name($_POST["username"])) {
		finalize($r2page, 'register_invalid_name', 'error');
	}

	// Carry on if all fields good
	else {
		$register = $auth->register($_POST['username'], $_POST['password'], $_POST['email']);
		
		if (!$register) {
			finalize($r2page, 'register_exists', 'error');
		} else {
			finalize('/welcome');
		}
	}
}

elseif($action === "logout") {
	$logout = $auth->logout();
	
	if($logout) {
		finalize($r2prev, 'logout_success');
	} else {
		finalize($r2prev, 'logout_failure');
	}
}

// File should only reach this point if no other actions have reached finalization.
finalize($r2login, 'disallowed_action', 'error');
?>
