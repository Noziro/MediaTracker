<?php

// Refactor this page if you later add functions for non-users. As it is currently, it will auto-redirect logged out users to home page.

// SETUP

include 'server/server.php';

// AUTH

$auth = new Authentication();
$has_session = $auth->isLoggedIn();

if ($has_session) {
	$user = $auth->getCurrentUser();
	$permission_level = $user['permission_level'];
} else {
	finalize('/?error=require-sign-in'); 
}

$action = $_POST['action'];

// ACTIONS

if($action === "forum-thread-create") {
	if(!isset($_POST['board-id'])) {
		finalize('/forum?error=disallowed-action');
	}
	
	$return_to = '/forum/board?id='.$_POST['board-id'];
	
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
		if($error !== "") {
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
	$stmt->free_result();
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
	$stmt->free_result();
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
	$stmt->free_result();
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
	$stmt->free_result();
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
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
	$stmt->free_result();
	if($error !== "") {
		finalize($return_to.'&error=database-failure');
	}
	finalize($return_to.'&notice=success');
}

// File should only reach this point if no other actions have reached finalization.
finalize('/?error=disallowed-action');

$auth->cleanup();
?>