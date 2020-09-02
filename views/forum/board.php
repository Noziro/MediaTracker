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

$stmt = $db->prepare('SELECT id FROM threads WHERE board_id=? AND deleted=FALSE');
$stmt->bind_param('s', $_GET['id']);
$stmt->execute();
$total_threads = $stmt->get_result();
$stmt->free_result();
$total_threads = $total_threads->num_rows;

$pagination_offset = 15;

if(isset($_GET["page"])) {
	$pagination_offset_current = ($_GET['page'] - 1) * $pagination_offset;
	
	$stmt = $db->prepare("SELECT id, user_id, title, created_at, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=FALSE ORDER BY updated_at DESC LIMIT ?, ?");
	$stmt->bind_param("sii", $board['id'], $pagination_offset_current, $pagination_offset);
} else {
	$stmt = $db->prepare("SELECT id, user_id, title, created_at, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=FALSE ORDER BY updated_at DESC LIMIT 20");
	$stmt->bind_param("s", $board['id']);
}

$stmt->execute();
$threads = $stmt->get_result();
$stmt->free_result();
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

	
	
	<?php if($has_session || $total_threads > $pagination_offset) : ?>
	<div class="page-actions">
		<?php if($has_session) : ?>
		<div class="page-actions__button-list">
			<button class="page-actions__action button" type="button" onclick="toggleModal('modal--thread-create', true)">
				New Thread
			</button>
		</div>
		<?php endif ?>
		
		<?php if($total_threads > $pagination_offset) : ?>
		<div class="page-actions__pagination">
			Page: 
			
			<?php
			$pagination_pages = ceil($total_threads / $pagination_offset);
			
			// Replaces all "page=#" from URL query 
			$normalized_query = preg_replace("/\&page\=.+?(?=(\&|$))/", "", $_SERVER['QUERY_STRING']);
			
			if($pagination_pages < 8) :
			
			$i = 0;
			while($i < $pagination_pages) :
			$i++;
			?>
			
			<a class="page-actions__pagination-link" href="board?<?=$normalized_query.'&page='.$i?>">
				<?=$i?>
			</a>
			
			<?php endwhile; elseif($pagination_pages >= 8) :
			
			$pages = [1, 2, 3, 4, $pagination_pages-2, $pagination_pages-1, $pagination_pages];
			
			foreach($pages as $p) :
			
			if($p === 4) { echo ' … '; }
			else {
			?>
			
			<a class="page-actions__pagination-link" href="board?<?=$normalized_query.'&page='.$p?>">
				<?=$p?>
			</a>
			
			<?php } endforeach; endif ?>
		</div>
		<?php endif ?>
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
					<h6 class="forum-threads__thread-title"><?=htmlspecialchars($thread['title'])?></h6>
				</a>
				<p class="forum-threads__description">
					<span class="forum-threads__date" title="<?=utc_date_to_user($thread['created_at'])?>">
						<?=readable_date($thread['created_at'])?>
					</span>
					by

					<?php
					if($thread['anonymous'] !== 1) :
					$thread_user = sqli_result_bindvar("SELECT id, nickname FROM users WHERE id=?", "s", $thread['user_id']);
					$thread_user = $thread_user->fetch_assoc();
					?>

					<a class="user" href="<?=FILEPATH."user?id=".$thread_user['id']?>">
						<?=$thread_user['nickname']?>
					</a>

					<?php else : ?>

					<i>- deleted -</i>

					<?php endif; ?>
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
					
					<span class="forum-threads__date" title="<?=utc_date_to_user($thread['updated_at'])?>">
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
	


	<?php if($has_session) : ?>
	<div id="modal--thread-create" class="modal modal--hidden" role="dialog" aria-modal="true">
		<button class="modal__background" onclick="toggleModal('modal--thread-create', false)"></button>
		<div class="modal__inner">
			<a class="modal__close" onclick="toggleModal('modal--thread-create', false)">Close</a>
			<h3 class="modal__header">
				New Thread
			</h3>
			<form action="/interface" method="POST">
				<input type="hidden" name="action" value="forum-thread-create">
				<input type="hidden" name="board-id" value="<?=$board['id']?>">
				
				<label class="label">Title</label>
				<input class="input input--wide" type="text" name="title" required>
				
				<label class="label">Body</label>
				<textarea class="text-input text-input--resizable-v" name="body" required></textarea>
				
				<input class="button button--spaced" type="submit" value="Create">
			</form>
		</div>
	</div>
	<?php endif; ?>
</div>