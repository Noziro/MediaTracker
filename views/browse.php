<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<?php if(count($_GET) === 0) : ?>

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

			<a class="c-media" href="/item?id=<?=$media['id']?>">
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
				<a href="/item?id=<?=$media['id']?>"><?=$media['name']?></a>
			</div>

			<?php endforeach; ?>
		</div>



		<?php elseif(isset($_GET['name'])) :

		$stmt = sql('SELECT COUNT(id) FROM media WHERE name LIKE ? AND private=0 AND deleted=0', ['s', '%'.$_GET['name'].'%']);
		if(!$stmt['result']) { finalize('/browse', [$stmt['response_code'], $stmt['response_type']]); }
		$total = reset($stmt['result'][0]);

		$pg = new Pagination();
		$pg->Setup(30, $total);

		$stmt = sql('SELECT id, name, image FROM media WHERE name LIKE ? AND private=0 AND deleted=0 LIMIT ?, ?', ['sii', '%'.$_GET['name'].'%', $pg->offset, $pg->increment]);
		if(!$stmt['result']) { finalize('/browse', [$stmt['response_code'], $stmt['response_type']]); }
		$search = $stmt;
		?>

		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="/browse">Browse</a> >
				<span>Search Results</span>
			</div>
			<h2 class="content-header__title">Search Results</h2>
		</div>

		<?php if($pg->total > $pg->increment) : ?>
		<div class="page-actions">
			<?php $pg->Generate() ?>
		</div>
		<?php endif; ?>

		<div class="c-media-browse">
			<?php
			if($search['rows'] < 1) :

			echo 'No results.';

			else:

			$results = $search['result'];
			foreach($results as $media) :
			?>

			<a class="c-media" href="/item?id=<?=$media['id']?>">
				<div class="c-media__inner" <?php if(strlen($media['image']) > 0) : ?> style="background-image: url(<?=$media['image']?>)" <?php endif; ?>>
					<?=$media['name']?>
				</div>
			</a>

			<?php endforeach; endif; ?>
		</div>

		<?php
		else :

		//finalize('/browse', ['invalid_value']);

		endif;
		?>
	</div>
</main>