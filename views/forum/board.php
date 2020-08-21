<?php
if(isset($_GET["id"])) {
	$board = sqli_result_bindvar("SELECT id, name, description, permission_level FROM boards WHERE id=?", "s", $_GET["id"]);
	
	if($board->num_rows < 1) {
		header('Location: /404');
		exit();
	}
	
	$board = $board->fetch_assoc();
	
	// redirect if user lacks access
	if($permission_level < $board['permission_level']) {
		header('Location: /403');
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

<div class="wrapper__inner">
	<div class="content-header">
		<div class="content-header__breadcrumb">
			<a href="<?=FILEPATH."forum"?>">Forum</a> >
			<span><?=$board['name']?></span>
		</div>
		
		<h2 class="content-header__title"><?=$board['name']?></h2>
		
		<h6 class="content-header__subtitle"><?=$board['description']?></h2>
	</div>
	
	<?php if($has_session) : ?>
	<div class="page-actions">
		<button id="js-newthread" class="page-actions__action button" type="button">
			New Thread
		</button>
	</div>
	<?php endif ?>
	
	<div class="forum-threads">
		<div class="forum-threads__thread-header">
			<div class="forum-threads__thread-description-header">
				Thread Information
			</div>
			
			<div class="forum-threads__recent-replies-header">
				Most Recent Reply
			</div>
		</div>
		
		<?php foreach($threads as $thread): ?>
		<div class="forum-threads__thread">
			<div class="forum-threads__thread-description">
				<a href="<?=FILEPATH?>forum/thread?id=<?=$thread['id']?>">
					<h6 class="forum-threads__thread-title"><?=$thread['title']?></h6>
				</a>
				<p>
					<span class="forum-threads__date" title="<?=$thread['created_at']?>">
						<?=readable_date($thread['created_at'])?>
					</span>
					by
					<?php
					$thread_user = sqli_result_bindvar("SELECT id, nickname FROM users WHERE id=?", "s", $thread['user_id']);
					$thread_user = $thread_user->fetch_assoc();
					?>
					<a class="user" href="<?=FILEPATH."user?id=".$thread_user['id']?>">
						<?=$thread_user['nickname']?>
					</a>
				</p>
			</div>
			<div class="forum-threads__recent-replies">
				<?php 				
				$replies = sqli_result_bindvar("SELECT id, user_id, updated_at FROM thread_replies WHERE thread_id=? ORDER BY created_at DESC LIMIT 1", "s", $thread['id']);
				
				if($replies->num_rows > 0) :
				
				$reply = $replies->fetch_assoc(); ?>
				
				<div class="reply">
					<?php $post_user = sqli_result_bindvar("SELECT id, nickname FROM users WHERE id=?", "s", $reply['user_id']);
					$post_user = $post_user->fetch_assoc(); ?>
					
					<span class="forum-threads__date" title="<?=$thread['updated_at']?>">
						<?=readable_date($thread['updated_at'])?>
					</span>
					by
					<a class="user" href="<?=FILEPATH."user?id=".$post_user['id']?>">
						<?=$post_user['nickname']?>
					</a>
					<a class="goto-reply" href="<?=FILEPATH."forum/thread?id=".$thread['id']."#reply-".$reply['id']?>">
						>>
					</a>
				</div>
				<?php endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>
	
	<div id="js-hidetoggle" class="forum-submit">
		<form action="/interface" method="POST">
			<input type="hidden" name="action" value="forum-thread-create">
			<input type="hidden" name="board-id" value="<?=$board['id']?>">
			
			<label class="forum-submit__label" for="thread-title">Title</label>
			<input id="thread-title" class="forum-submit__title" type="text" name="title" required>
			
			<label class="forum-submit__label" for="thread-body">Body</label>
			<textarea id="thread-body" class="forum-submit__body-text" name="body" required></textarea>
			
			<input class="forum-submit__button button" type="submit" value="Create">
		</form>
	</div>
</div>