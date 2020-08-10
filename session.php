<?php
include 'server/server.php';

$auth = new Authentication();

$action = $_POST['action'];

if($action == "login") {
	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if ($login) {
		header('Location: /?notice=login-success');
	} else {
		header('Location: /login?action=login&error=login-bad');
	}
}

elseif($action == "register") {
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
		header('Location: /login?action=register&error=required-field');
	} elseif($_POST['password'] != $_POST['password-confirm']) {
		header("Location: /login?action=register&error=register-match");
	} elseif(!valid_name($_POST["username"])) {
		// Validate username
		header('Location: /login?action=register&error=register-invalid-name');
	} else {
		// Carry on if all fields good
		$register = $auth->register($_POST['username'], $_POST['email'], $_POST['password']);
		
		if (!$register) {
			header('Location: /login?action=register&error=register-exists');
		} else {
			header('Location: /?notice=register-success');
		}
	}
}

elseif($action == "logout") {
	$logout = $auth->logout();
	
	if($logout) {
		header('Location: /?notice=logout-success');
	} else {
		header('Location: /?error=logout-failure');
	}
}

else {
	header('Location: /');
	exit();
}

$auth->cleanup();
?>
