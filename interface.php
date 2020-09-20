<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

include 'server/server.php';

// AUTH

if(!$has_session)  {
	finalize('/', 'require_sign_in', 'error'); 
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
		finalize($r2, 'disallowed_action', 'error');
	}
	
	$board__id = $_POST['board_id'];

	// Check user authority

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $board__id);
	$stmt->execute();
	$board = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize('/forum', 'database_failure', 'error');
	}
	if($board->num_rows < 1) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level']) {
		finalize('/forum', 'unauthorized', 'error');
	}
	
	// Check required fields

	if(!isset($_POST['title']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, 'required_field', 'error');
	}

	$title = $_POST['title'];
	$body = $_POST['body'];

	// Add thread to DB
	$stmt = $db->prepare('INSERT INTO threads (user_id, board_id, title) VALUES (?, ?, ?)');
	$stmt->bind_param('iis', $user['id'], $board__id, $title);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	// Get newly added thread ID
	$stmt = $db->prepare('SELECT id FROM threads WHERE id = LAST_INSERT_ID()');
	$stmt->execute();
	$new_thread_id = $stmt->get_result();
	$new_thread_id = $new_thread_id->fetch_assoc()['id'];
	
	// Add thread reply to DB
	$stmt = $db->prepare('INSERT INTO replies (user_id, thread_id, body) VALUES (?, ?, ?)');
	$stmt->bind_param('iis', $user['id'], $new_thread_id, $body);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	finalize($r2);
}





