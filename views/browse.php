<?php declare(strict_types=1);

$users = sql('
	SELECT id, nickname
	FROM users
	ORDER BY created_at DESC
	LIMIT 20');

$random_media = sql('
	SELECT id, user_id, status, name, image
	FROM media
	WHERE private=0 AND deleted=0
	ORDER BY RAND()
	LIMIT 20');

?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<div class="content-header">
			<h2 class="content-header__title">Browse</h2>
		</div>

		<div class="l-leave-a-gap">
			<div class="c-search">
				<div class="c-search__bar js-search-bar">
					<input class="c-search__input" type="search" autocomplete="off" placeholder="Search for Movies, Games, TV, Books, Anime, and more...">
					<button class="button c-search__submit" type="button">Search</button>
				</div>
			</div>
		</div>

		<div class="c-module l-leave-a-gap">
			<h3 class="c-heading">Random Media</h3>

			<div class="l-card-layout">
				<?php
				if( $random_media->row_count > 0 ){
					foreach( $random_media->rows as $module_media ){
						require PATH.'modules/media_card.inc';
					}
				}
				?>
			</div>
		</div>

		<div class="l-columns l-leave-a-gap">
			<div class="l-columns__column l-columns__column--thin">
				<div class="l-columns__section c-module">
					<h4 class="c-heading-minor">Newly Added Media</h4>

					<?php
					$stmt = sql('SELECT id, name FROM media WHERE private=0 AND deleted=0 ORDER BY id DESC LIMIT 20');
					if( $stmt->row_count > 0 ) :
					echo '<div class="c-links-list">';
					foreach( $stmt->rows as $media ) :
					?>

					<a class="c-links-list__link c-links-list__link--one-line" href="/item/<?=$media['id']?>"><?=$media['name']?></a>

					<?php endforeach;
					echo '</div>';
					endif; ?>
				</div>
			</div>

			<div class="l-columns__column l-columns__column--thin">
				<div class="l-columns__section c-module">
					<h4 class="c-heading-minor">New Users</h4>

					<?php
					if( $users->row_count > 0 ) :
					echo '<div class="c-links-list">';
					foreach( $users->rows as $user ) :
					?>

					<a class="c-links-list__link c-links-list__link--one-line" href="/user/<?=$user['id']?>"><?=$user['nickname']?></a>

					<?php endforeach; ?>
					
					<a class="c-links-list__link c-links-list__link--one-line" href="/browse/users"><b>See More</b></a>
					
					</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</main>