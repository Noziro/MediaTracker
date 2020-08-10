<?php

$boards = $db->query("SELECT id, name, description FROM boards ORDER BY display_order ASC");
$boards = $boards->fetch_all(MYSQLI_ASSOC);

function getRecentThreads($board_id) {
	$threads = $db->query("SELECT id AS thread_id, title, body, created_at FROM thread LIMIT 2");
	return $threads->fetch_assoc();
}

?>

<div class="container">
	<h2>Forum</h2>
	
	<div class="boards">
		<?php foreach ($boards as $board): ?>
		<div class="board">
			<div class="board-description">
				<a href="<?=FILEPATH?>forum?board=<?=$board['id']?>">
					<h6><?=$board['name']?></h6>
				</a>
				<p><?=$board['description']?></p>
			</div>
			<div class="recent-threads">
				Temp
			</div>
		</div>
		<?php endforeach ?>
	</div>
</div>