<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

include 'server/server.php';

// AUTH

if(!$has_session)  {
	finalize('/?error=require-sign-in'); 
}

$action = $_POST['action'];

// ACTIONS

if($action === "forum-thread-create") {
	if(!isset($_POST['board-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$return_to = '/forum/board?id='.$_POST['board-id'];

	// Check user authority - WHY DOESNT THIS WORK FIX THIS LATER - TODO

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $_POST['board_id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize('/forum?error=database-failure');
	}
	$board = $stmt->get_result();
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level']) {
		finalize('/forum?error=unauthorized');
	}
	
	if(!isset($_POST['title']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($return_to.'?error=required-field');
	} else {
		// Add thread to DB
		$stmt = $db->prepare('INSERT INTO threads (user_id, board_id, title) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $user['id'], $_POST['board-id'], $_POST['title']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		// Get newly added thread ID
		$stmt = $db->prepare('SELECT id FROM threads WHERE id = LAST_INSERT_ID()');
		$stmt->execute();
		$new_thread_id = $stmt->get_result();
		$new_thread_id = $new_thread_id->fetch_assoc()['id'];
		
		// Add thread reply to DB
		$stmt = $db->prepare('INSERT INTO thread_replies (user_id, thread_id, body) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $user['id'], $new_thread_id, $_POST['body']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		$stmt->close();
		finalize($return_to);
	}
}

elseif($action === "forum-thread-reply") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$return_to = '/forum/thread?id='.$_POST['thread-id'];
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($return_to.'?error=required-field');
	} else {
		// Add post to DB
		$stmt = $db->prepare('INSERT INTO thread_replies (user_id, thread_id, body) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $user['id'], $_POST['thread-id'], $_POST['body']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		// Set thread updated_at date for sorting purposes
		$stmt = $db->prepare('UPDATE threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?');
		$stmt->bind_param('s', $_POST['thread-id']);
		$stmt->execute();
		$error = $stmt->error;
		if($stmt->error!== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		$stmt->close();
		finalize($return_to);
	}
}

elseif($action === "forum-thread-delete") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$stmt = $db->prepare('SELECT id, board_id FROM threads WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$thread = $stmt->get_result();
	$stmt->free_result();
	$thread = $thread->fetch_assoc();
	
	$return_to = '/forum/board?id='.$thread['board_id'];
	
	$stmt = $db->prepare('UPDATE threads SET deleted=TRUE WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	finalize($return_to.'&notice=success');
}

elseif($action === "forum-thread-undelete") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$return_to = '/forum/thread?id='.$_POST['thread-id'];
	
	$stmt = $db->prepare('UPDATE threads SET deleted=FALSE WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	finalize($return_to.'&notice=success');
}

elseif($action === "forum-reply-edit") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$return_to = '/forum/thread?id='.$reply['thread_id'].'#reply-'.$_POST['reply-id'];
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($return_to.'?error=required-field');
	}
	
	$stmt = $db->prepare('UPDATE thread_replies SET body=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
	$stmt->bind_param('ss', $_POST['body'], $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	
	finalize($return_to.'&notice=success');
}

elseif($action === "forum-reply-delete") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$return_to = '/forum/thread?id='.$reply['thread_id'];
	
	$stmt = $db->prepare('UPDATE thread_replies SET deleted=TRUE WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	
	// Set thread anonymous if deleting first post.
	$stmt = $db->prepare("SELECT id FROM thread_replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 1");
	$stmt->bind_param("s", $reply['thread_id']);
	$stmt->execute();
	$res = $stmt->get_result();
	$res = $res->fetch_assoc();
	$first_reply_id = $res['id'];
	$error = $stmt->error;
	$stmt->free_result();
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=TRUE WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'&error=database-failure');
		}
	}
	
	finalize($return_to.'&notice=success');
}

elseif($action === "forum-reply-undelete") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$return_to = '/forum/thread?id='.$reply['thread_id'];
	
	$stmt = $db->prepare('UPDATE thread_replies SET deleted=FALSE WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	
	// Set thread un-anonymous if un-deleting first post.
	$stmt = $db->prepare("SELECT id FROM thread_replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 1");
	$stmt->bind_param("s", $reply['thread_id']);
	$stmt->execute();
	$res = $stmt->get_result();
	$res = $res->fetch_assoc();
	$first_reply_id = $res['id'];
	$error = $stmt->error;
	$stmt->free_result();
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=FALSE WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'&error=database-failure');
		}
	}

	finalize($return_to.'&notice=success');
}



