<?php
$sql = sql('SELECT id, name, description FROM boards WHERE permission_level <= ? ORDER BY display_order ASC', ['i', $permission_level]);
if(!$sql['result']) {
	finalize('/404', [$sql['response_code'], $sql['response_type']]);
}
$boards = $sql['result'];
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
		
		<table class="table c-forum">
			<tbody>
				<?php foreach($boards as $board): ?>
				<tr class="table__body-row table__body-row--spacious">
					<td class="table__cell table__cell--extra-spacious">
						<a href="/forum/board?id=<?=$board['id']?>">
							<b class="forum-boards__board-title"><?=$board['name']?></b>
						</a>
						<p class="c-forum__board-description"><?=$board['description']?></p>
					</td>
					<td class="table__cell table__cell--extra-spacious table__cell--one-third">
						<?php
						$threads = sql("SELECT id, user_id, title, updated_at, anonymous FROM threads WHERE board_id=? AND deleted=0 ORDER BY updated_at DESC LIMIT 2", ["i", $board['id']]);
						
						if($threads['rows'] > 0) :
							$threads = $threads['result'];

							foreach($threads as $thread) :
						?>
						
						<div class="c-forum__aside-item">
							<b class="u-bold">
								<a href="<?="/forum/thread?id=".$thread['id']?>"><?=htmlspecialchars($thread['title'])?></a>
							</b>
							<br />
							
							<div class="c-forum__aside-description">
								<span class="forum-boards__thread-date" title="<?=utc_date_to_user($thread['updated_at'])?>">
									<?=readable_date($thread['updated_at'])?>
								</span>
								by

								<?php
								if($thread['anonymous'] !== 1) :
									$thread_username = sql("SELECT nickname FROM users WHERE id=?", ["i",$thread['user_id']])['result'][0]['nickname'];
								?>

								<span class="forum-boards__thread-author">
									<?=$thread_username?>
								</span>

								<?php else : ?>

								<i>- deleted -</i>

								<?php endif; ?>
								
							</div>
						</div>
						
						<?php
							endforeach;
						endif;
						?>
					</td>
				</tr>
				<?php endforeach ?>
			</tbody>
		</table>

		<div class="dialog-box dialog-box--fullsize">
			<?php
			$threads = reset(sql('SELECT COUNT(*) FROM threads WHERE deleted=0')['result'][0]);
			$replies = reset(sql('SELECT COUNT(*) FROM replies WHERE deleted=0')['result'][0]);
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