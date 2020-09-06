<?php
if(isset($_GET["id"])) {
	$thread = sqli_result_bindvar("SELECT id, board_id, title, deleted FROM threads WHERE id=?", "s", $_GET["id"]);
	
	if($thread->num_rows < 1) {
		header('Location: /404');
		exit();
	}
	
	$thread = $thread->fetch_assoc();
	
	$board = sqli_result_bindvar("SELECT id, name, permission_level FROM boards WHERE id=?", "s", $thread['board_id']);
	$board = $board->fetch_assoc();
	$board_permission_level = $board['permission_level'];
	
	// redirect if user lacks access
	if($permission_level < $board_permission_level || $thread['deleted'] === 1 && $permission_level < $permission_levels['Moderator']) {
		header('Location: /403');
		exit();
	}
} else {
	header('Location: /404');
	exit();
}

$stmt = $db->prepare('SELECT COUNT(id) FROM thread_replies WHERE thread_id=? AND deleted=0');
$stmt->bind_param('i', $thread['id']);
$stmt->execute();
$total_replies = $stmt->get_result();
$stmt->free_result();
$total_replies = $total_replies->fetch_row()[0];

$pagination_offset = 20;

if(isset($_GET["page"])) {
	$pagination_offset_current = ($_GET['page'] - 1) * $pagination_offset;
	
	$stmt = $db->prepare("SELECT id, user_id, body, created_at, updated_at, deleted FROM thread_replies WHERE thread_id=? ORDER BY created_at ASC LIMIT ?, ?");
	$stmt->bind_param("sii", $thread['id'], $pagination_offset_current, $pagination_offset);
} else {
	$stmt = $db->prepare("SELECT id, user_id, body, created_at, updated_at, deleted FROM thread_replies WHERE thread_id=? ORDER BY created_at ASC LIMIT 20");
	$stmt->bind_param("s", $thread['id']);
}

$stmt->execute();
$replies = $stmt->get_result();
$replies__count = $replies->num_rows;
$stmt->free_result();
$replies = $replies->fetch_all(MYSQLI_ASSOC);
?>