elseif($action === "collection-create") {
	$return_to = '/collection';
	
	if(!isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($return_to.'?error=required-field');
	} else {
		// Define variables
		$name = trim($_POST['name']);

		$type = trim($_POST['type']);
		if(!in_array((string)$type, $valid_coll_types, True)) {
			finalize($return_to.'?error=invalid-value');
		}

		if(!isset($_POST['private']) || !in_array((int)$_POST['private'], [0,9], True)) {
			$private = 0;
		} else {
			$private = $_POST['private'];
		}

		// Add collection to DB
		$stmt = $db->prepare('INSERT INTO collections (user_id, name, type, private) VALUES (?, ?, ?, ?)');
		$stmt->bind_param('ssss', $user['id'], $name, $type, $private);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		$stmt->close();
		finalize($return_to.'?notice=success');
	}
}



elseif($action === "collection-item-create") {
	$collection__id = $_POST['collection'];
	$return_to = '/collection?id='.$collection__id;

	// Check user authority - TEST THIS WORKS - NOT CURRENTLY TESTED - TODO

	$stmt = $db->prepare('SELECT id, user_id FROM collections WHERE id=?');
	$stmt->bind_param('i', $collection__id);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	$collection = $stmt->get_result();
	$stmt->free_result();
	$collection = $collection->fetch_assoc();

	if($user['id'] !== $collection['user_id']) {
		finalize($return_to.'&error=unauthorized');
	}
	
	if(!isset($_POST['name']) || !isset($_POST['status'])) {
		finalize($return_to.'&error=required-field');
	}

	// Define base variables
	$status = 'planned';
	$name = trim($_POST['name']);
	$score = null;
	$episodes = null;
	$user_started = null;
	$user_finished = null;
	$release_date = null;
	$started_at = null;
	$finished_at = null;
	$comments = null;


	// Validate status
	if(array_key_exists('status', $_POST)) {
		$status = (string)$_POST['status'];

		if(!in_array($status, $valid_status, True)) {
			finalize($return_to.'&error=invalid-value');
		}
	}


	// Validate Score
	if(array_key_exists('score', $_POST)) {
		$score = (int)$_POST['score'];

		if($score < 0 || $score > $prefs['rating_system']) {
			finalize($return_to.'&error=invalid-value');
		}

		$score = score_normalize($score, $prefs['rating_system']);
	}


	// Validate Episodes
	if(array_key_exists('episodes', $_POST)) {
		$episodes = (int)$_POST['episodes'];
		if($episodes < 0) {
			finalize($return_to.'&error=invalid-value');
		}
	}


	// Validate Dates
	function validate_date($date) {
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
				finalize($return_to.'&error=invalid-value');
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
			finalize($return_to.'&error=invalid-value');
		}
	}


	// Add item to DB
	$stmt = $db->prepare('
		INSERT INTO media (
			user_id,
			collection_id,
			status,
			name,
			score,
			episodes,
			user_started_at,
			user_finished_at,
			release_date,
			started_at,
			finished_at,
			comments
		)
		VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
	');
	$stmt->bind_param(
		'ssssssssssss',
		$user['id'],
		$collection__id,
		$status,
		$name,
		$score,
		$episodes,
		$user_started,
		$user_finished,
		$release_date,
		$started,
		$finished,
		$comments
	);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($return_to.'&error=database-failure&cause='.$error);
	}
	
	$stmt->close();
	finalize($return_to.'&notice=success');
}


elseif($action === "change-settings") {
	$return_to = '/account/settings';
	$changed = False;

	// ALL SETTINGS
	if(!$has_session) {
		finalize('/?error=require-sign-in');
	}

	// TIMEZONE
	if(isset($_POST['change-timezone'])) {
		$tz = $_POST['change-timezone'];

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
			finalize($return_to.'?error=invalid-value');
		}

		// If valid, continue
		$stmt = $db->prepare('UPDATE user_preferences SET timezone=? WHERE user_id=?');
		$stmt->bind_param('ss', $tz, $user['id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== '') {
			finalize($return_to.'?error=database-failure');
		}
		$changed = True;
	}
	skip_timezone:


	// RATING SYSTEM
	if(isset($_POST['change-rating-system'])) {
		$ratsys = $_POST['change-rating-system'];

		// If value the same as before
		if($ratsys === $prefs['rating_system']) {
			goto skip_rating_system;
		}

		// If not valid input
		if(!in_array((int)$ratsys, [3,5,10,20,100], True)) {
			finalize($return_to.'?error=invalid-value');
		}

		// If valid, continue
		$stmt = $db->prepare('UPDATE user_preferences SET rating_system=? WHERE user_id=?');
		$stmt->bind_param('ss', $ratsys, $user['id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== '') {
			finalize($return_to.'?error=database-failure');
		}
		$changed = True;
	}
	skip_rating_system:


	// FINALIZE - should only reach this point after clearing all conditions
	if($changed === True) {
		finalize($return_to.'?notice=success');
	} else {
		finalize($return_to.'?notice=no-change-detected');
	}
}



// File should only reach this point if no other actions have reached finalization.
finalize('/?error=disallowed-action');
?>