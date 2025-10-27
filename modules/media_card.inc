<?php /*
This module requires:
- A $module_media variable with the following valid media data:
  - id
  - user_id
  - name
  - image
  - status
- Optionally, also include these:
  - progress
  - episodes
*/ ?>

<div class="c-media-card" <?php if( isset($module_media['image']) ) { echo 'style="--cover: url('.$module_media['image'].')"'; } ?>>
	<?php if( $has_session && $module_media['user_id'] === $user['id'] ) : ?>
	<span class="c-media-card__status"><?=ucfirst($module_media['status'])?></span>
	<div class="c-media-card__actions">
		<a href="/item/<?=$module_media['id']?>/edit?return_to=<?=URL['PATH_STRING']?>" class="c-media-card__button">Edit</a>
	</div>
	<?php endif; ?>
	<div class="c-media-card__text">
		<a href="/item/<?=$module_media['id']?>" class="c-media-card__name"><?=$module_media['name']?></a>
		<?php if( $has_session && isset($module_media['episodes']) && $module_media['episodes'] > 0 ) : ?>
		<div class="c-media-card__count"><?=$module_media['progress']?> / <?=$module_media['episodes']?></div>
		<?php endif; ?>
	</div>
</div>