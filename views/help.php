<?php declare(strict_types=1); ?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<?php if(!isset($_GET['section'])):  ?>
		
		Add support/help section here.
		
		<?php elseif($_GET['section'] == 'bbcode') : ?>
		
		Add bbcode info here after BBCode is added
		
		<?php endif; ?>
	</div>
</main>