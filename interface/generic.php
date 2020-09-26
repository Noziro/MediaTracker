<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

define("PATH", $_SERVER["DOCUMENT_ROOT"] . "/");
include PATH.'server/server.php';

// AUTH

if(!$has_session)  {
	finalize('/', ['require_sign_in', 'error']); 
}

$action = $_POST['action'];

// RETURN TO

if(isset($_POST['return_to'])) {
	$r2 = $_POST['return_to'];
} else {
	$r2 = '/';
}




// ACTIONS

if($action === "forum_thread_create") {
	if(!isset($_POST['board_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}
	
	// Check required fields
	if(!isset($_POST['title']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, ['required_field', 'error']);
	}

	// Get info
	$stmt = sql('SELECT id, permission_level FROM boards WHERE id=?', ['i', $_POST['board_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$board = $stmt['result'][0];
	
	// Check user authority
	if($permission_level < $board['permission_level']) { 
		finalize($r2, ['unauthorized', 'error']);
	}

	$title = trim($_POST['title']);
	$body = $_POST['body'];

	// Add thread to DB
	$stmt = sql('INSERT INTO threads (user_id, board_id, title) VALUES (?, ?, ?)', ['iis', $user['id'], $board['id'], $title]);
	if(!stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	// Get newly added thread ID
	$stmt = sql('SELECT LAST_INSERT_ID()');
	if($stmt !== false) {
		$new_thread_id = reset($stmt['result'][0]);
		$r2 = '/forum/thread/'.$new_thread_id;
	}

	// Add thread reply to DB
	$stmt = sql('INSERT INTO replies (user_id, thread_id, body) VALUES (?, ?, ?)', ['iis', $user['id'], $new_thread_id, $body]);
	if(!stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	finalize($r2);
}





elseif($action === "forum_reply_create") {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	// Check required fields
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, ['required_field', 'error']);
	}

	// Get info
	$stmt = sql('SELECT threads.id, threads.board_id, threads.locked, boards.permission_level FROM threads INNER JOIN boards ON threads.board_id = boards.id WHERE threads.id=?', ['i', $_POST['thread_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$thread = $stmt['result'][0];

	// Check user authority
	if($thread['locked'] === 1 && $permission_level < $permission_levels['Moderator'] || $permission_level < $thread['permission_level']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('INSERT INTO replies (user_id, thread_id, body) VALUES (?, ?, ?)', ['iis', $user['id'], $thread['id'], $_POST['body']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	// Set thread updated_at date for sorting purposes
	$stmt = sql('UPDATE threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?', ['i', $thread['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	finalize($r2, ['success']);
}





elseif($action === 'forum_thread_lock' || $action === 'forum_thread_unlock') {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	if($action === 'forum_thread_lock') {
		$lock = 1;
	} elseif($action === 'forum_thread_unlock') {
		$lock = 0;
	}

	// Check user authority
	if($permission_level < $permission_levels['Moderator']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Check existence
	$stmt = sql('SELECT id FROM threads WHERE id=?', ['i', $_POST['thread_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$thread = $stmt['result'][0];

	// Execute DB
	$stmt = sql('UPDATE threads SET locked=? WHERE id=?', ['ii', $lock, $thread['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	finalize($r2, ['success']);
}





elseif($action === 'forum_thread_delete' || $action === 'forum_thread_undelete') {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	if($action === 'forum_thread_delete') {
		$delete = 1;
	} elseif($action === 'forum_thread_undelete') {
		$delete = 0;
	}

	// Check existence
	$stmt = sql('SELECT threads.id, threads.board_id, threads.user_id, boards.permission_level FROM threads INNER JOIN boards ON threads.board_id = boards.id WHERE threads.id=?', ['i', $_POST['thread_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$thread = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $thread['user_id'] && $permission_level < $thread['permission_level']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE threads SET deleted=? WHERE id=?', ['ii', $delete, $thread['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	if($action === 'forum_thread_delete') {
		$r2 = '/forum/board/'.$thread['board_id'];
	}

	finalize($r2, ['success']);
}





elseif($action === "forum_reply_edit") {
	if(!isset($_POST['reply_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	// Check required fields
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, ['required_field', 'error']);
	}

	// Check existence
	$stmt = sql('SELECT id, thread_id, user_id FROM replies WHERE id=?', ['i', $_POST['reply_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$reply = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $reply['user_id']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE replies SET body=?, updated_at=CURRENT_TIMESTAMP WHERE id=?', ['si', $_POST['body'], $reply['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	finalize($r2, ['success']);
}



elseif($action === 'forum_reply_delete' || $action === 'forum_reply_undelete') {
	if(!isset($_POST['reply_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	if($action === 'forum_reply_delete') {
		$delete = 1;
	} elseif($action === 'forum_reply_undelete') {
		$delete = 0;
	}
	
	// Check existence
	$stmt = sql('SELECT id, thread_id, user_id FROM replies WHERE id=?', ['i', $_POST['reply_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$reply = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $reply['user_id'] && $permission_level < $permission_levels['Moderator']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE replies SET deleted=? WHERE id=?', ['ii', $delete, $reply['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	// Set thread anonymous if deleting first post.
	$stmt = sql('SELECT id FROM replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 1', ['i', $reply['thread_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	$first_reply_id = $stmt['result'][0]['id'];

	if($first_reply_id === $reply['id']) {
		$stmt = sql('UPDATE threads SET anonymous=? WHERE id=?', ['ii', $delete, $reply['thread_id']]);
		if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	}
	
	finalize($r2, ['success']);
}





elseif($action === "collection_create") {
	// Required fields
	if(!isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($r2, ['required_field', 'error']);
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if(!in_array((string)$type, $valid_coll_types, True)) {
		finalize($r2, ['invalid_value', 'error']);
	}

	if(!isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True)) {
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	// Execute DB
	$stmt = sql('INSERT INTO collections (user_id, name, type, private) VALUES (?, ?, ?, ?)', ['issi', $user['id'], $name, $type, $private]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	
	finalize($r2, ['success']);
}





elseif($action === "collection_edit") {
	if(!isset($_POST['collection_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	// Required Fields
	if(!isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($r2, ['required_field', 'error']);
	}
	
	// Check existence
	$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$collection = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $collection['user_id']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if(!in_array((string)$type, $valid_coll_types, True)) {
		finalize($r2, ['invalid_value', 'error']);
	}

	if(!isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True)) {
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	$columns = [
		'display_score' => 1,
		'display_progress' => 1,
		'display_user_started' => 1,
		'display_user_finished' => 1,
		'display_days' => 1
	];

	foreach($columns as $col => $val) {
		if(!isset($_POST[$col])) {
			finalize($r2, ['invalid_value', 'error']);
		} else {
			$columns[$col] = $_POST[$col];
		}
	}

	if(isset($_POST['rating_system'])) {
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if(!in_array((int)$rating_system, [3,5,10,20,100], True)) {
			finalize($r2, ['invalid_value', 'error']);
		}
	} else {
		$rating_system = $collection['rating_system'];
	}

	// Execute DB
	$stmt = sql('UPDATE collections SET
		name=?,
		type=?,
		display_score=?,
		display_progress=?,
		display_user_started=?,
		display_user_finished=?,
		display_days=?,
		rating_system=?,
		private=?
		WHERE id=?
	', [
		'sssssssisi',
		$name,
		$type,
		$columns['display_score'],
		$columns['display_progress'],
		$columns['display_user_started'],
		$columns['display_user_finished'],
		$columns['display_days'],
		$rating_system,
		$private,
		$collection['id']
	]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	finalize($r2, ['success']);
}





elseif($action === 'collection_delete' || $action === 'collection_undelete') {
	if(!isset($_POST['collection_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	if($action === 'collection_delete') {
		$delete = 1;
	} elseif($action === 'collection_undelete') {
		$delete = 0;
	}

	// Check existence
	$stmt = sql('SELECT id, user_id FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$collection = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $collection['user_id']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE collections SET deleted=? WHERE id=?', ['ii', $delete, $collection['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	if($action === 'forum_thread_delete') {
		$r2 = '/collection/user/'.$user['id'];
	}

	finalize($r2, ['success']);
}





elseif($action === "collection_item_create" || $action === "collection_item_edit") {
	if($action === "collection_item_create") {
		if(!isset($_POST['collection_id'])) {
			finalize($r2, ['disallowed_action', 'error']);
		}

		// Get info
		$stmt = sql('SELECT id, user_id, rating_system FROM collections WHERE id=?', ['i', $_POST['collection_id']]);
		if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
		if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
		$collection = $stmt['result'][0];
	} elseif($action === "collection_item_edit") {
		if(!isset($_POST['item_id'])) {
			finalize($r2, ['disallowed_action', 'error']);
		}

		// Get info
		$stmt = sql('SELECT collections.id, collections.user_id, collections.rating_system FROM collections INNER JOIN media ON collections.id = media.collection_id WHERE media.id=?', ['i', $_POST['item_id']]);
		if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
		if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
		$collection = $stmt['result'][0];
	}

	// Check user authority
	if($user['id'] !== $collection['user_id']) {
		finalize('/collection', ['unauthorized', 'error']);
	}
	
	// Required fields
	if(!isset($_POST['name']) || !isset($_POST['status'])) {
		finalize($r2, ['required_field', 'error']);
	}


	// Define base variables
	$status = 'planned';
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

	// Validate status
	if(array_key_exists('status', $_POST)) {
		$status = (string)$_POST['status'];

		if(!in_array($status, $valid_status, True)) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Validate Score
	if(array_key_exists('score', $_POST)) {
		$score = (int)$_POST['score'];
		if($score < 0 || $score > $collection['rating_system']) {
			finalize($r2, ['invalid_value', 'error']);
		}
		$score = score_normalize($score, $collection['rating_system']);
	}


	// Validate Episodes
	if(array_key_exists('progress', $_POST)) {
		$progress= (int)$_POST['progress'];
		if($progress < 0) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	if(array_key_exists('episodes', $_POST)) {
		$episodes = (int)$_POST['episodes'];
		if($episodes < 0) {
			finalize($r2, ['invalid_value', 'error']);
		}
		// Increase total episodes to match watched episodes if needed
		if($episodes < $progress) {
			$episodes = $progress;
		}
	}

	if(array_key_exists('rewatched', $_POST)) {
		$rewatched = (int)$_POST['rewatched'];
		if($rewatched < 0) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Modify episodes to make sense if item completed.
	if($status === 'completed' && $episodes >= $progress) {
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

	if(array_key_exists('user_started_at', $_POST) && $_POST['user_started_at'] !== '') {
		$user_started_at = validate_date($_POST['user_started_at']);
	}

	if(array_key_exists('user_finished_at', $_POST) && $_POST['user_finished_at'] !== '') {
		$user_finished_at = validate_date($_POST['user_finished_at']);
	}

	if(array_key_exists('release_date', $_POST) && $_POST['release_date'] !== '') {
		$release_date = validate_date($_POST['release_date']);
	}

	if(array_key_exists('started_at', $_POST) && $_POST['started_at'] !== '') {
		$started_at = validate_date($_POST['started_at']);
	}

	if(array_key_exists('finished_at', $_POST) && $_POST['finished_at'] !== '') {
		$finished_at = validate_date($_POST['finished_at']);
	}

	// Validate comments
	if(array_key_exists('comments', $_POST)) {
		$comments = $_POST['comments'];
		$maxlen = pow(2,16) - 1;
		if(strlen($comments) > $maxlen) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	// Links
	if(array_key_exists('links', $_POST) && is_array($_POST['links'])) {
		$validatedLinks = [];
		foreach($_POST['links'] as $link) {
			$link = trim($link);
			if($link !== ""
			&& filter_var($url, FILTER_VALIDATE_URL) === False
			&& strpos($link, 'http') === 0) {
				$validatedLinks[] = $link;
			}
		}
		$links = json_encode($validatedLinks);
	}

	// Flags
	if(array_key_exists('adult', $_POST)) {
		$adult = $_POST['adult'];
		if($adult < 0 || $adult > 1) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}

	if(array_key_exists('favourite', $_POST)) {
		$favourite = $_POST['favourite'];
		if($favourite < 0 || $favourite > 1) {
			finalize($r2, ['invalid_value', 'error']);
		}
	}


	// Execute DB
	if($action === "collection_item_create") {
		$stmt = sql('
			INSERT INTO media (
				user_id,
				collection_id,
				status,
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
				favourite
			)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		', [
			'iissiiiisssssssii',
			$user['id'],
			$collection['id'],
			$status,
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
			$favourite
		]);
	} elseif($action === "collection_item_edit") {
		$stmt = sql('
			UPDATE media SET
				status=?,
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
				favourite=?
			WHERE id=?
		', [
			'ssiiiisssssssiii',
			$status,
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
			$_POST['item_id']
		]);
	}
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	if($action === 'collection_item_create') {
		// Get newly added ID
		$stmt = sql('SELECT LAST_INSERT_ID()');
		if($stmt['result'] !== false) {
			$new_item_id = reset($stmt['result'][0]);
			$r2 = $r2.'#item-'.$new_item_id;
		}
		
		// Create activity
		$stmt = sql('INSERT INTO activity (user_id, type, media_id) VALUES (?, ?, ?)', ['iii', $user['id'], $activity_types[$status], $new_item_id]);
		if(!$stmt['result']) {
			$details = 'Primary action performed successfully. Secondary action of creating activity post failed.';
		}
	}
	
	if(isset($details)) {
		finalize($r2, ['blank', 'generic', $details]);
	}
	finalize($r2, ['success', 'generic']);
}





if($action === 'collection_item_delete' || $action === 'collection_item_undelete') {
	if(!isset($_POST['item_id'])) {
		finalize($r2, ['disallowed_action', 'error']);
	}

	if($action === 'collection_item_delete') {
		$delete = 1;
	} elseif($action === 'collection_item_undelete') {
		$delete = 0;
	}

	// Get info & check existence
	$stmt = sql('SELECT id, user_id, collection_id FROM media WHERE id=?', ['i', $_POST['item']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }
	if($stmt['rows'] < 1) { finalize($r2, ['disallowed_action', 'error']); }
	$item = $stmt['result'][0];

	// Check user authority
	if($user['id'] !== $item['user_id']) {
		finalize($r2, ['unauthorized', 'error']);
	}

	// Execute DB
	$stmt = sql('UPDATE media SET deleted=? WHERE id=?', ['ii', $delete, $item['id']]);
	if(!$stmt['result']) { finalize($r2, [$stmt['response_code'], $stmt['response_type']]); }

	finalize($r2, ['success']);
}





elseif($action === 'change_settings') {
	// ALL SETTINGS
	if(!$has_session) {
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
	if(isset($_POST['nickname'])) {
		$nick = trim($_POST['nickname']);

		// If not valid input
		if(!valid_name($nick) || $nick === '') {
			$error_list[] = ['invalid_name', 'error'];
		}
		// If value valid and not the same as before 
		elseif($nick !== $user['nickname']) {
			$to_update['users'][] = [
				'column' => 'nickname',
				'type' => 's',
				'value' => $nick
			];
		}
	}

	// ABOUT
	if(isset($_POST['about'])) {
		$about = $_POST['about'];

		// If value not the same as before
		if($about !== $user_extra['about']) {
			// If valid, continue
			$to_update['users'][] = [
				'column' => 'about',
				'type' => 's',
				'value' => $about
			];
		}
	}

	// EMAIL
	//if(isset($_POST['email'])) {
	//	
	//}

	// PASSWORD
	// TODO - a lot of this password validation should be done in a single place instead of repeatedly, to avoid future problems.
	// Currently, the password validation can be found here, in session.php, and in the Authentication class of server.php 
	if(isset($_POST['previous_password'])) {
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
			if(!$stmt['result'] || $stmt['rows'] < 1) {
				$error_list[] = [$stmt['error_code'], $stmt['error_type']]; 
			}
			elseif(!password_verify($prev, $stmt['result'][0]['password'])) {
				$error_list[] = ['blank', 'error', 'Current password was incorrect.'];
			}
			elseif($new !== $conf) {
				$error_list[] = ['register_match', 'error'];
			}
			elseif(strlen($new) < 6 || strlen($new) > 72) {
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
	if(isset($_POST['timezone'])) {
		$tz = $_POST['timezone'];

		// If value not the same as before
		if($tz !== $prefs['timezone']) {
			// If not valid input
			$needle = false;
			foreach($valid_timezones as $zone_group) {
				if(in_array($tz, $zone_group, True)) {
					$needle = true;
					break;
				}
			}

			if($needle === false) {
				$error_list[] = ['invalid_value', 'error', 'Please choose a valid timezone'];
			}

			// If valid, continue
			$to_update['user_preferences'][] = [
				'column' => 'timezone',
				'type' => 's',
				'value' => $tz
			];
		}
	}

	// PROFILE COLOUR
	if(array_key_exists('reset_profile_colour', $_POST) && $_POST['reset_profile_colour'] == 1) {
		$to_update['user_preferences'][] = [
			'column' => 'profile_colour',
			'type' => 's',
			'value' => null
		];
	}
	elseif(isset($_POST['profile_colour'])) {
		$col = $_POST['profile_colour'];

		// If value not the same as before and not default
		// TODO - hardcoding the default colour like this and preventing users from permanently setting it is unwanted behaviour. Improve this later.
		if($col !== $prefs['profile_colour'] && $col !== '#ff3333') {
			// If not valid input
			if(
				strlen($col) !== 7
				|| preg_match('/#([a-f0-9]{3}){1,2}\b/i', $col) < 1
			) {
				$error_list[] = ['blank', 'error', 'Please choose a valid profile colour.'];
			}

			// If valid, continue
			$to_update['user_preferences'][] = [
				'column' => 'profile_colour',
				'type' => 's',
				'value' => $col
			];
		}
	}

	// Start updating database
	if(count($to_update) > 0) {
		// Setup basic variables
		$columns = [];
		$types = '';
		$values = [];
		
		foreach($to_update as $table => $updates) {
			if($table === 'users') {
				$query = "UPDATE {$table} SET %columns% WHERE id=?";
			} else {
				$query = "UPDATE {$table} SET %columns% WHERE user_id=?";
			}

			$query = str_replace('%table%', $table, $query);

			foreach($updates as $upd) {
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
		if($stmt['result'] === false) {
			$error_list[] = [$stmt['response_code'], $stmt['response_type'], $query.var_export($params, true)];
		} else {
			$changed = true;
		}
	} else {
		$changed = false;
	}

	// Finalize
	if($changed && count($error_list) > 0) {
		finalize($r2, ['partial_success'], ...$error_list);
	} elseif($changed) {
		finalize($r2, ['success']);
	} else {
		finalize($r2, ['no_change_detected'], ...$error_list);
	}
}





elseif($action === 'import-list') {
	if(!array_key_exists('file', $_POST)) {
		finalize($r2, ['required_field', 'error']);
	}

	finalize($r2, ['blank', 'generic', 'feature not implemented yet']);
}





// File should only reach this point if no other actions have reached finalization.
finalize($r2, ['disallowed_action', 'error']);
?>