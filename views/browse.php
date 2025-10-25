<?php

$users = sql('
	SELECT id, nickname
	FROM users
	ORDER BY created_at DESC
	LIMIT 20');

?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<h2 class="content-header__title">Browse</h2>
		</div>

		<div class="database-search">
			<input type="search" autocomplete="off" class="search-bar" placeholder="Search for Movies, Games, TV, Books, Anime, and more...">
		</div>
		
		<h3 class="c-heading">Random Media</h3>

		<div class="c-media-browse">
			<?php
			$stmt = sql('
				SELECT id, name, image FROM media WHERE private=0 AND deleted=0 ORDER BY RAND() LIMIT 20');
			$random_media = $stmt->rows;
			if( $stmt->row_count > 0 ) :
			foreach($random_media as $media) :
			?>

			<a class="c-media" href="/item/<?=$media['id']?>">
				<div class="c-media__inner" <?php if(strlen($media['image']) > 0) : ?> style="background-image: url(<?=$media['image']?>)" <?php endif; ?>>
					<?=$media['name']?>
				</div>
			</a>

			<?php endforeach; endif; ?>
		</div>

		<div class="l-horizontal">

			<div class="c-media-list">
				<h4 class="c-media-list__heading">Newly Added Media</h3>

				<?php
				$stmt = sql('SELECT id, name FROM media WHERE private=0 AND deleted=0 ORDER BY id DESC LIMIT 20');
				if( $stmt->row_count > 0 ) :
				foreach( $stmt->rows as $media ) :
				?>

				<div class="c-media-list__media">
					<a href="/item/<?=$media['id']?>"><?=$media['name']?></a>
				</div>

				<?php endforeach; endif; ?>
			</div>

			<div class="c-media-list">
				<h4 class="c-media-list__heading">New Users</h3>

				<?php
				if( $users->row_count > 0 ) :
				foreach( $users->rows as $user ) :
				?>

				<div class="c-media-list__media">
					<a href="/user/<?=$user['id']?>"><?=$user['nickname']?></a>
				</div>

				<?php endforeach; ?>
				
				<div class="c-media-list__media">
					<a href="/browse/users"><b>See More</b></a>
				</div>
				
				<?php endif; ?>
			</div>
		</div>
	</div>
</main>