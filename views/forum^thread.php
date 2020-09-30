<?php
if(isset($_GET["id"])) {
	$thread = sql('SELECT id, board_id, title, locked, deleted FROM threads WHERE id=? LIMIT 1', ['i', $_GET['id']]);
	
	if($thread['rows'] < 1) {
		header('Location: /404');
		exit();
	}
	
	$thread = $thread['result'][0];
	
	$board = sql('SELECT id, name, permission_level FROM boards WHERE id=?', ['i', $thread['board_id']])['result'][0];
	
	// redirect if user lacks access
	if($permission_level < $board['permission_level'] || $thread['deleted'] === 1 && $permission_level < $permission_levels['Moderator']) {
		header('Location: /403');
		exit();
	}
} else {
	header('Location: /404');
	exit();
}

$total_replies = reset(sql('SELECT COUNT(id) FROM replies WHERE thread_id=? AND deleted=0', ['i', $thread['id']])['result'][0]);

$pagination_offset = 20;

if(isset($_GET["page"])) {
	$pagination_offset_current = ($_GET['page'] - 1) * $pagination_offset;
	$replies = sql('SELECT id, user_id, body, created_at, updated_at, deleted FROM replies WHERE thread_id=? ORDER BY created_at ASC LIMIT ?, ?', ['iii', $thread['id'], $pagination_offset_current, $pagination_offset]);
} else {
	$replies = sql('SELECT id, user_id, body, created_at, updated_at, deleted FROM replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 20', ['i', $thread['id']]);
}
?>




