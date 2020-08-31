<?php
include 'server/server.php';

$action = $_POST['action'];

if(isset($_POST['return_to'])) {
	$r2prev = $_POST['return_to'];
	
	$r2prev = preg_replace("/(\&|\?)(notice|error)\=.+?(?=(\&|$))/", "", $r2prev);
	$r2login = '/login?action=login&return_to='.urlencode($r2prev);
} else {
	$r2prev = '/?';
	$r2login = '/login?action=login';
}

if($action === "login") {
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
	) {
		finalize($r2login, ['error=required-field']);
	}

	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if ($login) {
		finalize($r2prev, ['notice=login-success']);
	} else {
		finalize($r2login, ['error=login-bad']);
	}
}

elseif($action === "register") {
	$r2page = '/login?action=register';

	function valid_name(string $str) {
		$okay = str_split("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_");
		
		foreach(str_split($str) as $c) {
			if(!in_array($c, $okay)) {
				return false;
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
		finalize($r2page, ['error=required-field']);
	}
	
	// Confirm user password
	elseif(strlen($_POST['password']) < 6 || strlen($_POST['password']) > 72) {
		finalize($r2page, ['error=register-invalid-pass']);
	}

	elseif($_POST['password'] != $_POST['password-confirm']) {
		finalize($r2page, ['error=register-match']);
	}

	// Validate username
	elseif(!valid_name($_POST["username"])) {
		finalize($r2page, ['error=register-invalid-name']);
	}

	// Carry on if all fields good
	else {
		$register = $auth->register($_POST['username'], $_POST['password'], $_POST['email']);
		
		if (!$register) {
			finalize($r2page, ['error=register-exists']);
		} else {
			finalize('/welcome');
		}
	}
}

elseif($action === "logout") {
	$logout = $auth->logout();
	
	if($logout) {
		finalize($r2prev, ['notice=logout-success']);
	} else {
		finalize($r2prev, ['notice=logout-failure']);
	}
}

// File should only reach this point if no other actions have reached finalization.
finalize('/?error=disallowed-action');
?>
