<?php declare(strict_types=1);

$page = isset(URL['PATH_ARRAY'][1]) ? URL['PATH_ARRAY'][1] : '';
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<?php if( $page === '' ) : ?>
		
		Add support/help section here.
		
		<?php elseif( $page === 'bbcode') : ?>
		
		Add bbcode info here after BBCode is added
		
		<?php endif; ?>
	</div>
</main>