<?php //header("Location: /404"); exit(); ?>
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
			$stmt = sql('SELECT id, name, image FROM media WHERE private=0 AND deleted=0 ORDER BY RAND() LIMIT 20');
			$random_media = $stmt['result'];
			foreach($random_media as $media) :
			?>

			<a class="c-media" href="/media/<?=$media['id']?>">
				<div class="c-media__inner" <?php if(strlen($media['image']) > 0) : ?> style="background-image: url(<?=$media['image']?>)" <?php endif; ?>>
					<?=$media['name']?>
				</div>
			</a>

			<?php endforeach; ?>
		</div>

		<div class="c-media-list">
			<h4 class="c-media-list__heading">Newly Added Media</h3>

			<?php
			$stmt = sql('SELECT id, name FROM media WHERE private=0 AND deleted=0 ORDER BY id DESC LIMIT 20');
			$new_media = $stmt['result'];
			foreach($new_media as $media) :
			?>

			<div class="c-media-list__media">
				<a href="/media/<?=$media['id']?>"><?=$media['name']?></a>
			</div>

			<?php endforeach; ?>
		</div>
	</div>
</main>