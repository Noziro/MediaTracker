<?php declare(strict_types=1);

// SETUP

require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';

// Parse return_to values if any are set

$return_to = '/';
if( isset($_POST['return_to']) ){
	$return_to = $_POST['return_to'];
}
elseif( isset($_GET['return_to']) ){
	$return_to = $_GET['return_to'];
}

// Define all possible actions

const ACTIONS = [
	'/session/logout' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'session'
	],
	'/session/logout_all' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'session'
	],
	'/session/login' => [
		'method' => 'POST',
		'auth' => false,
		'file' => 'session'
	],
	'/session/register' => [
		'method' => 'POST',
		'auth' => false,
		'file' => 'session'
	],
	'/user/settings/update' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'account'
	],
	'/media/create' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'media'
	],
	'/media/edit' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'media'
	],
	'/media/delete' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'media'
	],
	'/media/undelete' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'media'
	],
	'/collection/create' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'collection'
	],
	'/collection/edit' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'collection'
	],
	'/collection/delete' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'collection'
	],
	'/collection/undelete' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'collection'
	],
	'/import' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'import'
	],
	'/export' => [
		'method' => 'POST',
		'auth' => true,
		'file' => 'import'
	]
];

// Check if request is one of defined actions

foreach( ACTIONS as $action => $properties ){
	$attempted_action = str_replace('/interface', '', URL['PATH_STRING']);
	if( $attempted_action === $action ){
		if( $properties['auth'] === true && !$has_session ){
			bailout($return_to, 'require_sign_in');
		}
		if( $properties['method'] !== $_SERVER['REQUEST_METHOD'] ){
			bailout($return_to, 'incorrect_method');
		}
		DEFINE("API_ACTION", $action);
		require_once $properties['file'].'.php';
		exit();
	}
}

bailout($return_to, 'disallowed_action');