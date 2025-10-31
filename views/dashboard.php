<?php declare(strict_types=1);
if( !$has_session ){
	soft_error(403);
	return;
}

$currently_watching = sql('
	SELECT id, user_id, status, name, image, progress, episodes
	FROM media
	WHERE user_id=? AND status = "current"',
	['i', $user['id']]);
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner l-split">
		<div class="c-module c-module--spacious l-split__section">
			<?php if( $currently_watching->row_count > 0 ) : ?>
			<h3 class="c-heading">From Your Lists</h3>
			<div class="l-horizontal">
				<?php foreach( $currently_watching->rows as $module_media ){
					require PATH.'modules/media_card.inc';
				}
				endif; ?>
			</div>

			<h3 class="c-heading">(Friend) Activity Feed</h3>
		</div>
	</div>
</main>