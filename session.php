<?php
include 'server/server.php';

$action = $_POST['action'];

if(isset($_POST['return_to'])) {
	$return_to = $_POST['return_to'];
	
	$return_to = preg_replace("/\&(notice|error)\=.+?(?=(\&|$))/", "", $return_to);
	
	$return_to_page = $return_to;
	if(strpos($return_to_page, '?') === false) {
		$return_to_page = $return_to_page.'?';
	}
	$return_to_login = '/login?action=login&return_to='.$return_to;
} else {
	$return_to_page = '/?';
	$return_to_login = '/login?action=login';
}

if($action === "login") {
	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if ($login) {
		finalize($return_to_page.'&notice=login-success');
	} else {
		finalize($return_to_login.'&error=login-bad');
	}
}

elseif($action === "register") {
	function valid_name(string $str) {
		$okay = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-_";
		$okay = str_split($okay);
		
		foreach(str_split($str) as $c) {
			if(!in_array($c, $okay)) {
				return false;
			}
		}
		
		return true;
	}
	
	if(strlen($_POST['username']) == 0 || strlen($_POST['password']) == 0 || strlen($_POST['password-confirm']) == 0) {
		finalize('/login?action=register&error=required-field');
	} elseif($_POST['password'] != $_POST['password-confirm']) {
		finalize('/login?action=register&error=register-match');
	} elseif(!valid_name($_POST["username"])) {
		// Validate username
		finalize('/login?action=register&error=register-invalid-name');
	} else {
		// Carry on if all fields good
		$register = $auth->register($_POST['username'], $_POST['email'], $_POST['password']);
		
		if (!$register) {
			finalize('/login?action=register&error=register-exists');
		} else {
			finalize('/welcome');
		}
	}
}

elseif($action === "logout") {
	$logout = $auth->logout();
	
	if($logout) {
		finalize($return_to_page.'&notice=logout-success');
	} else {
		finalize($return_to_page.'&error=logout-failure');
	}
}

// File should only reach this point if no other actions have reached finalization.
finalize('/?error=disallowed-action');
?>
