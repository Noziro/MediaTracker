<?php declare(strict_types=1);

$db_root = new mysqli(
	SQL_CREDENTIALS['host'],
	'root',
	getenv('DB_ROOT_PASSWORD') ?: 'root_password_change_me',
	SQL_CREDENTIALS['dbname'],
	SQL_CREDENTIALS['port']);

$schemaFile = realpath(__DIR__ . '/../schema.sql');
if( $schemaFile && file_exists($schemaFile) ){
	$commands = file_get_contents($schemaFile);
	$db_root->multi_query($commands);
	while ($db_root->more_results() && $db_root->next_result()) { }
} else {
	die('Schema file not found.');
}

?>