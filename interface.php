<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

include 'server/server.php';

// AUTH

if(!$has_session)  {
	finalize('/', 'require_sign_in', 'error'); 
}

$action = $_POST['action'];





// ACTIONS

if($action === "forum-thread-create") {
	if(!isset($_POST['board-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$rpage = '/forum/board?id='.$_POST['board-id'];

	// Check user authority - WHY DOESNT THIS WORK FIX THIS LATER - TODO

	$stmt = $db->prepare('SELECT id, permission_level FROM boards WHERE id=?');
	$stmt->bind_param('i', $_POST['board_id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize('/forum', 'database_failure', 'error');
	}
	$board = $stmt->get_result();
	$stmt->free_result();
	$board = $board->fetch_assoc();
	
	if($permission_level < $board['permission_level']) {
		finalize('/forum', 'unauthorized', 'error');
	}
	
	if(!isset($_POST['title']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($rpage, 'required_field', 'error');
	} else {
		// Add thread to DB
		$stmt = $db->prepare('INSERT INTO threads (user_id, board_id, title) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $user['id'], $_POST['board-id'], $_POST['title']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
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
			finalize($rpage, 'database_failure', 'error');
		}
		
		$stmt->close();
		finalize($rpage);
	}
}





elseif($action === "forum-thread-reply") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$rpage = '/forum/thread?id='.$_POST['thread-id'];
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($rpage, 'required_field', 'error');
	} else {
		// Add post to DB
		$stmt = $db->prepare('INSERT INTO thread_replies (user_id, thread_id, body) VALUES (?, ?, ?)');
		$stmt->bind_param('sss', $user['id'], $_POST['thread-id'], $_POST['body']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
		
		// Set thread updated_at date for sorting purposes
		$stmt = $db->prepare('UPDATE threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?');
		$stmt->bind_param('s', $_POST['thread-id']);
		$stmt->execute();
		$error = $stmt->error;
		if($stmt->error!== "") {
			finalize($rpage, 'database_failure', 'error');
		}
		
		$stmt->close();
		finalize($rpage);
	}
}





elseif($action === "forum-thread-delete") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$stmt = $db->prepare('SELECT id, board_id FROM threads WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$thread = $stmt->get_result();
	$stmt->free_result();
	$thread = $thread->fetch_assoc();
	
	$rpage = '/forum/board?id='.$thread['board_id'];
	
	$stmt = $db->prepare('UPDATE threads SET deleted=TRUE WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	finalize($rpage, 'success');
}




elseif($action === "forum-thread-undelete") {
	if(!isset($_POST['thread-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$rpage = '/forum/thread?id='.$_POST['thread-id'];
	
	$stmt = $db->prepare('UPDATE threads SET deleted=FALSE WHERE id=?');
	$stmt->bind_param('s', $_POST['thread-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	finalize($rpage, 'success');
}





elseif($action === "forum-reply-edit") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$rpage = '/forum/thread?id='.$reply['thread_id'].'#reply-'.$_POST['reply-id'];
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($rpage, 'required_field', 'error');
	}
	
	$stmt = $db->prepare('UPDATE thread_replies SET body=?, updated_at=CURRENT_TIMESTAMP WHERE id=?');
	$stmt->bind_param('ss', $_POST['body'], $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	
	finalize($rpage, 'success');
}



elseif($action === "forum-reply-delete") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$rpage = '/forum/thread?id='.$reply['thread_id'];
	
	$stmt = $db->prepare('UPDATE thread_replies SET deleted=TRUE WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
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
		finalize($rpage, 'database_failure', 'error');
	}

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=TRUE WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
	}
	
	finalize($rpage, 'success');
}



elseif($action === "forum-reply-undelete") {
	if(!isset($_POST['reply-id'])) {
		finalize('/forum', 'disallowed_action', 'error');
	}
	
	$stmt = $db->prepare('SELECT id, thread_id FROM thread_replies WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$reply = $stmt->get_result();
	$stmt->free_result();
	$reply = $reply->fetch_assoc();
	
	$rpage = '/forum/thread?id='.$reply['thread_id'];
	
	$stmt = $db->prepare('UPDATE thread_replies SET deleted=FALSE WHERE id=?');
	$stmt->bind_param('s', $_POST['reply-id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
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
		finalize($rpage, 'database_failure', 'error');
	}

	if($first_reply_id === $reply['id']) {
		$stmt = $db->prepare('UPDATE threads SET anonymous=FALSE WHERE id=?');
		$stmt->bind_param('s', $reply['thread_id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
	}

	finalize($rpage, 'success');
}





elseif($action === "collection-create") {
	$rpage = '/collection';
	
	if(!isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($rpage, 'required_field', 'error');
	} else {
		// Define variables
		$name = trim($_POST['name']);

		$type = trim($_POST['type']);
		if(!in_array((string)$type, $valid_coll_types, True)) {
			finalize($rpage, 'invalid_value', 'error');
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
			finalize($rpage, 'database_failure', 'error');
		}
		
		$stmt->close();
		finalize($rpage, 'success');
	}
}





elseif($action === "collection-edit") {
	$rpage = '/collection';
	
	if(!isset($_POST['collection_id']) || !isset($_POST['name']) || !isset($_POST['type'])) {
		finalize($rpage, 'required_field', 'error');
	}
	
	$collection__id = $_POST['collection_id'];
	$rpage = '/collection?id='.$collection__id;
	
	// Check user authority
	$stmt = $db->prepare('SELECT id, user_id, rating_system FROM collections WHERE id=?');
	$stmt->bind_param('s', $collection__id);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	$collection = $stmt->get_result();
	$stmt->free_result();
	$collection = $collection->fetch_assoc();

	if($user['id'] !== $collection['user_id']) {
		finalize($rpage, 'unauthorized', 'error');
	}


	// Define other variables
	$name = trim($_POST['name']);


	$type = trim($_POST['type']);
	if(!in_array((string)$type, $valid_coll_types, True)) {
		finalize($rpage, 'invalid_value', 'error');
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
			finalize($rpage, 'invalid_value', 'error');
		} else {
			$columns[$col] = $_POST[$col];
		}
	}


	if(isset($_POST['rating_system'])) {
		$rating_system = $_POST['rating_system'];

		// If not valid input
		if(!in_array((int)$rating_system, [3,5,10,20,100], True)) {
			finalize($rpage, 'invalid_value', 'error');
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
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	$stmt->close();

	finalize($rpage, 'success');
}





elseif($action === "collection-item-create" || $action === "collection-item-edit") {
	$rpage = '/collection';
	if($action === "collection-item-create") {
		if(!isset($_POST['collection'])) {
			finalize($rpage, 'disallowed-action', 'error');
		}
		$collection__id = $_POST['collection'];
		$rpage = '/collection?id='.$collection__id;

		// Check user authority - TEST THIS WORKS - NOT CURRENTLY TESTED - TODO
		$stmt = $db->prepare('SELECT id, user_id FROM collections WHERE id=?');
		$stmt->bind_param('i', $collection__id);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
		$collection = $stmt->get_result();
		$stmt->free_result();
		$collection = $collection->fetch_assoc();

		$correct_user_id = $collection['user_id'];
	} elseif($action === "collection-item-edit") {
		if(!isset($_POST['item'])) {
			finalize($rpage, 'disallowed-action', 'error');
		}
		$item__id = $_POST['item'];

		// Check user authority - TEST THIS WORKS - NOT CURRENTLY TESTED - TODO
		$stmt = $db->prepare('SELECT id, user_id, collection_id FROM media WHERE id=?');
		$stmt->bind_param('i', $item__id);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
		$item = $stmt->get_result();
		$stmt->free_result();
		$item = $item->fetch_assoc();

		// Get collection
		$stmt = $db->prepare('SELECT id, rating_system FROM collections WHERE id=?');
		$stmt->bind_param('i', $item['collection_id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== "") {
			finalize($rpage, 'database_failure', 'error');
		}
		$collection = $stmt->get_result();
		$stmt->free_result();
		$collection = $collection->fetch_assoc();

		$rpage = '/collection?id='.$item['collection_id'].'#item-'.$item['id'];
		$correct_user_id = $item['user_id'];
	}

	if($user['id'] !== $correct_user_id) {
		finalize($rpage, 'unauthorized', 'error');
	}
	
	if(!isset($_POST['name']) || !isset($_POST['status'])) {
		finalize($rpage, 'required_field', 'error');
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
	$comments = null;


	// Validate status
	if(array_key_exists('status', $_POST)) {
		$status = (string)$_POST['status'];

		if(!in_array($status, $valid_status, True)) {
			finalize($rpage, 'invalid_value', 'error');
		}
	}


	// Validate Score
	if(array_key_exists('score', $_POST)) {
		$score = (int)$_POST['score'];

		if($score < 0 || $score > $collection['rating_system']) {
			finalize($rpage, 'invalid_value', 'error');
		}

		$score = score_normalize($score, $collection['rating_system']);
	}


	// Validate Episodes
	if(array_key_exists('progress', $_POST)) {
		$progress= (int)$_POST['progress'];
		if($progress < 0) {
			finalize($rpage, 'invalid_value', 'error');
		}
	}

	if(array_key_exists('episodes', $_POST)) {
		$episodes = (int)$_POST['episodes'];
		if($episodes < 0) {
			finalize($rpage, 'invalid_value', 'error');
		}
		// Increase total episodes to match watched episodes if needed
		if($episodes < $progress) {
			$episodes = $progress;
		}
	}

	if(array_key_exists('rewatched', $_POST)) {
		$rewatched = (int)$_POST['rewatched'];
		if($rewatched < 0) {
			finalize($rpage, 'invalid_value', 'error');
		}
	}

	// Modify episodes to make sense if item completed.
	if($status === 'completed' && $episodes >= $progress) {
		$progress = $episodes;
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
				finalize($rpage, 'invalid_value', 'error');
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
			finalize($rpage, 'invalid_value', 'error');
		}
	}


	// Apply to DB
	if($action === "collection-item-create") {
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
				comments
			)
			VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)
		');

		$stmt->bind_param(
			'iissiiiissssss',
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
			$comments
		);
	} elseif($action === "collection-item-edit") {
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
				comments=?
			WHERE id=?
		');

		$stmt->bind_param(
			'ssiiiissssssi',
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
			$item['id']
		);
	}
	
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	
	$stmt->close();
	finalize($rpage, 'success');
}





// WHY DOES NONE OF THIS WORK
if($action === "collection-item-delete") {
	$rpage = '/collection';

	if(!isset($_POST['item'])) {
		finalize($rpage, 'disallowed_action', 'error');
	}

	// Check user authority

	$item__id = $_POST['item'];

	// Check user authority - TEST THIS WORKS - NOT CURRENTLY TESTED - TODO
	$stmt = $db->prepare('SELECT id, user_id, collection_id FROM media WHERE id=?');
	$stmt->bind_param('i', $item__id);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}
	$item = $stmt->get_result();
	$stmt->free_result();
	$item = $item->fetch_assoc();

	$rpage = '/collection?id='.$item['collection_id'];

	if($user['id'] !== $item['user_id']) {
		finalize($rpage, 'unauthorized', 'error');
	}

	// Delete from DB
	
	$stmt = $db->prepare('UPDATE media SET deleted=1 WHERE id=?');
	$stmt->bind_param('s', $item['id']);
	$stmt->execute();
	$error = $stmt->error;
	if($error !== "") {
		finalize($rpage, 'database_failure', 'error');
	}

	finalize($rpage, 'success');
}





elseif($action === "change-settings") {
	$rpage = '/account/settings';
	$changed = False;

	// ALL SETTINGS
	if(!$has_session) {
		finalize('/', 'require_sign_in', 'error');
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
			finalize($rpage, 'invalid_value', 'error');
		}

		// If valid, continue
		$stmt = $db->prepare('UPDATE user_preferences SET timezone=? WHERE user_id=?');
		$stmt->bind_param('ss', $tz, $user['id']);
		$stmt->execute();
		$error = $stmt->error;
		if($error !== '') {
			finalize($rpage, 'database_failure', 'error');
		}
		$changed = True;
	}
	skip_timezone:


	// FINALIZE - should only reach this point after clearing all conditions
	if($changed === True) {
		finalize($rpage, 'success');
	} else {
		finalize($rpage, 'no_change_detected');
	}
}





// File should only reach this point if no other actions have reached finalization.
finalize('/', 'disallowed_action', 'error');
?>