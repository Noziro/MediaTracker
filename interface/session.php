<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';

$return_to_login = '/login';
$return_to_register = '/register';
if( $return_to !== '/' ){
	$return_to_login = $return_to_login.'?return_to='.urlencode($return_to);
	$return_to_register = $return_to_register.'?return_to='.urlencode($return_to);
}

if( API_ACTION === "/session/login" ){
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
	) {
		bailout($return_to_login, 'required_field');
	}

	$login = $auth->login($_POST['username'], $_POST['password']);
	
	if( $login ){
		bailout($return_to, 'login_success');
	} else {
		bailout($return_to_login, 'login_bad');
	}
}

elseif( API_ACTION === "/session/register" ){
	// Check all required fields filled
	if(	   !array_key_exists('username', $_POST)
		|| !array_key_exists('password', $_POST)
		|| !array_key_exists('password-confirm', $_POST)
		|| strlen($_POST['username']) == 0
		|| strlen($_POST['password']) == 0
		|| strlen($_POST['password-confirm']) == 0
	) {
		bailout($return_to_register, 'required_field');
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
		bailout($return_to_register, 'invalid_pass');
	}

	if( $post_pass != $post_pass_confirm ){
		bailout($return_to_register, 'register_match');
	}

	// Validate username
	if( !valid_name($post_user) ){
		bailout($return_to_register, 'invalid_name');
	}

	// Carry on if all fields good
	else {
		$register = $auth->register($post_user, $post_pass, $post_email);
		
		if( !$register ){
			bailout($return_to_register, 'register_exists');
		} else {
			bailout('/welcome');
		}
	}
}

elseif( API_ACTION === "/session/logout" ){
	$logout = $auth->logout();
	
	if( $logout ){
		bailout($return_to, 'logout_success');
	} else {
		bailout($return_to, 'logout_failure');
	}
}

elseif( API_ACTION === "/session/logout_all" ){
	$logout = $auth->logout(true);
	
	if( $logout ){
		bailout($return_to, 'logout_success');
	} else {
		bailout($return_to, 'logout_failure');
	}
}

// File should only reach this point if no other actions have reached finalization.
bailout($return_to_login, 'disallowed_action');
?>
