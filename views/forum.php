<?php
$boards = sqli_result_bindvar('SELECT id, name, description FROM boards WHERE permission_level <= ? ORDER BY display_order ASC', 's', $permission_level);
$boards = $boards->fetch_all(MYSQLI_ASSOC);

?>

<div class="wrapper__inner">
	<div class="content-header">
		<h2 class="content-header__title">Forum</h2>
	</div>
	
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
				$threads = sqli_result_bindvar("SELECT id, user_id, title, updated_at FROM threads WHERE board_id=? ORDER BY updated_at DESC LIMIT 2", "s", $board['id']);
				
				if($threads->num_rows > 0) :
				
				$threads->fetch_all(MYSQLI_ASSOC);
				
				foreach($threads as $thread) :
				
				?>
				
				<div class="forum-boards__recent-thread">
					<b class="forum-boards__thread-title">
						<a href="<?=FILEPATH."forum/thread?id=".$thread['id']?>"><?=$thread['title']?></a>
					</b>
					<br />
					
					<div class="forum-boards__thread-description">
						<span class="forum-boards__thread-date" title="<?=utc_date_to_user($thread['updated_at'])?>">
							<?=readable_date($thread['updated_at'])?>
						</span>
						by
						<span class="forum-boards__thread-author">
							<?php
							$user = sqli_result_bindvar("SELECT nickname FROM users WHERE id=?", "s", $thread['user_id']);
							echo $user->fetch_row()[0];
							?>
						</span>
					</div>
				</div>
				
				<?php endforeach; endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>