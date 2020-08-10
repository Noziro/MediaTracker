<?php
$stmt = $db->prepare("SELECT id, name, description FROM boards WHERE permission_level <= ? ORDER BY display_order ASC");
$stmt->bind_param("s", $access_level);
$stmt->execute();
$boards = $stmt->get_result();
$boards = $boards->fetch_all(MYSQLI_ASSOC);

?>

<div class="container forum">
	<h2>Forum</h2>
	
	<div class="boards">
		<?php foreach($boards as $board): ?>
		<div class="board">
			<div class="board-description">
				<a href="<?=FILEPATH?>forum/board?id=<?=$board['id']?>">
					<h6><?=$board['name']?></h6>
				</a>
				<p><?=$board['description']?></p>
			</div>
			<div class="recent-threads">
				<?php 				
				$threads = sqli_result("SELECT id, user_id, title, updated_at FROM threads WHERE board_id=? LIMIT 2", "s", $board['id']);
				
				if($threads->num_rows < 1) : ?>
				
				<span>No recent threads.</span>
				
				<?php else :
				
				$threads->fetch_all(MYSQLI_ASSOC);
				
				foreach($threads as $thread) : ?>
				<div class="thread">
					<b class="title">
						<a href="<?=FILEPATH."forum/thread?id=".$thread['id']?>"><?=$thread['title']?></a>
					</b> -
						<span class="user">
						<?php
						$user = sqli_result("SELECT nickname FROM users WHERE id=?", "s", $thread['user_id']);
						echo $user->fetch_row()[0];
						?>
						</span>
					<br />
					<span class="date"><?=$thread['updated_at']?></span>
				</div>
				<?php endforeach; endif ?>
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>