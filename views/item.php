<?php

if( !isset($_GET['id']) ){
	finalize('/404');
}

$stmt = sql('SELECT id, user_id, collection_id, image, name, episodes, release_date, started_at, finished_at, links, adult, private, deleted FROM media WHERE id=? LIMIT 1', ['i', $_GET['id']]);
if( !$stmt->ok || $stmt->row_count === 0 ){
	finalize('/404');
}
$item = $stmt->rows[0];

if( $item['deleted'] === 1 ){
	finalize('/404');
}

if($item['private'] === 1 && !$has_session ||
   $item['private'] === 1 && $user['id'] !== $item['user_id']) {
	finalize('/403');
}

$stmt = sql('SELECT id, user_id, name, type, private FROM collections WHERE id=?', ['s', $item['collection_id']]);
if( !$stmt->ok || $stmt->row_count === 0 ){
	finalize('/404');
}
$collection = $stmt->rows[0];
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<h2 class="content-header__title">
				<?=$item['name']?>
			</h2>

			<?php if($item['adult'] === 1) : ?>
			<h6 class="content-header__subtitle">Adult</h6>
			<?php endif; ?>
		</div>
		
		<?php if(!empty($item['image'])) : ?>
		<img src="<?=$item['image']?>" style="width: 300px; height: 450px; float: right; object-fit: cover;" />
		<?php endif; ?>

		<div>Episodes: <?=$item['episodes'];?></div>

		<?php
		if(!empty($item['started_at']) || !empty($item['finished_at'])) :
		?>

		<br />

		<div>Dated <?=$item['started_at'];?> to <?=$item['finished_at'];?></div>
		
		<?php endif; ?>

		<?php
		$links = json_decode($item['links']);
		if($links !== null && count($links) !== 0) : ?>

		<br />

		<div>
			Links:<br />

			<?php
			foreach( $links as $link ){
				echo '<a href="'.$link.'">'.$link.'</a><br />';
			}
			?>
		</div>

		<?php endif; ?>

		<br />
		<br />

		<?php
		$stmt = sql('SELECT users.nickname FROM users INNER JOIN media ON media.user_id = users.id WHERE users.id=? LIMIT 1', ['i', $item['user_id']]);
		if(    !$stmt->ok
			|| $stmt->row_count < 1
			|| $item['private'] === 1 && !$has_session
			|| $item['private'] === 1 && $user['id'] !== $item['user_id']
			|| $collection['private'] === 9 && !$has_session
			|| $collection['private'] === 9 && $user['id'] !== $item['user_id']
		) :
		?>
		<div>Added by an anonymous user</div>
		<?php else : ?>
		<div>Added by <a href="/user?u=<?=$item['user_id']?>"><?=$stmt->rows[0]['nickname']?></a></div>
		<?php endif; ?>
	</div>
</main>