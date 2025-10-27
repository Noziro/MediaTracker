<?php declare(strict_types=1);
require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';

if( API_ACTION === "/collection/create" ){
	// Required fields
	if( !isset($_POST['name']) || !isset($_POST['type']) ){
		bailout($return_to, 'required_field');
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if( !in_array((string)$type, VALID_MEDIA_TYPES, True) ){
		bailout($return_to, 'invalid_value');
	}

	if( !isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True) ){
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	// Execute DB
	$stmt = sql('INSERT INTO collections (user_id, name, type, private) VALUES (?, ?, ?, ?)', ['issi', $user['id'], $name, $type, $private]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
	
	bailout($return_to, 'success');
}





elseif( API_ACTION === "/collection/edit" ){
	if( !isset($_POST['collection_id']) ){
		bailout($return_to, 'disallowed_action');
	}

	// Required Fields
	if( !isset($_POST['name']) || !isset($_POST['type']) ){
		bailout($return_to, 'required_field');
	}
	
	// Check existence
	$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
	if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
	$collection = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		bailout($return_to, 'unauthorized');
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if( !in_array((string)$type, VALID_MEDIA_TYPES, True) ){
		bailout($return_to, 'invalid_value');
	}

	if( !isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True) ){
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	$columns = [
		'display_image' => 1,
		'display_score' => 1,
		'display_progress' => 1,
		'display_user_started' => 1,
		'display_user_finished' => 1,
		'display_days' => 1
	];

	foreach( $columns as $col => $val ){
		if( !isset($_POST[$col]) ){
			bailout($return_to, 'invalid_value');
		} else {
			$columns[$col] = $_POST[$col];
		}
	}

	if( isset($_POST['rating_system']) ){
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if( !in_array((int)$rating_system, [3,5,10,20,100], True) ){
			bailout($return_to, 'invalid_value');
		}
	} else {
		$rating_system = $collection['rating_system'];
	}

	// Execute DB
	$stmt = sql('UPDATE collections SET
		name=?,
		type=?,
		display_image=?,
		display_score=?,
		display_progress=?,
		display_user_started=?,
		display_user_finished=?,
		display_days=?,
		rating_system=?,
		private=?
		WHERE id=?
	', [
		'ssiiiiiiiii',
		$name,
		$type,
		$columns['display_image'],
		$columns['display_score'],
		$columns['display_progress'],
		$columns['display_user_started'],
		$columns['display_user_finished'],
		$columns['display_days'],
		$rating_system,
		$private,
		$collection['id']
	]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }

	bailout($return_to, 'success');
}





elseif( API_ACTION === '/collection/delete' || API_ACTION === '/collection/undelete' ){
	if( !isset($_POST['collection_id']) ){
		bailout($return_to, 'disallowed_action');
	}

	if( API_ACTION === '/collection/delete' ){
		$delete = 1;
	} elseif( API_ACTION === '/collection/undelete' ){
		$delete = 0;
	}

	// Check existence
	$stmt = sql('SELECT id, user_id FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
	if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
	$collection = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		bailout($return_to, 'unauthorized');
	}

	// Execute DB
	$stmt = sql('UPDATE collections SET deleted=? WHERE id=?', ['ii', $delete, $collection['id']]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }

	if( API_ACTION === '/collection/delete' ){
		$return_to = '/collection';
	}

	bailout($return_to, 'success');
}
?>