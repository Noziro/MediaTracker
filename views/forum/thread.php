<?php
if(isset($_GET["id"])) {
	$thread = sqli_result("SELECT id, board_id, title FROM threads WHERE id=?", "s", $_GET["id"]);
	
	if($thread->num_rows < 1) {
		header('Location: /404');
		exit();
	}
	
	$thread = $thread->fetch_assoc();
	
	$board = sqli_result("SELECT id, name, permission_level FROM boards WHERE id=?", "s", $thread['board_id']);
	$board = $board->fetch_assoc();
	$board_permission_level = $board['permission_level'];
	
	// redirect if user lacks access
	if($access_level < $board_permission_level) {
		header('Location: /404');
		exit();
	}
} else {
	header('Location: /404');
	exit();
}

$stmt = $db->prepare("SELECT id, user_id, body, created_at, updated_at FROM thread_replies WHERE thread_id=? ORDER BY updated_at ASC LIMIT 20");
$stmt->bind_param("s", $thread['id']);
$stmt->execute();
$replies = $stmt->get_result();
$replies = $replies->fetch_all(MYSQLI_ASSOC);
?>

<div class="container forum thread">
	<div class="breadcrumb">
		<a href="<?=FILEPATH."forum"?>">Forum</a> >
		<a href="<?=FILEPATH."forum/board?id=".$board['id']?>"><?=$board['name']?></a> >
		<span><?=$thread['title']?></span>
	</div>
	
	<h3><?=$thread['title']?></h3>
	
	<div class="replies">
		<?php foreach($replies as $reply): ?>
		<div class="reply">
			<div class="reply-info">
				<?php
				$reply_user = sqli_result("SELECT id, nickname FROM users WHERE id=?", "s", $reply['user_id']);
				$reply_user = $reply_user->fetch_assoc();
				?>
				<a class="user" href="<?=FILEPATH."user?id=".$reply_user['id']?>">
					<?=$reply_user['nickname']?>
				</a>
				<br />
				<?=$reply['created_at']?>
				<br />
				<?=$reply['updated_at']?>
			</div>
			<div class="reply-content">
				<p>
					<?=$reply['body']?>
				</p>
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>