<?php
if(isset($_GET["id"])) {
	$board = sql("SELECT id, name, description, permission_level FROM boards WHERE id=? LIMIT 1", ["i", $_GET["id"]]);
	
	if($board['rows'] < 1) {
		header('Location: /404');
		exit();
	}
	
	$board = $board['result'][0];
	
	// redirect if user lacks access
	if($permission_level < $board['permission_level']) {
		header('Location: /403');
		exit();
	}
} else {
	header('Location: /404');
	exit();
}

$total_threads = reset(sql('SELECT COUNT(id) FROM threads WHERE board_id=? AND deleted=0', ['i', $board['id']])['result'][0]);

$pagination_offset = 15;

if(isset($_GET["page"])) {
	$pagination_offset_current = ($_GET['page'] - 1) * $pagination_offset;
	$threads = sql("SELECT id, user_id, title, created_at, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=0 ORDER BY updated_at DESC LIMIT ?, ?", ["iii", $board['id'], $pagination_offset_current, $pagination_offset]);
} else {
	$threads = sql("SELECT id, user_id, title, created_at, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=0 ORDER BY updated_at DESC LIMIT 20", ["i", $board['id']]);
}
?>

<main id="content" class="wrapper wrapper--content">
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
				
				<a class="page-actions__pagination-link" href="?<?=$normalized_query.'&page='.$i?>">
					<?=$i?>
				</a>
				
				<?php endwhile; elseif($pagination_pages >= 8) :
				
				$pages = [1, 2, 3, 4, $pagination_pages-2, $pagination_pages-1, $pagination_pages];
				
				foreach($pages as $p) :
				
				if($p === 4) { echo ' … '; }
				else {
				?>
				
				<a class="page-actions__pagination-link" href="?<?=$normalized_query.'&page='.$p?>">
					<?=$p?>
				</a>
				
				<?php } endforeach; endif ?>
			</div>
			<?php endif ?>
		</div>
		<?php endif ?>
		


		<?php
		if($total_threads < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No threads yet.</div>

		<?php
		elseif($threads['rows'] < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No threads in specified search.</div>

		<?php
		else :
		?>

		<table class="table">
			<thead>
				<tr>
					<td class="table__cell table__cell--emphasized">
						<b class="table__heading">Thread Information</b>
					</td>
					
					<td class="table__cell table__cell--emphasized table__cell--small u-text-center">
						<b class="table__heading">Replies</b>
					</td>

					<td class="table__cell table__cell--emphasized table__cell--one-third">
						<b class="table__heading">Most Recent Reply</b>
					</td>
				</tr>
			</thead>
			
			<tbody>
				<?php foreach($threads['result'] as $thread): ?>
				<tr class="table__body-row">
					<td class="table__cell">
						<a href="<?=FILEPATH?>forum/thread?id=<?=$thread['id']?>">
							<b class="table__body-row-title"><?=htmlspecialchars($thread['title'])?></b>
						</a>
						<p class="c-forum__thread-description">
							<span title="<?=utc_date_to_user($thread['created_at'])?>">
								<?=readable_date($thread['created_at'])?>
							</span>
							by

							<?php
							if($thread['anonymous'] !== 1) :
							$thread_user = sql("SELECT id, nickname FROM users WHERE id=?", ["s", $thread['user_id']])['result'][0];
							?>

							<a href="<?=FILEPATH."user?id=".$thread_user['id']?>">
								<?=$thread_user['nickname']?>
							</a>

							<?php else : ?>

							<i>- deleted -</i>

							<?php endif; ?>
						</p>
					</td>
					<td class="table__cell u-text-center">
						<?php
						$reply__count = reset(sql('SELECT COUNT(id) FROM replies WHERE thread_id=?', ['i', $thread['id']])['result'][0]);
						echo $reply__count - 1;
						?>
					</td>
					<td class="table__cell">
						<?php 				
						$replies = sql('SELECT id, user_id, updated_at FROM replies WHERE thread_id=? ORDER BY created_at DESC LIMIT 1', ['i', $thread['id']]);
						
						if($replies['rows'] > 0) :
						
						$reply = $replies['result'][0] ?>
						
						<div class="reply">
							<?php $post_user = sql('SELECT id, nickname FROM users WHERE id=?', ['s', $reply['user_id']])['result'][0]; ?>
							
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
					</td>
				</div>
				<?php endforeach ?>
			</tbody>
		</table>

		<?php
		endif;
		?>
		


		<?php if($has_session) : ?>
		<div id="modal--thread-create" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--thread-create', false)"></button>
			<div class="modal__inner">
				<a class="modal__close" onclick="toggleModal('modal--thread-create', false)">Close</a>
				<h3 class="modal__header">
					New Thread
				</h3>
				<form action="/interface" method="POST">
					<input type="hidden" name="action" value="forum_thread_create">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="board_id" value="<?=$board['id']?>">
					
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
</main>