<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?=FILEPATH."forum"?>">Forum</a> >
				<a href="<?=FILEPATH."forum/board?id=".$board['id']?>"><?=$board['name']?></a> >
				<span><?=htmlspecialchars($thread['title'])?></span>
			</div>
			
			<h2 class="content-header__title"><?=htmlspecialchars($thread['title'])?></h2>
		</div>
		
		
		
		
		
		<?php if($has_session) : ?>
		<div class="page-actions">
			<div class="page-actions__button-list">
				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--reply-create', true)">
					New Reply
				</button>
				
				<button id="js-watchthread" class="page-actions__action button button--disabled" type="button" disabled>
					Watch Thread
				</button>
				
				<?php if($permission_level >= $permission_levels['Moderator']) : ?>
				
				<button id="js-lockthread" class="page-actions__action button button--disabled" type="button" disabled>
					Lock Thread
				</button>
				
				<?php if($thread['deleted'] === 1) : ?>
				
				<form id="form-undelete-thread" style="display:none" action="/interface" method="POST">
					<input type="hidden" name="action" value="forum-thread-undelete">
					<input type="hidden" name="thread-id" value="<?=$thread['id']?>">
				</form>
				
				<button form="form-undelete-thread" class="page-actions__action button" type="submit">
					Undelete Thread
				</button>
				
				<?php else : ?>
				
				<form id="form-delete-thread" style="display:none" action="/interface" method="POST">
					<input type="hidden" name="action" value="forum-thread-delete">
					<input type="hidden" name="thread-id" value="<?=$thread['id']?>">
				</form>
				
				<button form="form-delete-thread" class="page-actions__action button" type="submit">
					Delete Thread
				</button>
				
				<?php endif; endif ?>
			</div>

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
		
		

		<?php
		if($replies__count < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No replies in specified search.</div>
		
		<?php 
		else :
			foreach($replies as $reply) :
				if($reply['deleted'] === 0) :
		?>
		
		<div id="reply-<?=$reply['id']?>" class="thread-reply">
			<div class="thread-reply__info">
				<?php
				$reply_user = sqli_result_bindvar("SELECT id, nickname, permission_level FROM users WHERE id=?", "s", $reply['user_id']);
				$reply_user = $reply_user->fetch_assoc();
				
				$user_rank = sqli_result_bindvar("SELECT title FROM permission_levels WHERE permission_level <= ? ORDER BY permission_level DESC", "s", $reply_user['permission_level']);
				$user_rank = $user_rank->fetch_row()[0];
				?>
				
				<a class="thread-reply__username" href="<?=FILEPATH."user?id=".$reply_user['id']?>">
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
				<p id="js-reply-body-<?=$reply['id']?>" class="thread-reply__text global__long-text js-reply-body">
					<?=format_user_text($reply['body'])?>
				</p>
				
				<?php if($has_session && $reply['user_id'] === $user['id']) : ?>
				
				<div id="js-reply-edit-<?=$reply['id']?>" class="thread-reply__edit js-reply-edit" style="display:none">
					<form id="form-edit-reply-<?=$reply['id']?>" style="display:none" action="/interface" method="POST">
						<input type="hidden" name="action" value="forum-reply-edit">
						<input type="hidden" name="reply-id" value="<?=$reply['id']?>">
					</form>
					
					<textarea form="form-edit-reply-<?=$reply['id']?>" class="thread-reply__edit-body" name="body"></textarea>
					
					<div class="thread-reply__actions">
						<button form="form-edit-reply-<?=$reply['id']?>" class="thread-reply__action button button--small" type="submit">
							Submit Edit
						</button>
						
						<button id="js-edit-cancel-<?=$reply['id']?>" class="thread-reply__action button button--small js-edit-cancel" type="button">
							Cancel
						</button>
					</div>
				</div>
				
				<?php endif ?>
				
				<?php if($has_session) : ?>
				<div class="thread-reply__actions">
					<button id="js-reply-<?=$reply['id']?>" class="thread-reply__action button button--small button--disabled" type="button" disabled>
						Reply
					</button>
					
					<?php if($reply['user_id'] === $user['id']) : ?>
					
					<button id="js-edit-reply-<?=$reply['id']?>" class="thread-reply__action button button--small js-edit-reply" type="button" data-value="<?=$reply['id']?>">
						Edit
					</button>
					
					<?php endif ?>
					
					<?php if($permission_level >= $permission_levels['Moderator'] || $reply['user_id'] === $user['id']) : ?>
					<form id="form-delete-reply-<?=$reply['id']?>" style="display:none" action="/interface" method="POST">
						<input type="hidden" name="action" value="forum-reply-delete">
						<input type="hidden" name="reply-id" value="<?=$reply['id']?>">
					</form>
					
					<button form="form-delete-reply-<?=$reply['id']?>" class="thread-reply__action button button--small" type="submit">
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
				<?php
				$reply_user = sqli_result_bindvar("SELECT id FROM users WHERE id=?", "s", $reply['user_id']);
				$reply_user = $reply_user->fetch_assoc();
				?>
				
				- Deleted - Posted <span title="<?=utc_date_to_user($reply['created_at'])?>">
					<?=readable_date($reply['created_at'])?>
				</span>
				
				<?php if($permission_level >= $permission_levels['Moderator'] || $has_session && $reply['user_id'] === $user['id']) : ?>
				
				<div class="thread-reply__actions">
					<form id="form-undelete-reply-<?=$reply['id']?>" style="display:none" action="/interface" method="POST">
						<input type="hidden" name="action" value="forum-reply-undelete">
						<input type="hidden" name="reply-id" value="<?=$reply['id']?>">
					</form>
					
					<button form="form-undelete-reply-<?=$reply['id']?>" class="thread-reply__action button button--small" type="submit">
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
				<form action="/interface" method="POST">
					<input type="hidden" name="action" value="forum-thread-reply">
					<input type="hidden" name="thread-id" value="<?=$thread['id']?>">
					
					<label class="label">Body</label>
					<textarea class="text-input text-input--resizable-v" name="body" required></textarea>
					
					<input class="button button--spaced" type="submit" value="Reply">
				</form>
			</div>
		</div>
		<?php endif; ?>
	</div>
</main>