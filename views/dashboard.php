<?php
if( !$has_session ){
	finalize('/403');
}

$currently_watching = sql('
	SELECT id, user_id, status, name, image, progress, episodes
	FROM media
	WHERE user_id=? AND status = "current"',
	['i', $user['id']]);
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner split">
		<div class="split__section">
			<?php if( $currently_watching->row_count > 0 ) : ?>
			<h3 class="c-heading">From Your Lists</h3>
			<div class="l-horizontal">
				<?php foreach( $currently_watching->rows as $module_media ){
					include(PATH.'modules/media_card.php');
				}
				endif; ?>
			</div>

			<h3 class="c-heading">(Friend) Activity Feed</h3>
		</div>
	</div>
</main>