<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';

// FUNCTIONS

function generate_random_characters($amount, $characters = '0123456789abcdefghijklmnopqrstuvwxyz') {
	$str = '';

	$i = 0;
	while($i < $amount) {
		$str = $str.$characters[mt_rand(0, strlen($characters) - 1)];
		$i++;
	}
	return $str;
}


// Data class to store results of file uploads

class UploadResult {
	public bool $ok;
	public string $path;
	public Notice $notice;

	function __construct( string $path = '', string $response_code = 'default' ){
		$this->ok = strlen($path) > 0;
		$this->path = $path;
		$this->notice = new Notice($response_code);
	}
}

// Stores an image uploaded via POST that was fetched from $_FILES
# TODO: add supports for requirements such as minimum or maximum image dimensions and size
# TODO: check whether camera metadata is stored in these and if so, how to strip it out
function upload_image( array $image, string $subdir = '' ): UploadResult {
	// setup
	
	# why tf does getenv return false instead of null when it doesn't exist? bruh
	$base_path = rtrim(getenv('DATA_DIR') !== false ? getenv('DATA_DIR') : PATH, '/');
	$sub_path = trim('upload/'.$subdir, '/');

	// validate file before upload

	if( !is_writable($base_path) ){
		return new UploadResult(response_code: 'file_system_insufficient_permissions');
	}

	if( $image['size'] < 1 ){
		return new UploadResult(response_code: 'image_invalid');
	}

	// "upload" (move) file

	$ext = '.'.explode('.', $image['name'])[1];

	$full_path_prefix = implode('/', [$base_path, $sub_path]);

	# keep generating random strings until a valid name is found.
	# TODO: there has gotta be a better way than this?
	while(true) {
		$filename = generate_random_characters(64);
		$internal_image_location = implode('/', [$full_path_prefix, $filename.$ext]);
		if( file_exists($internal_image_location) ){
			continue;
		}
		break;
	}

	$external_image_location = implode('/', ['', $sub_path, $filename.$ext]);
	
	# make local directory if needed, then move
	if( !is_dir($full_path_prefix) ){
		mkdir($full_path_prefix, recursive: true);
	}
	$uploaded = move_uploaded_file($image['tmp_name'], $internal_image_location);
	
	if( !$uploaded ){
		return new UploadResult(response_code: 'image_failure');
	}
	return new UploadResult(path: $external_image_location, response_code: 'success');
}
?>