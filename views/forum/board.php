<?php
if(isset($_GET["id"])) {
	$board = sqli_result("SELECT id, name, description, permission_level FROM boards WHERE id=?", "s", $_GET["id"]);
	
	if($board->num_rows < 1) {
		header('Location: /404');
		exit();
	}
	
	$board = $board->fetch_assoc();
	
	// redirect if user lacks access
	if($access_level < $board['permission_level']) {
		header('Location: /404');
		exit();
	}
} else {
	header('Location: /404');
	exit();
}

$stmt = $db->prepare("SELECT id, user_id, title, created_at, updated_at FROM threads WHERE board_id=? ORDER BY updated_at DESC LIMIT 20");
$stmt->bind_param("s", $board['id']);
$stmt->execute();
$threads = $stmt->get_result();
$threads = $threads->fetch_all(MYSQLI_ASSOC);
?>

<div class="container forum board">
	<div class="breadcrumb">
		<a href="<?=FILEPATH."forum"?>">Forum</a> >
		<span><?=$board['name']?></span>
	</div>
	
	<h3><?=$board['name']?></h3>
	<h6><?=$board['description']?></h6>
	
	<div class="threads">
		<?php foreach($threads as $thread): ?>
		<div class="thread">
			<div class="thread-description">
				<a href="<?=FILEPATH?>forum/thread?id=<?=$thread['id']?>">
					<h6><?=$thread['title']?></h6>
				</a>
				<p>
					<?=$thread['created_at']?>
					by
					<?php
					$thread_user = sqli_result("SELECT id, nickname FROM users WHERE id=?", "s", $thread['user_id']);
					$thread_user = $thread_user->fetch_assoc();
					?>
					<a class="user" href="<?=FILEPATH."user?id=".$thread_user['id']?>">
						<?=$thread_user['nickname']?>
					</a>
				</p>
			</div>
			<div class="recent-replies">
				<?php 				
				$replies = sqli_result("SELECT id, user_id, updated_at FROM thread_replies WHERE thread_id=? ORDER BY created_at DESC LIMIT 1", "s", $thread['id']);
				
				if($replies->num_rows < 1) : ?>
				
				<span>No recent replies.</span>
				
				<?php else :
				
				$reply = $replies->fetch_assoc(); ?>
				<div class="reply">
					<?php $post_user = sqli_result("SELECT id, nickname FROM users WHERE id=?", "s", $reply['user_id']);
					$post_user = $post_user->fetch_assoc(); ?>
					<a class="user" href="<?=FILEPATH."user?id=".$post_user['id']?>">
						<?=$post_user['nickname']?>
					</a>
					<a class="goto-reply" href="<?=FILEPATH."forum/thread?id=".$thread['id']."&reply=".$reply['id']?>">
						>>
					</a>
					<br />
					<span class="date"><?=$thread['updated_at']?></span>
				</div>
				<?php endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>