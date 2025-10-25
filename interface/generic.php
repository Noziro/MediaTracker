<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");
include PATH.'server/server.php';

// AUTH

if( !$has_session ){
	finalize('/', ['require_sign_in', 'error']); 
}

$action = $_POST['action'];

// RETURN TO

if( isset($_POST['return_to']) ){
	$r2 = $_POST['return_to'];
} else {
	$r2 = '/';
}




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




// ACTIONS

if( $action === "collection_create" ){
	// Required fields
	if( !isset($_POST['name']) || !isset($_POST['type']) ){
		finalize($r2, ['required_field', 'error']);
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if( !in_array((string)$type, $valid_coll_types, True) ){
		finalize($r2, ['invalid_value', 'error']);
	}

	if( !isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True) ){
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	// Execute DB
	$stmt = sql('INSERT INTO collections (user_id, name, type, private) VALUES (?, ?, ?, ?)', ['issi', $user['id'], $name, $type, $private]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
	
	finalize($r2, ['success']);
}





elseif( $action === "collection_edit" ){
	if( !isset($_POST['collection_id']) ){
		finalize($r2, ['disallowed_action', 'error']);
	}

	// Required Fields
	if( !isset($_POST['name']) || !isset($_POST['type']) ){
		finalize($r2, ['required_field', 'error']);
	}
	
	// Check existence
	$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
	if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
	$collection = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		finalize($r2, ['unauthorized', 'error']);
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if( !in_array((string)$type, $valid_coll_types, True) ){
		finalize($r2, ['invalid_value', 'error']);
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
			finalize($r2, ['invalid_value', 'error']);
		} else {
			$columns[$col] = $_POST[$col];
		}
	}

	if( isset($_POST['rating_system']) ){
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if( !in_array((int)$rating_system, [3,5,10,20,100], True) ){
			finalize($r2, ['invalid_value', 'error']);
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
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }

	finalize($r2, ['success']);
}





elseif( $action === 'collection_delete' || $action === 'collection_undelete' ){
	if( !isset($_POST['collection_id']) ){
		finalize($r2, ['disallowed_action', 'error']);
	}

	if( $action === 'collection_delete' ){
		$delete = 1;
	} elseif( $action === 'collection_undelete' ){
		$delete = 0;
	}

	// Check existence
	$stmt = sql('SELECT id, user_id FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
	if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
	$collection = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE collections SET deleted=? WHERE id=?', ['ii', $delete, $collection['id']]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }

	if( $action === 'collection_delete' ){
		$r2 = '/collection';
	}

	finalize($r2, ['success']);
}





elseif( $action === "collection_item_create" || $action === "collection_item_edit" ){
	if( $action === "collection_item_create" ){
		if( !isset($_POST['collection_id']) ){
			finalize($r2, ['disallowed_action', 'error']);
		}

		// Get info
		$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
		if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
		if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
		$collection = $stmt->rows[0];
	} elseif( $action === "collection_item_edit" ){
		if( !isset($_POST['item_id']) ){
			finalize($r2, ['disallowed_action', 'error']);
		}

		// Get item info
		$stmt = sql('SELECT image FROM media WHERE id=?', ['i', $_POST['item_id']]);
		if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
		if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
		$item = $stmt->rows[0];

		// Get collection info
		$stmt = sql('SELECT collections.id, collections.user_id, collections.rating_system FROM collections INNER JOIN media ON collections.id = media.collection_id WHERE media.id=?', ['i', $_POST['item_id']]);
		if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
		if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
		$collection = $stmt->rows[0];
	}

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		finalize('/collection', ['unauthorized', 'error']);
	}
	
	// Required fields
	if( !isset($_POST['name']) || !isset($_POST['status']) ){
		finalize($r2, ['required_field', 'error']);
	}


	// Define base variables
	$status = 'planned';
	$image_location = isset($item['image']) ? $item['image'] : '';
	$name = trim($_POST['name']);
	$score = 0;
	$episodes = 0;
	$progress = 0;
	$rewatched = 0;
	$user_started_at = null;
	$user_finished_at = null;
	$release_date = null;
	$started_at = null;
	$finished_at = null;
	$comments = '';
	$links = '';
	$adult = 0;
	$favourite = 0;
	$private = 0;


	// Validate status
	if( array_key_exists('status', $_POST) ){
		$status = (string)$_POST['status'];

		if( !in_array($status, $valid_status, True) ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Validate Score
	if( array_key_exists('score', $_POST) ){
		$score = (int)$_POST['score'];
		if( $score < 0 || $score > $collection['rating_system'] ){
			finalize($r2, ['invalid_value', 'error']);
		}
		$score = score_normalize($score, $collection['rating_system']);
	}

	// Validate and upload image
	if( array_key_exists('image', $_FILES) && $_FILES['image']['name'] !== '' ){
		$uploaded = upload_image($_FILES['image'], 'media_cover');
		if( !$uploaded->ok ){
			finalize($r2, [$uploaded->notice->code, $uploaded->notice->message, $uploaded->notice->details]);
		} else {
			$image_location = $uploaded->path;
		}
	}

	// Validate Episodes
	if( array_key_exists('progress', $_POST) ){
		$progress = (int)$_POST['progress'];
		if( $progress < 0 ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	if( array_key_exists('episodes', $_POST) ){
		$episodes = (int)$_POST['episodes'];
		if( $episodes < 0 ){
			finalize($r2, ['invalid_value', 'error']);
		}
		// Increase total episodes to match watched episodes if needed
		if( $episodes < $progress ){
			$episodes = $progress;
		}
	}

	if( array_key_exists('rewatched', $_POST) ){
		$rewatched = (int)$_POST['rewatched'];
		if( $rewatched < 0 ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Modify episodes to make sense if item completed.
	if( $status === 'completed' && $episodes >= $progress ){
		$progress = $episodes;
	}


	// Validate Dates
	function validate_date($date) {
		global $r2;

		// strings should match this format: YYYY-MM-DD

		$split = explode('-', $date);
		$y = $split[0];
		$m = $split[1];
		$d = $split[2];

		if(
			// total length
			count($split) !== 3
			// basic length formatting
			|| strlen((string)$y) !== 4
			|| strlen((string)$m) !== 2
			|| strlen((string)$d) !== 2
			// valid ranges - min and max dates are as defined in SQL: 1000-01-01 to 9999-12-31
			|| (int)$y < 1000
			|| (int)$y > 9999
			|| (int)$m < 1
			|| (int)$m > 12
			|| (int)$d < 1
			|| (int)$d > 31 // yes, this will accept invalid day ranges. this is dealt with below
			) {
				finalize($r2, ['invalid_value', 'error']);
		}

		// Uses PHP date functions to validate that the year/day is actually valid
		// TODO - a lot of the above IF checks can probably be scrapped in favour of just using this str->date->str method
		$dateObj = date_create_from_format('Y-m-d', $date);
		$date = date_format($dateObj, 'Y-m-d');

		// If all checks passed, return
		return $date;
	}

	if( array_key_exists('user_started_at', $_POST) && $_POST['user_started_at'] !== '' ){
		$user_started_at = validate_date($_POST['user_started_at']);
	}

	if( array_key_exists('user_finished_at', $_POST) && $_POST['user_finished_at'] !== '' ){
		$user_finished_at = validate_date($_POST['user_finished_at']);
	}

	if( array_key_exists('release_date', $_POST) && $_POST['release_date'] !== '' ){
		$release_date = validate_date($_POST['release_date']);
	}

	if( array_key_exists('started_at', $_POST) && $_POST['started_at'] !== '' ){
		$started_at = validate_date($_POST['started_at']);
	}

	if( array_key_exists('finished_at', $_POST) && $_POST['finished_at'] !== '' ){
		$finished_at = validate_date($_POST['finished_at']);
	}

	// Validate comments
	if( array_key_exists('comments', $_POST) ){
		$comments = $_POST['comments'];
		$maxlen = pow(2,16) - 1;
		if( strlen($comments) > $maxlen ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Links
	if( array_key_exists('links', $_POST) && is_array($_POST['links']) ){
		$validatedLinks = [];
		foreach( $_POST['links'] as $link ){
			$link = trim($link);
			if($link !== ""
			&& filter_var($link, FILTER_VALIDATE_URL) === False
			&& strpos($link, 'http') === 0) {
				$validatedLinks[] = $link;
			}
		}
		$links = json_encode($validatedLinks);
	}

	// Flags
	if( array_key_exists('adult', $_POST) ){
		$adult = $_POST['adult'];
		if( $adult < 0 || $adult > 1 ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	if( array_key_exists('favourite', $_POST) ){
		$favourite = $_POST['favourite'];
		if( $favourite < 0 || $favourite > 1 ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	if( array_key_exists('private', $_POST) ){
		$private = $_POST['private'];
		if( $private < 0 || $private > 1 ){
			finalize($r2, ['invalid_value', 'error']);
		}
	}


	// Execute DB
	if( $action === "collection_item_create" ){
		$stmt = sql('
			INSERT INTO media (
				user_id,
				collection_id,
				status,
				image,
				name,
				score,
				episodes,
				progress,
				rewatched,
				user_started_at,
				user_finished_at,
				release_date,
				started_at,
				finished_at,
				comments,
				links,
				adult,
				favourite,
				private
			)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		', [
			'iisssiiiisssssssiii',
			$user['id'],
			$collection['id'],
			$status,
			$image_location,
			$name,
			$score,
			$episodes,
			$progress,
			$rewatched,
			$user_started_at,
			$user_finished_at,
			$release_date,
			$started_at,
			$finished_at,
			$comments,
			$links,
			$adult,
			$favourite,
			$private
		]);
	} elseif( $action === "collection_item_edit" ){
		$stmt = sql('
			UPDATE media SET
				status=?,
				image=?,
				name=?,
				score=?,
				episodes=?,
				progress=?,
				rewatched=?,
				user_started_at=?,
				user_finished_at=?,
				release_date=?,
				started_at=?,
				finished_at=?,
				comments=?,
				links=?,
				adult=?,
				favourite=?,
				private=?
			WHERE id=?
		', [
			'sssiiiisssssssiiii',
			$status,
			$image_location,
			$name,
			$score,
			$episodes,
			$progress,
			$rewatched,
			$user_started_at,
			$user_finished_at,
			$release_date,
			$started_at,
			$finished_at,
			$comments,
			$links,
			$adult,
			$favourite,
			$private,
			$_POST['item_id']
		]);
	}
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }

	if( $action === 'collection_item_create' ){
		// Get newly added ID
		$stmt = sql('SELECT LAST_INSERT_ID()');
		if( $stmt->rows !== false ){
			$new_item_id = reset($stmt->rows[0]);
			$r2 = $r2.'#item-'.$new_item_id;
		}
		
		// Create activity
		$stmt = sql('INSERT INTO activity (user_id, type, media_id) VALUES (?, ?, ?)', ['iii', $user['id'], $activity_types[$status], $new_item_id]);
		if( !$stmt->ok ){
			$details = 'Primary action performed successfully. Secondary action of creating activity post failed.';
		}
	}
	
	if( isset($details) ){
		finalize($r2, ['blank', 'generic', $details]);
	}
	finalize($r2, ['success', 'generic']);
}





if( $action === 'collection_item_delete' || $action === 'collection_item_undelete' ){
	if( !isset($_POST['item_id']) ){
		finalize($r2, ['disallowed_action', 'error']);
	}

	if( $action === 'collection_item_delete' ){
		$delete = 1;
	} elseif( $action === 'collection_item_undelete' ){
		$delete = 0;
	}

	// Get info & check existence
	$stmt = sql('SELECT id, user_id, collection_id FROM media WHERE id=?', ['i', $_POST['item']]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }
	if( $stmt->row_count < 1 ){ finalize($r2, ['disallowed_action', 'error']); }
	$item = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $item['user_id'] ){
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE media SET deleted=? WHERE id=?', ['ii', $delete, $item['id']]);
	if( !$stmt->ok ){ finalize($r2, [$stmt->response_code, $stmt->response_type]); }

	finalize($r2, ['success']);
}





elseif( $action === 'change_settings' ){
	// ALL SETTINGS
	if( !$has_session ){
		finalize($r2, ['require_sign_in', 'error']);
	}

	// Variables which will be added onto as settings are changed.

	$to_update = [];
	$error_list = [];

	// $to_update contains information on what SQL to update.
	// It will follow the format: table => [column, type, value]
	// It may look something like this:
	//
	// $to_update = [
	//   'users' => [
	//     [
	//       'column' =>'nickname',
	//       'type' => 's',
	//       'value' => 'xXSnipesXx'
	// 	   ],
	// 	   [
	//       'column' =>'password',
	//       'type' => 's',
	//       'value' => '12345'
	// 	   ]
	//   ],	
	//   'user_preferences' => [
	// 	   [
	//       'column' =>'timezone',
	//       'type' => 's',
	//       'value' => 'UTC'
	//     ],
	//   ]	
	// ]

	// $error_list contains multiple sub-arrays containing error code/type/detail combinations to be fed to finalize()
	//
	// $error_list = [
	// 	 ['database_failure', 'error', 'About was not updated.'],
	// 	 ['invalid_name', 'error'],
	// 	 ['blank', 'generic', 'Just a notice']
	// ]


	$user_extra = sql('SELECT about FROM users WHERE id=?', ['i', $user['id']]);

	// NICKNAME
	if( isset($_POST['nickname']) ){
		$nick = trim($_POST['nickname']);

		// If not valid input
		if( !valid_name($nick) || $nick === '' ){
			$error_list[] = ['invalid_name', 'error'];
		}
		// If value valid and not the same as before 
		elseif( $nick !== $user['nickname'] ){
			$to_update['users'][] = [
				'column' => 'nickname',
				'type' => 's',
				'value' => $nick
			];
		}
	}

	// ABOUT
	if( isset($_POST['about']) ){
		$about = $_POST['about'];

		// If value not the same as before
		if( $about !== $user_extra->rows[0]['about'] ){
			// If valid, continue
			$to_update['users'][] = [
				'column' => 'about',
				'type' => 's',
				'value' => $about
			];
		}
	}

	// PROFILE IMAGE
	if( array_key_exists('profile_image', $_FILES) && $_FILES['profile_image']['name'] !== '' ){
		$uploaded = upload_image($_FILES['profile_image'], 'user_avatar');
		if( !$uploaded->ok ){
			$error_list[] = [$uploaded->notice->code, $uploaded->notice->message, $uploaded->notice->details];
		} else {
			$to_update['users'][] = [
				'column' => 'profile_image',
				'type' => 's',
				'value' => $uploaded->path
			];
		}
	}

	// BANNER IMAGE
	if( array_key_exists('banner_image', $_FILES) && $_FILES['banner_image']['name'] !== '' ){
		$uploaded = upload_image($_FILES['banner_image'], 'user_banner');
		if( !$uploaded->ok ){
			$error_list[] = [$uploaded->notice->code, $uploaded->notice->message, $uploaded->notice->details];
		} else {
			$to_update['users'][] = [
				'column' => 'banner_image',
				'type' => 's',
				'value' => $uploaded->path
			];
		}
	}

	// EMAIL
	//if( isset($_POST['email']) ){
	//	
	//}

	// PASSWORD
	// TODO - a lot of this password validation should be done in a single place instead of repeatedly, to avoid future problems.
	// Currently, the password validation can be found here, in session.php, and in the Authentication class of server.php 
	if( isset($_POST['previous_password']) ){
		if(
			!array_key_exists('new_password', $_POST)
			|| !array_key_exists('new_password_confirm', $_POST)
			|| strlen($_POST['previous_password']) === 0
			|| strlen($_POST['new_password']) === 0
			|| strlen($_POST['new_password_confirm']) === 0
		) {
			$error_list[] = ['blank', 'error', 'Please complete all three password fields.'];
		} else {
			$prev = $_POST['previous_password'];
			$new = $_POST['new_password'];
			$conf = $_POST['new_password_confirm'];

			$stmt = sql('SELECT password FROM users WHERE id=?', ['i', $user['id']]);
			if( !$stmt->ok || $stmt->row_count < 1 ){
				$error_list[] = [$stmt['error_code'], $stmt['error_type']]; 
			}
			elseif( !password_verify($prev, $stmt->rows[0]['password']) ){
				$error_list[] = ['blank', 'error', 'Current password was incorrect.'];
			}
			elseif( $new !== $conf ){
				$error_list[] = ['register_match', 'error'];
			}
			elseif( strlen($new) < 6 || strlen($new) > 72 ){
				$error_list[] = ['invalid_pass', 'error'];
			}
			else {
				// If valid, continue
				$new_hashed = password_hash($new, PASSWORD_BCRYPT);
				
				$to_update['users'][] = [
					'column' => 'password',
					'type' => 's',
					'value' => $new_hashed
				];
			}
		}
	}

	// TIMEZONE
	if( isset($_POST['timezone']) ){
		$tz = $_POST['timezone'];

		// If value not the same as before
		if( $tz !== $user['timezone'] ){
			// If not valid input
			$needle = false;
			foreach( $valid_timezones as $zone_group ){
				if( in_array($tz, $zone_group, True) ){
					$needle = true;
					break;
				}
			}

			if( $needle === false ){
				$error_list[] = ['invalid_value', 'error', 'Please choose a valid timezone'];
			}

			// If valid, continue
			$to_update['users'][] = [
				'column' => 'timezone',
				'type' => 's',
				'value' => $tz
			];
		}
	}

	// PROFILE COLOUR
	if( array_key_exists('reset_profile_colour', $_POST) && $_POST['reset_profile_colour'] == 1 ){
		$to_update['users'][] = [
			'column' => 'profile_colour',
			'type' => 's',
			'value' => null
		];
	}
	elseif( isset($_POST['profile_colour']) ){
		$col = $_POST['profile_colour'];

		// If value not the same as before and not default
		// TODO - hardcoding the default colour like this and preventing users from permanently setting it is unwanted behaviour. Improve this later.
		if( $col !== $user['profile_colour'] && $col !== '#ff3333' ){
			// If not valid input
			if(
				strlen($col) !== 7
				|| preg_match('/#([a-f0-9]{3}){1,2}\b/i', $col) < 1
			) {
				$error_list[] = ['blank', 'error', 'Please choose a valid profile colour.'];
			}

			// If valid, continue
			$to_update['users'][] = [
				'column' => 'profile_colour',
				'type' => 's',
				'value' => $col
			];
		}
	}

	// Start updating database
	if( count($to_update) > 0 ){
		// Setup basic variables
		$columns = [];
		$types = '';
		$values = [];
		
		foreach( $to_update as $table => $updates ){
			if( $table === 'users' ){
				$query = "UPDATE {$table} SET %columns% WHERE id=?";
			} else {
				$query = "UPDATE {$table} SET %columns% WHERE user_id=?";
			}

			$query = str_replace('%table%', $table, $query);

			foreach( $updates as $upd ){
				$columns[] = $upd['column'].'=?';
				$types .= $upd['type'];
				$values[] = $upd['value'];
			}
		}

		// Format columns
		$columns = implode(', ', $columns);
		$query = str_replace('%columns%', $columns, $query);

		// Add user ID onto end of params.
		$types .= 'i';
		$values[] = $user['id'];
		$params = array_merge([$types], $values);

		// Execute statement
		$stmt = sql($query, $params);
		if( !$stmt->ok ){
			$error_list[] = [$stmt->response_code, $stmt->response_type, $query.var_export($params, true)];
		} else {
			$changed = true;
		}
	} else {
		$changed = false;
	}

	// Finalize
	if( $changed && count($error_list) > 0 ){
		finalize($r2, ['partial_success'], ...$error_list);
	} elseif( $changed ){
		finalize($r2, ['success']);
	} else {
		finalize($r2, ['no_change_detected'], ...$error_list);
	}
}





elseif( $action === 'import-list' ){
	if( !array_key_exists('file', $_POST) ){
		finalize($r2, ['required_field', 'error']);
	}

	finalize($r2, ['blank', 'generic', 'feature not implemented yet']);
}





// File should only reach this point if no other actions have reached finalization.
finalize($r2, ['disallowed_action', 'error']);
?>