elseif($action === "forum_reply_create") {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$thread__id = $_POST['thread_id'];

	// Validate thread exists

	$stmt = $db->prepare('SELECT id, board_id, locked FROM threads WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	$thread = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($thread->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$thread = $thread->fetch_assoc();

	// Check user authority

	if($thread['locked'] === 1 && $permission_level < $permission_levels['Moderator']) {
		finalize($r2, 'unauthorized', 'error');
	}

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $thread['board_id']);
	$stmt->execute();
	$board = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Check required fields
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, 'required_field', 'error');
	}

	// Add post to DB
	$stmt = $db->prepare('INSERT INTO replies (user_id, thread_id, body) VALUES (?, ?, ?)');
	$stmt->bind_param('iis', $user['id'], $thread__id, $_POST['body']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	// Set thread updated_at date for sorting purposes
	$stmt = $db->prepare('UPDATE threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	finalize($r2, 'success');
}





elseif($action === "forum_thread_lock") {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$thread__id = $_POST['thread_id'];

	// Check user authority
	if($permission_level < $permission_levels['Moderator']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Check thread exists
	$stmt = $db->prepare('SELECT id FROM threads WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	$thread = $stmt->get_result();
	if($thread->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$stmt->free_result();
	$thread = $thread->fetch_assoc();

	// Execute DB
	$stmt = $db->prepare('UPDATE threads SET locked=1 WHERE id=?');
	$stmt->bind_param('i', $thread['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	finalize($r2, 'success');
}





elseif($action === "forum_thread_unlock") {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$thread__id = $_POST['thread_id'];

	// Check user authority
	if($permission_level < $permission_levels['Moderator']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Check thread exists
	$stmt = $db->prepare('SELECT id FROM threads WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	$thread = $stmt->get_result();
	if($thread->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$stmt->free_result();
	$thread = $thread->fetch_assoc();

	// Execute DB
	$stmt = $db->prepare('UPDATE threads SET locked=0 WHERE id=?');
	$stmt->bind_param('i', $thread['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	finalize($r2, 'success');
}





elseif($action === "forum_thread_delete") {
	if(!isset($_POST['thread_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$thread__id = $_POST['thread_id'];

	// Check existence

	$stmt = $db->prepare('SELECT id, board_id, user_id FROM threads WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	$thread = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($thread->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$thread = $thread->fetch_assoc();

	// Check user authority

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $thread['board_id']);
	$stmt->execute();
	$board = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize('/forum', 'database_failure', 'error');
	}
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level'] || $user['id'] !== $thread['user_id']) {
		finalize('/forum', 'unauthorized', 'error');
	}

	// Execute DB
	
	$stmt = $db->prepare('SELECT id, board_id FROM threads WHERE id=?');
	$stmt->bind_param('s', $thread['id']);
	$stmt->execute();
	$thread = $stmt->get_result();
	$stmt->free_result();
	$thread = $thread->fetch_assoc();
	
	$r2 = '/forum/board?id='.$thread['board_id'];
	
	$stmt = $db->prepare('UPDATE threads SET deleted=1 WHERE id=?');
	$stmt->bind_param('s', $thread['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	finalize($r2, 'success');
}




elseif($action === "forum_thread_undelete") {
	if(!isset($_POST['thread_id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	$thread__id = $_POST['thread_id'];

	// Check existence

	$stmt = $db->prepare('SELECT id, board_id, user_id FROM threads WHERE id=?');
	$stmt->bind_param('i', $thread__id);
	$stmt->execute();
	$thread = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($thread->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$thread = $thread->fetch_assoc();

	// Check user authority

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $thread['board_id']);
	$stmt->execute();
	$board = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize('/forum', 'database_failure', 'error');
	}
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level'] || $user['id'] !== $thread['user_id']) {
		finalize('/forum', 'unauthorized', 'error');
	}

	// Execute DB
	
	$stmt = $db->prepare('UPDATE threads SET deleted=0 WHERE id=?');
	$stmt->bind_param('s', $thread['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	finalize($r2, 'success');
}





elseif($action === "forum_reply_edit") {
	if(!isset($_POST['reply_id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}

	// Check existence

	$stmt = $db->prepare('SELECT id, thread_id, user_id FROM replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply_id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	if($reply->num_rows < 1) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$reply = $reply->fetch_assoc();

	// Check user authority

	if($user['id'] !== $reply['user_id']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Execute DB
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($r2, 'required_field', 'error');
	}
	
	$stmt = $db->prepare('UPDATE replies SET body=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
	$stmt->bind_param('ss', $_POST['body'], $reply['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	finalize($r2, 'success');
}



elseif($action === "forum_reply_delete") {
	if(!isset($_POST['reply_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$reply__id = $_POST['reply_id'];
	
	// Check existence

	$stmt = $db->prepare('SELECT id, thread_id, user_id FROM replies WHERE id=?');
	$stmt->bind_param('i', $reply__id);
	$stmt->execute();
	$reply = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($reply->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$reply = $reply->fetch_assoc();

	// Check user authority
	
	if($user['id'] !== $reply['user_id'] && $permission_level < $permission_levels['Moderator']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Execute DB
	
	$stmt = $db->prepare('UPDATE replies SET deleted=1 WHERE id=?');
	$stmt->bind_param('s', $reply['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	// Set thread anonymous if deleting first post.

	$stmt = $db->prepare("SELECT id FROM replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 1");
	$stmt->bind_param("s", $reply['thread_id']);
	$stmt->execute();
	$res = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$res = $res->fetch_assoc();
	$first_reply_id = $res['id'];
	$stmt->free_result();

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=1 WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		if($stmt->affected_rows < 1) {
			finalize($r2, 'database_failure', 'error');
		}
	}
	
	finalize($r2, 'success');
}



elseif($action === "forum_reply_undelete") {
	if(!isset($_POST['reply_id'])) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$reply__id = $_POST['reply_id'];
	
	// Check existence

	$stmt = $db->prepare('SELECT id, thread_id, user_id FROM replies WHERE id=?');
	$stmt->bind_param('i', $reply__id);
	$stmt->execute();
	$reply = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($reply->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$reply = $reply->fetch_assoc();

	// Check user authority
	
	if($user['id'] !== $reply['user_id'] && $permission_level < $permission_levels['Moderator']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Execute DB
	
	$stmt = $db->prepare('UPDATE replies SET deleted=0 WHERE id=?');
	$stmt->bind_param('s', $reply['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	// Set thread un-anonymous if un-deleting first post.
	$stmt = $db->prepare("SELECT id FROM replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 1");
	$stmt->bind_param("s", $reply['thread_id']);
	$stmt->execute();
	$res = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$res = $res->fetch_assoc();
	$first_reply_id = $res['id'];
	$stmt->free_result();

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=0 WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		if($stmt->affected_rows < 1) {
			finalize($r2, 'database_failure', 'error');
		}
	}

	finalize($r2, 'success');
}





elseif($action === "collection_create") {
	// Required fields
	if(!isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($r2, 'required_field', 'error');
	}

	// Define variables
	$name = trim($_POST['name']);

	$type = trim($_POST['type']);
	if(!in_array((string)$type, $valid_coll_types, True)) {
		finalize($r2, 'invalid_value', 'error');
	}

	if(!isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True)) {
		$private = 0;
	} else {
		$private = $_POST['private'];
	}

	// Execute DB
	$stmt = $db->prepare('INSERT INTO collections (user_id, name, type, private) VALUES (?, ?, ?, ?)');
	$stmt->bind_param('ssss', $user['id'], $name, $type, $private);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}
	
	finalize($r2, 'success');
}





elseif($action === "collection_edit") {
	if(!isset($_POST['collection_id']) || !isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($r2, 'required_field', 'error');
	}
	
	$collection__id = $_POST['collection_id'];
	
	// Check user authority
	$stmt = $db->prepare('SELECT id, user_id, rating_system FROM collections WHERE id=?');
	$stmt->bind_param('s', $collection__id);
	$stmt->execute();
	$collection = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	$stmt->free_result();
	$collection = $collection->fetch_assoc();

	if($user['id'] !== $collection['user_id']) {
		finalize($r2, 'unauthorized', 'error');
	}


	// Define other variables
	$name = trim($_POST['name']);


	$type = trim($_POST['type']);
	if(!in_array((string)$type, $valid_coll_types, True)) {
		finalize($r2, 'invalid_value', 'error');
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
			finalize($r2, 'invalid_value', 'error');
		} else {
			$columns[$col] = $_POST[$col];
		}
	}


	if(isset($_POST['rating_system'])) {
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if(!in_array((int)$rating_system, [3,5,10,20,100], True)) {
			finalize($r2, 'invalid_value', 'error');
		}
	} else {
		$rating_system = $collection['rating_system'];
	}

	// Add collection to DB
	$stmt = $db->prepare('UPDATE collections SET
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
	');
	$stmt->bind_param(
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
	);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	finalize($r2, 'success');
}





elseif($action === "collection_item_create" || $action === "collection_item_edit") {
	if($action === "collection_item_create") {
		if(!isset($_POST['collection'])) {
			finalize($r2, 'disallowed_action', 'error');
		}
		$collection__id = $_POST['collection'];

		// Check user authority
		$stmt = $db->prepare('SELECT id, user_id, rating_system FROM collections WHERE id=?');
		$stmt->bind_param('i', $collection__id);
		$stmt->execute();
		$collection = $stmt->get_result();
		if($stmt->affected_rows === -1) {
			finalize($r2, 'database_failure', 'error');
		}
		$stmt->free_result();
		$collection = $collection->fetch_assoc();

		$correct_user_id = $collection['user_id'];
	} elseif($action === "collection_item_edit") {
		if(!isset($_POST['item'])) {
			finalize($r2, 'disallowed_action', 'error');
		}
		$item__id = $_POST['item'];

		// Check user authority
		$stmt = $db->prepare('SELECT id, user_id, collection_id FROM media WHERE id=?');
		$stmt->bind_param('i', $item__id);
		$stmt->execute();
		$item = $stmt->get_result();
		if($stmt->affected_rows === -1) {
			finalize($r2, 'database_failure', 'error');
		}
		$stmt->free_result();
		$item = $item->fetch_assoc();

		// Get collection
		$stmt = $db->prepare('SELECT id, rating_system FROM collections WHERE id=?');
		$stmt->bind_param('i', $item['collection_id']);
		$stmt->execute();
		$collection = $stmt->get_result();
		if($stmt->affected_rows === -1) {
			finalize($r2, 'database_failure', 'error');
		}
		$stmt->free_result();
		$collection = $collection->fetch_assoc();
		$correct_user_id = $item['user_id'];
	}

	if($user['id'] !== $correct_user_id) {
		finalize('/collection', 'unauthorized', 'error');
	}
	
	if(!isset($_POST['name']) || !isset($_POST['status'])) {
		finalize($r2, 'required_field', 'error');
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
			finalize($r2, 'invalid_value', 'error');
		}
	}


	// Validate Score
	if(array_key_exists('score', $_POST)) {
		$score = (int)$_POST['score'];

		if($score < 0 || $score > $collection['rating_system']) {
			finalize($r2, 'invalid_value', 'error');
		}

		$score = score_normalize($score, $collection['rating_system']);
	}


	// Validate Episodes
	if(array_key_exists('progress', $_POST)) {
		$progress= (int)$_POST['progress'];
		if($progress < 0) {
			finalize($r2, 'invalid_value', 'error');
		}
	}

	if(array_key_exists('episodes', $_POST)) {
		$episodes = (int)$_POST['episodes'];
		if($episodes < 0) {
			finalize($r2, 'invalid_value', 'error');
		}
		// Increase total episodes to match watched episodes if needed
		if($episodes < $progress) {
			$episodes = $progress;
		}
	}

	if(array_key_exists('rewatched', $_POST)) {
		$rewatched = (int)$_POST['rewatched'];
		if($rewatched < 0) {
			finalize($r2, 'invalid_value', 'error');
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
			|| (int)$d > 31 // yes, this will accept invalid day ranges. will fix with a different if statement when/if it becomes a problem (such as SQL refusing the date). This requires some testing.
			) {
				finalize($r2, 'invalid_value', 'error');
		}

		// if all check passed, return
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
			finalize($r2, 'invalid_value', 'error');
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
			finalize($r2, 'invalid_value', 'error');
		}
	}

	if(array_key_exists('favourite', $_POST)) {
		$favourite = $_POST['favourite'];
		if($favourite < 0 || $favourite > 1) {
			finalize($r2, 'invalid_value', 'error');
		}
	}


	// Apply to DB
	if($action === "collection_item_create") {
		$stmt = $db->prepare('
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
		');

		$stmt->bind_param(
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
		);
	} elseif($action === "collection_item_edit") {
		$stmt = $db->prepare('
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
		');

		$stmt->bind_param(
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
			$item['id']
		);
	}
	
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	if($action === 'collection_item_create') {
		// Get newly added ID
		$stmt = $db->prepare('SELECT LAST_INSERT_ID()');
		$stmt->execute();
		$new_item_id = $stmt->get_result();
		$new_item_id = $new_item_id->fetch_row()[0];

		$r2 = $r2.'#item-'.$new_item_id;
	}
	
	finalize($r2, 'success');
}





if($action === "collection_item_delete") {
	if(!isset($_POST['item'])) {
		finalize($r2, 'disallowed_action', 'error');
	}

	// Check user authority

	$item__id = $_POST['item'];

	// Check user authority
	$stmt = $db->prepare('SELECT id, user_id, collection_id FROM media WHERE id=?');
	$stmt->bind_param('i', $item__id);
	$stmt->execute();
	$item = $stmt->get_result();
	if($stmt->affected_rows === -1) {
		finalize($r2, 'database_failure', 'error');
	}
	if($item->num_rows < 1) {
		finalize($r2, 'disallowed_action', 'error');
	}
	$stmt->free_result();
	$item = $item->fetch_assoc();

	if($user['id'] !== $item['user_id']) {
		finalize($r2, 'unauthorized', 'error');
	}

	// Delete from DB
	
	$stmt = $db->prepare('UPDATE media SET deleted=1 WHERE id=?');
	$stmt->bind_param('s', $item['id']);
	$stmt->execute();
	if($stmt->affected_rows < 1) {
		finalize($r2, 'database_failure', 'error');
	}

	finalize($r2, 'success');
}





elseif($action === "change_settings") {
	$changed = False;

	// ALL SETTINGS
	if(!$has_session) {
		finalize('/', 'require_sign_in', 'error');
	}

	// TIMEZONE
	if(isset($_POST['change_timezone'])) {
		$tz = $_POST['change_timezone'];

		// If value the same as before
		if($tz === $user_timezone) {
			goto skip_timezone;
		}

		// If not valid input
		$needle = False;
		foreach($valid_timezones as $zone_group) {
			if(in_array($tz, $zone_group, True)) {
				$needle = True;
				break;
			}
		}

		if($needle === False) {
			finalize($r2, 'invalid_value', 'error');
		}

		// If valid, continue
		$stmt = $db->prepare('UPDATE user_preferences SET timezone=? WHERE user_id=?');
		$stmt->bind_param('ss', $tz, $user['id']);
		$stmt->execute();
		if($stmt->affected_rows < 1) {
			finalize($r2, 'database_failure', 'error');
		}
		$changed = True;
	}
	skip_timezone:


	// FINALIZE - should only reach this point after clearing all conditions
	if($changed === True) {
		finalize($r2, 'success');
	} else {
		finalize($r2, 'no_change_detected');
	}
}





elseif($action === 'import-list') {
	if(!array_key_exists('file', $_POST)) {
		finalize($r2, 'required_field', 'error');
	}

	finalize($r2, 'generic', 'generic', 'feature not implemented yet');
}





// File should only reach this point if no other actions have reached finalization.
finalize('/', 'disallowed_action', 'error');
?>