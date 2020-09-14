<?php
$boards = sqli_result_bindvar('SELECT id, name, description FROM boards WHERE permission_level <= ? ORDER BY display_order ASC', 's', $permission_level);
$boards = $boards->fetch_all(MYSQLI_ASSOC);
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<h2 class="content-header__title">Forum</h2>
		</div>

		<?php if($permission_level >= $permission_levels['Admin']) : ?>
		<div class="page-actions">
			<div class="page-actions__button-list">
				<button class="page-actions__action button button--disabled" type="button" disabled>
					New Board <!-- not sure if this will be implemented or not -->
				</button>
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Edit Board
				</button>
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Rearrange Boards
				</button>
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Delete Board <!-- not sure if this will be implemented or not -->
				</button>
			</div>
		</div>
		<?php endif; ?>
		
		<div class="forum-boards">
			<?php foreach($boards as $board): ?>
			<div class="forum-boards__board">
				<div class="forum-boards__board-info">
					<a href="<?=FILEPATH?>forum/board?id=<?=$board['id']?>">
						<b class="forum-boards__board-title"><?=$board['name']?></b>
					</a>
					<div class="forum-boards__board-description"><?=$board['description']?></div>
				</div>
				<div class="forum-boards__board-aside">
					<?php 				
					$threads = sqli_result_bindvar("SELECT id, user_id, title, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=FALSE ORDER BY updated_at DESC LIMIT 2", "s", $board['id']);
					
					if($threads->num_rows > 0) :
					
					$threads->fetch_all(MYSQLI_ASSOC);
					
					foreach($threads as $thread) :
					
					?>
					
					<div class="forum-boards__recent-thread">
						<b class="forum-boards__thread-title">
							<a href="<?=FILEPATH."forum/thread?id=".$thread['id']?>"><?=htmlspecialchars($thread['title'])?></a>
						</b>
						<br />
						
						<div class="forum-boards__thread-description">
							<span class="forum-boards__thread-date" title="<?=utc_date_to_user($thread['updated_at'])?>">
								<?=readable_date($thread['updated_at'])?>
							</span>
							by

							<?php
							if($thread['anonymous'] !== 1) :
							$thread_username = sqli_result_bindvar("SELECT nickname FROM users WHERE id=?", "s", $thread['user_id']);
							$thread_username = $thread_username->fetch_row()[0];
							?>

							<span class="forum-boards__thread-author">
								<?=$thread_username?>
							</span>

							<?php else : ?>

							<i>- deleted -</i>

							<?php endif; ?>
							
						</div>
					</div>
					
					<?php endforeach; endif ?>
				</div>
			</div>
			<?php endforeach ?>
		</div>

		<div class="dialog-box dialog-box--fullsize">
			<?php
			$threads = sqli_result('SELECT COUNT(*) FROM threads WHERE deleted=0');
			$threads = $threads->fetch_row()[0];
			$replies = sqli_result('SELECT COUNT(*) FROM replies WHERE deleted=0');
			$replies = $replies->fetch_row()[0];
			?>

			Total Threads: <?=$threads?> - Total Replies: <?=$replies?>
		</div>

		<?php if($permission_level >= $permission_levels['Admin']) : ?>
		<div class="">
			<!-- TODO MODAL/FORMS -->
		</div>
		<?php endif; ?>
	</div>
</main>