<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?=FILEPATH."forum"?>">Forum</a> >
				<a href="<?=FILEPATH."forum/board/".$board['id']?>"><?=$board['name']?></a> >
				<span><?=htmlspecialchars($thread['title'])?></span>
			</div>
			
			<h2 class="content-header__title"><?=htmlspecialchars($thread['title'])?></h2>
		</div>
		
		
		
		
		
		<?php if($has_session && $permission_level >= $permission_levels['Moderator'] || $thread['locked'] === 0 || $total_replies > $pagination_offset) : ?>
		<div class="page-actions">
			<?php if($permission_levels['Moderator'] || $thread['locked'] === 0) : ?>
			<div class="page-actions__button-list">
				<?php
				if($thread['locked'] === 0 || $permission_level >= $permission_levels['Moderator']) :
				?>

				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--reply-create', true)">
					New Reply
				</button>
				
				<?php
				endif;
				?>
				
				<button id="js-watchthread" class="page-actions__action button button--disabled" type="button" disabled>
					Watch Thread
				</button>

				<?php
				if($permission_level >= $permission_levels['Moderator']) :
					if($thread['locked'] === 1) :
				?>

				<button class="page-actions__action button" type="button" onclick="modalConfirmation('Are you sure you wish to lock this thread?', 'forum_thread_unlock', 'thread_id', <?=$thread['id']?>)">
					Unlock Thread
				</button>

				<?php
					else :
				?>
				
				<button class="page-actions__action button" type="button" onclick="modalConfirmation('Are you sure you wish to lock this thread?', 'forum_thread_lock', 'thread_id', <?=$thread['id']?>)">
					Lock Thread
				</button>
				
				<?php
					endif;
					if($thread['deleted'] === 1) :
				?>
				
				<button class="page-actions__action button" onclick="modalConfirmation('Are you sure you wish to <u>un</u>delete this thread?', 'forum_thread_undelete', 'thread_id', <?=$thread['id']?>)">
					Undelete Thread
				</button>
				
				<?php
					else :
				?>
				
				<button class="page-actions__action button" onclick="modalConfirmation('Are you sure you wish to delete this thread?', 'forum_thread_delete', 'thread_id', <?=$thread['id']?>)">
					Delete Thread
				</button>
				
				<?php
					endif;
				endif;
				?>
			</div>
			<?php endif; ?>

			<?php if($total_replies > $pagination_offset) : ?>
			<div class="page-actions__pagination">
				Page: 
				
				<?php
				$pagination_pages = ceil($total_replies / $pagination_offset);
				
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
		
		



		<?php if($thread['locked'] === 1) : ?>

		<div class="dialog-box">This thread is locked and may no longer be replied to.</div>

		<?php endif; ?>
		


		<?php if($thread['deleted'] === 1) : ?>

		<div class="dialog-box">This thread is deleted and only visible to moderators.</div>

		<?php endif; ?>





		<?php
		if($replies['rows'] < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No replies in specified search.</div>
		
		<?php 
		else :
			foreach($replies['result'] as $reply) :
				if($reply['deleted'] === 0) :
		?>
		
		<div id="reply-<?=$reply['id']?>" class="thread-reply">
			<div class="thread-reply__info">
				<?php
				$reply_user = sql('SELECT id, nickname, permission_level FROM users WHERE id=?', ['i', $reply['user_id']])['result'][0];
				$user_rank = sql('SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC LIMIT 1', ['i', $reply_user['permission_level']])['result'][0]['title'];
				?>
				
				<a class="thread-reply__username" href="<?=FILEPATH."user/".$reply_user['id']?>">
					<?=$reply_user['nickname']?>
				</a>
				
				<div class="thread-reply__info-line">
					<span class="user-rank user-rank--<?=strtolower($user_rank)?>">
						<?=$user_rank?>
					</span>
				</div>
				
				<div class="thread-reply__info-line">
					<span title="<?=utc_date_to_user($reply['created_at'])?>">
					<?=readable_date($reply['created_at'])?>
					</span>
				</div>
				
				<?php if($reply['updated_at'] !== $reply['created_at']) : ?>
				
				<div class="thread-reply__info-line">
					<i>Edited <span title="<?=utc_date_to_user($reply['updated_at'])?>">
					<?=readable_date($reply['updated_at'])?>
					</span></i>
				</div>
				
				<?php endif ?>
			</div>
			
			
			
			<div class="thread-reply__content">
				<div id="js-truncate-<?=$reply['id']?>" class="c-truncate js-truncate" data-reply="<?=$reply['id']?>">
					<p id="js-reply-body-<?=$reply['id']?>" class="thread-reply__text u-readable-line-height js-reply-body">
						<?=format_user_text($reply['body'])?>
					</p>
					
					<div id="js-truncate-overlay-<?=$reply['id']?>" class="c-truncate__overlay is-hidden">
						<button class="button" onclick="untruncate_reply(<?=$reply['id']?>)">Show More</button>
					</div>

					<div id="js-truncate-close-<?=$reply['id']?>" class="l-button-list l-button-list--center is-hidden">
						<button class="l-button-list__button button" onclick="truncate_reply(<?=$reply['id']?>)">Show Less</button>
					</div>
				</div>

				<?php if($has_session && $reply['user_id'] === $user['id']) : ?>
				
				<div id="js-reply-edit-<?=$reply['id']?>" class="thread-reply__edit js-reply-edit" style="display:none">
					<form id="form-edit-reply-<?=$reply['id']?>" style="display:none" action="/interface/generic" method="POST">
						<input type="hidden" name="action" value="forum_reply_edit">
						<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI'].'#reply-'.$reply['id']?>">
						<input type="hidden" name="reply_id" value="<?=$reply['id']?>">
					</form>
					
					<textarea form="form-edit-reply-<?=$reply['id']?>" class="thread-reply__edit-body js-autofill" name="body" data-autofill="<?=$reply['body']?>"></textarea>
					
					<div class="thread-reply__actions">
						<button form="form-edit-reply-<?=$reply['id']?>" class="thread-reply__action button button--small" type="submit">
							Submit Edit
						</button>
						
						<button id="js-edit-cancel-<?=$reply['id']?>" class="thread-reply__action button button--small js-edit-cancel" type="button" onclick="toggleEdit(<?=$reply['id']?>, false)">
							Cancel
						</button>
					</div>
				</div>
				
				<?php endif ?>
				
				<?php if($has_session || $permission_level >= $permission_levels['Moderator']) : ?>
				<div class="thread-reply__actions">
					<?php if($has_session && $thread['locked'] === 0 || $permission_level >= $permission_levels['Moderator']) : ?>
					<button id="js-reply-<?=$reply['id']?>" class="thread-reply__action button button--small button--disabled" type="button" disabled>
						Reply
					</button>
					<?php endif; ?>
					
					<?php if($reply['user_id'] === $user['id']) : ?>
					
					<button id="js-edit-reply-<?=$reply['id']?>" class="thread-reply__action button button--small js-edit-reply" type="button" onclick="toggleEdit(<?=$reply['id']?>, true)">
						Edit
					</button>
					
					<?php endif ?>
					
					<?php if($permission_level >= $permission_levels['Moderator'] || $reply['user_id'] === $user['id']) : ?>
					<button class="thread-reply__action button button--small" onclick="modalConfirmation('Are you sure you wish to delete this post?', 'forum_reply_delete', 'reply_id', <?=$reply['id']?>)">
						Delete
					</button>
					<?php endif ?>
				</div>
				<?php endif ?>
			</div>
		</div>
		
		
		<?php
				else :
		?>
		
		
		<div id="reply-<?=$reply['id']?>" class="thread-reply thread-reply--deleted">
			<div class="thread-reply__deleted">
				- Deleted - Posted <span title="<?=utc_date_to_user($reply['created_at'])?>">
					<?=readable_date($reply['created_at'])?>
				</span>
				
				<?php if($permission_level >= $permission_levels['Moderator'] || $has_session && $reply['user_id'] === $user['id']) : ?>
				
				<div class="thread-reply__actions">
					<button class="thread-reply__action button button--small" onclick="modalConfirmation('Are you sure you wish to <u>un</u>delete this post?', 'forum_reply_undelete', 'reply_id', <?=$reply['id']?>)">
						Undelete
					</button>
				</div>
				
				<?php endif ?>
			</div>
		</div>
		
		<?php
				endif;
			endforeach;
		endif;
		?>


		
		<?php if($has_session) : ?>

		<div id="modal--reply-create" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--reply-create', false)"></button>
			<div class="modal__inner">
				<a class="modal__close" onclick="toggleModal('modal--reply-create', false)">Close</a>
				<h3 class="modal__header">
					New Reply
				</h3>
				<form action="/interface/generic" method="POST">
					<input type="hidden" name="action" value="forum_reply_create">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="thread_id" value="<?=$thread['id']?>">
					
					<label class="label">Body</label>
					<textarea class="text-input text-input--resizable-v" name="body" required></textarea>
					
					<input class="button button--spaced" type="submit" value="Reply">
				</form>
			</div>
		</div>



		<?php if($has_session) {
			include PATH.'server/includes/modal-confirmation.inc';
		} ?>

		<?php endif; ?>
	</div>
</main>