<?php
define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");
include PATH.'server/server.php';

$action = $_POST['action'];

if( isset($_POST['return_to']) ){
	$r2prev = $_POST['return_to'];
	$r2login = '/login?return_to='.urlencode($r2prev);
} else {
	$r2prev = '/';
	$r2login = '/login';
}

if( $action === "login" ){
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
	) {
		bailout($r2login, 'required_field');
	}

	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if( $login ){
		bailout($r2prev, 'login_success');
	} else {
		bailout($r2login, 'login_bad');
	}
}

elseif( $action === "register" ){
	$r2 = '/register';

	// Check all required fields filled
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
		|| !array_key_exists('password-confirm', $_POST)
		|| strlen($_POST['username']) == 0
		|| strlen($_POST['password']) == 0
		|| strlen($_POST['password-confirm']) == 0
	) {
		bailout($r2, 'required_field');
	}

	// Set variables
	$post_user = trim($_POST['username']);
	$post_pass = $_POST['password'];
	$post_pass_confirm = $_POST['password-confirm'];
	// Set email if not already set
	if( array_key_exists('email', $_POST) ){
		$post_email = $_POST['email'];
	} else {
		$post_email = '';
	}
	
	// Confirm user password
	if( strlen($post_pass) < 6 || strlen($post_pass) > 72 ){
		bailout($r2, 'invalid_pass');
	}

	if( $post_pass != $post_pass_confirm ){
		bailout($r2, 'register_match');
	}

	// Validate username
	if( !valid_name($post_user) ){
		bailout($r2, 'invalid_name');
	}

	// Carry on if all fields good
	else {
		$register = $auth->register($post_user, $post_pass, $post_email);
		
		if( !$register ){
			bailout($r2, 'register_exists');
		} else {
			bailout('/welcome');
		}
	}
}

elseif( $action === "logout" ){
	$logout = $auth->logout();
	
	if( $logout ){
		bailout($r2prev, 'logout_success');
	} else {
		bailout($r2prev, 'logout_failure');
	}
}

elseif( $action === "logout_all" ){
	$logout = $auth->logout(true);
	
	if( $logout ){
		bailout($r2prev, 'logout_success');
	} else {
		bailout($r2prev, 'logout_failure');
	}
}

// File should only reach this point if no other actions have reached finalization.
bailout($r2login, 'disallowed_action');
?>
