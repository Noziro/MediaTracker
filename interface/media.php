<?php
require_once $_SERVER["DOCUMENT_ROOT"].'/server/server.php';

if( API_ACTION === "/media/create" || API_ACTION === "/media/edit" ){
	if( API_ACTION === "/media/create" ){
		if( !isset($_POST['collection_id']) ){
			bailout($return_to, 'disallowed_action');
		}

		// Get info
		$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
		if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
		if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
		$collection = $stmt->rows[0];
	} elseif( API_ACTION === "/media/edit" ){
		if( !isset($_POST['item_id']) ){
			bailout($return_to, 'disallowed_action');
		}

		// Get item info
		$stmt = sql('SELECT image FROM media WHERE id=?', ['i', $_POST['item_id']]);
		if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
		if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
		$item = $stmt->rows[0];

		// Get collection info
		$stmt = sql('SELECT collections.id, collections.user_id, collections.rating_system FROM collections INNER JOIN media ON collections.id = media.collection_id WHERE media.id=?', ['i', $_POST['item_id']]);
		if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
		if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
		$collection = $stmt->rows[0];
	}

	// Check user authority
	if( $user['id'] !== $collection['user_id'] ){
		bailout('/collection', 'unauthorized');
	}
	
	// Required fields
	if( !isset($_POST['name']) || !isset($_POST['status']) ){
		bailout($return_to, 'required_field');
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

		if( !in_array($status, VALID_STATUS, True) ){
			bailout($return_to, 'invalid_value');
		}
	}

	// Rating System for Scoring
	$rating_system = $collection['rating_system'];
	if( isset($_POST['rating_system']) ){
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if( !in_array((int)$rating_system, [3,5,10,20,100], True) ){
			bailout($return_to, 'invalid_value');
		}
	}

	// Validate Score
	if( array_key_exists('score', $_POST) ){
		$score = (int)$_POST['score'];
		if( $score < 0 || $score > $rating_system ){
			bailout($return_to, 'invalid_value');
		}
		$score = score_normalize($score, $rating_system);
	}

	// Validate and upload image
	if( array_key_exists('image', $_FILES) && $_FILES['image']['name'] !== '' ){
		require_once PATH.'server/upload.php';
		$uploaded = upload_image($_FILES['image'], 'media_cover');
		if( !$uploaded->ok ){
			bailout($return_to, $uploaded->notice->code, $uploaded->notice->details);
		} else {
			$image_location = $uploaded->path;
		}
	}
	// If image not set, check for an image_url. This key is used on item/add pages when cloning.
	elseif( isset($_POST['image_url']) ){
		$old_relative_path = (string) $_POST['image_url'];
		$base_path = rtrim(getenv('DATA_DIR') !== false ? getenv('DATA_DIR') : PATH, '/');
		$old_full_path = $base_path.$old_relative_path;
		if( is_file($old_full_path) ){
			$ext = '.'.nth_last(explode('.', $old_relative_path));
			$new_relative_path = '/upload/media_cover/'.generate_random_characters(64).$ext;
			$new_full_path = $base_path.$new_relative_path;
			if( !copy($old_full_path, $new_full_path) ){
				bailout($return_to, 'file_copy_failure');
			}
			$image_location = $new_relative_path;
		}
	}

	// Validate Episodes
	if( array_key_exists('progress', $_POST) ){
		$progress = (int)$_POST['progress'];
		if( $progress < 0 ){
			bailout($return_to, 'invalid_value');
		}
	}

	if( array_key_exists('episodes', $_POST) ){
		$episodes = (int)$_POST['episodes'];
		if( $episodes < 0 ){
			bailout($return_to, 'invalid_value');
		}
		// Increase total episodes to match watched episodes if needed
		if( $episodes < $progress ){
			$episodes = $progress;
		}
	}

	if( array_key_exists('rewatched', $_POST) ){
		$rewatched = (int)$_POST['rewatched'];
		if( $rewatched < 0 ){
			bailout($return_to, 'invalid_value');
		}
	}

	// Modify episodes to make sense if item completed.
	if( $status === 'completed' && $episodes >= $progress ){
		$progress = $episodes;
	}


	// Validate Dates
	function validate_date($date) {
		global $return_to;

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
				bailout($return_to, 'invalid_value');
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
			bailout($return_to, 'invalid_value');
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
			bailout($return_to, 'invalid_value');
		}
	}

	if( array_key_exists('favourite', $_POST) ){
		$favourite = $_POST['favourite'];
		if( $favourite < 0 || $favourite > 1 ){
			bailout($return_to, 'invalid_value');
		}
	}

	if( array_key_exists('private', $_POST) ){
		$private = $_POST['private'];
		if( $private < 0 || $private > 1 ){
			bailout($return_to, 'invalid_value');
		}
	}


	// Execute DB
	if( API_ACTION === "/media/create" ){
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
	} elseif( API_ACTION === "/media/edit" ){
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
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }

	if( API_ACTION === '/media/create' ){
		// Get newly added ID
		$stmt = sql('SELECT LAST_INSERT_ID()');
		if( $stmt->rows !== false ){
			$new_item_id = reset($stmt->rows[0]);
			$return_to = $return_to.'#item-'.$new_item_id;
		}
		
		// Create activity
		$stmt = sql('INSERT INTO activity (user_id, type, media_id) VALUES (?, ?, ?)', ['iii', $user['id'], VALID_ACTIVITY_TYPES[$status], $new_item_id]);
		if( !$stmt->ok ){
			$details = 'Primary action performed successfully. Secondary action of creating activity post failed.';
		}
	}
	
	if( isset($details) ){
		bailout($return_to, 'blank', $details);
	}
	bailout($return_to, 'success');
}





if( API_ACTION === '/media/delete' || API_ACTION === '/media/undelete' ){
	if( !isset($_POST['item_id']) ){
		bailout($return_to, 'disallowed_action');
	}

	if( API_ACTION === '/media/delete' ){
		$delete = 1;
	} elseif( API_ACTION === '/media/undelete' ){
		$delete = 0;
	}

	// Get info & check existence
	$stmt = sql('SELECT id, user_id, collection_id FROM media WHERE id=?', ['i', $_POST['item']]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }
	if( $stmt->row_count < 1 ){ bailout($return_to, 'disallowed_action'); }
	$item = $stmt->rows[0];

	// Check user authority
	if( $user['id'] !== $item['user_id'] ){
		bailout($return_to, 'unauthorized');
	}

	// Execute DB
	$stmt = sql('UPDATE media SET deleted=? WHERE id=?', ['ii', $delete, $item['id']]);
	if( !$stmt->ok ){ bailout($return_to, $stmt->response_code); }

	bailout($return_to, 'success');
}
?>