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

<div class="wrapper__inner forum thread">
	<div class="content-header">
		<div class="content-header__breadcrumb">
			<a href="<?=FILEPATH."forum"?>">Forum</a> >
			<a href="<?=FILEPATH."forum/board?id=".$board['id']?>"><?=$board['name']?></a> >
			<span><?=$thread['title']?></span>
		</div>
		
		<h2 class="content-header__title"><?=$thread['title']?></h2>
	</div>
	
	<?php if($has_session) : ?>
	<div class="page-actions">
		<button id="js-newreply" class="page-actions__action button" type="button">
			New Reply
		</button>
		
		<button id="js-watchthread" class="page-actions__action button button--disabled" type="button" disabled>
			Watch Thread
		</button>
	</div>
	<?php endif ?>
	
	<?php foreach($replies as $reply): ?>
	<div class="thread-reply">
		<div class="thread-reply__info">
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
		<div class="thread-reply__content">
			<p class="thread-reply__text global__long-text">
				<?=$reply['body']?>
			</p>
		</div>
	</div>
	<?php endforeach ?>
	
	<div id="js-hidetoggle" class="submission-form">
		<form>
			<input type="text">
			<input type="submit" value="Reply">
		</form>
	</div>
</div>