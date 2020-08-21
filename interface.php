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
		finalize('/?error=disallowed-action');
	}
	
	$return_to = '/forum/board?id='.$_POST['board-id'];
	
	if(!isset($_POST['title']) || !isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($return_to.'?error=required-field');
	} else {
		// Add thread to DB
		$q = $db->prepare('INSERT INTO threads (user_id, board_id, title) VALUES (?, ?, ?)');
		$q->bind_param('sss', $user['id'], $_POST['board-id'], $_POST['title']);
		$q->execute();
		$error = $q->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		// Get newly added thread ID
		$q = $db->prepare('SELECT id FROM threads WHERE id = LAST_INSERT_ID()');
		$q->execute();
		$new_thread_id = $q->get_result();
		$new_thread_id = $new_thread_id->fetch_assoc()['id'];
		
		// Add thread reply to DB
		$q = $db->prepare('INSERT INTO thread_replies (user_id, thread_id, body) VALUES (?, ?, ?)');
		$q->bind_param('sss', $user['id'], $new_thread_id, $_POST['body']);
		$q->execute();
		$error = $q->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		$q->close();
		finalize($return_to);
	}
}

elseif($action === "forum-thread-reply") {
	if(!isset($_POST['thread-id'])) {
		finalize('/?error=disallowed-action');
	}
	
	$return_to = '/forum/thread?id='.$_POST['thread-id'];
	
	if(!isset($_POST['body']) || trim($_POST['body']) === '') {
		finalize($return_to.'?error=required-field');
	} else {
		// Add post to DB
		$q = $db->prepare('INSERT INTO thread_replies (user_id, thread_id, body) VALUES (?, ?, ?)');
		$q->bind_param('sss', $user['id'], $_POST['thread-id'], $_POST['body']);
		$q->execute();
		$error = $q->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		// Set thread updated_at date for sorting purposes
		$q = $db->prepare('UPDATE threads SET updated_at=CURRENT_TIMESTAMP WHERE id=?');
		$q->bind_param('s', $_POST['thread-id']);
		$q->execute();
		$error = $q->error;
		if($error !== "") {
			finalize($return_to.'?error=database-failure');
		}
		
		$q->close();
		finalize($return_to);
	}
}

else {
	finalize('/?error=disallowed-action');
}

?>