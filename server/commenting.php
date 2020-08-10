<?php

// function getUsernameById($id) {
// 	global $db;
// 	$result = mysqli_query($db, "SELECT username FROM users WHERE id=" . $id . " LIMIT 1");
// 	return mysqli_fetch_assoc($result)['username'];
// }

// Receives a user id and returns the username
function getUsernameById($id)
{
	global $db;
	$result = mysqli_query($db, "SELECT username FROM users WHERE id=" . $id . " LIMIT 1");
	// return the username
	return mysqli_fetch_assoc($result)['username'];
}
// Receives a comment id and returns the username
function getRepliesByCommentId($id)
{
	global $db;
	$result = mysqli_query($db, "SELECT * FROM replies WHERE comment_id=$id");
	$replies = mysqli_fetch_all($result, MYSQLI_ASSOC);
	return $replies;
}
// Receives a post id and returns the total number of comments on that post
function getCommentsCountByPostId($post_id)
{
	global $db;
	$result = mysqli_query($db, "SELECT COUNT(*) AS total FROM comments");
	$data = mysqli_fetch_assoc($result);
	return $data['total'];
}

?>