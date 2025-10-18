<?php

$host = getenv('DB_HOST') ?: 'localhost';
$user = 'root';
$pass = getenv('DB_ROOT_PASSWORD') ?: 'root_password_change_me';
$dbname = getenv('DB_DATABASE') ?: 'mediatracker';
$port = getenv('DB_PORT') ? intval(getenv('DB_PORT')) : 3306;

$db_root = new mysqli($host, $user, $pass, $dbname, $port);

$schemaFile = realpath(__DIR__ . '/../schema.sql');
if ($schemaFile && file_exists($schemaFile)) {
	$commands = file_get_contents($schemaFile);
	$db_root->multi_query($commands);
	while ($db_root->more_results() && $db_root->next_result()) { }
} else {
	die('Schema file not found.');
}

?>