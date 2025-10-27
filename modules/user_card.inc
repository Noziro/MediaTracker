<?php declare(strict_types=1); /*
This module expects a variable called $module_user with valid user data of:
- id
- banner_image
- profile_image
- nickname
- created_at
*/ ?>

<div class="c-user-card" <?php if( isset($module_user['banner_image']) ) { echo 'style="--banner: url('.$module_user['banner_image'].')"'; } ?>>
	<img class="c-user-card__avatar" src="<?=$module_user['profile_image']?>"></img>
	<div class="c-user-card__text">
		<a href="/user/<?=$module_user['id']?>"><b class="c-user-card__name"><?=$module_user['nickname']?></b></a>
		<span class="subtext" title="User since <?=utc_date_to_user($module_user['created_at'])?>.">
			User for <?=readable_date($module_user['created_at'], false)?>
		</span>
	</div>
</div>