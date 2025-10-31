<?php declare(strict_types=1); /*
This module requires:
- a $module_item array that is either empty or contains valid item data
- a $module_collection array with a valid $rating_system key
*/ ?>

<div class="item-fields">
	<div class="item-fields__divider">
		<span class="item-fields__header">Media Information</span>
	</div>

	<div class="item-fields__field">
		<label class="label">Name <span class="label__desc">(required)</span></label>
		<input class="input input--wide js-autofill" type="text" name="name" <?=autofill_if_set($module_item['name'] ?? null)?> required>
	</div>

	<div class="item-fields__field">
		<label class="label">Total Episodes</label>
		<input class="input input--thin js-autofill" name="episodes" type="number" min="0" <?=autofill_if_set($module_item['episodes'] ?? null)?>>
	</div>

	<div class="item-fields__field item-fields__field--date">
		<label class="label">Media Started At</label>
		<input id="js-set-today-3" class="input input--auto js-autofill" name="started_at" type="date" <?php if( isset($module_item['started_at']) ){ echo 'data-autofill="'.$module_item['started_at'].'"'; } ?>>
		<a class="subtext" onclick="setToday('js-set-today-3')">Today</a>
	</div>

	<div class="item-fields__field item-fields__field--date">
		<label class="label">Media Finished At</label>
		<input id="js-set-today-4" class="input input--auto js-autofill" name="finished_at" type="date" <?php if( isset($module_item['finished_at']) ){ echo 'data-autofill="'.$module_item['finished_at'].'"'; } ?>>
		<a class="subtext" onclick="setToday('js-set-today-4')">Today</a>
	</div>
	

	<div class="item-fields__field">
		<label class="label">Flags</label>
		<div class="checkbox-group">
			<label class="checkbox-group__item">
				<input type="hidden" name="adult" value="0"> <!-- fallback value -->
				<input class="checkbox" type="checkbox" name="adult" value="1" <?php if( isset($module_item['adult']) && $module_item['adult'] === 1 ){ echo 'checked'; } ?>>
				Adult
			</label>
		</div>
	</div>

	<div class="item-fields__field">
		<label class="label">Image</label>

		<?php if(!empty($module_item['image'])) : ?>
		<img src="<?=$module_item['image']?>" style="width: 30px; height: 30px; object-fit: cover;" />
		<input type="hidden" name="image_url" value="<?=$module_item['image']?>">
		<?php endif; ?>

		<input class="file-upload" type="file" name="image" accept=".jpg,.png">
	</div>
</div>

<div class="item-fields">
	<div class="item-fields__divider">
		<span class="item-fields__header">User Data</span>
	</div>

	<div class="item-fields__field">
		<label class="label">Status</label>
		<select class="select" type="text" name="status" required>
			<?php foreach( VALID_STATUS as $status ) : ?>
			<option value=<?=$status?> <?php if( isset($module_item['status']) && $status === $module_item['status'] || !isset($module_item['status']) && $status === 'completed' ){ echo "selected"; }?>><?=ucfirst($status)?></option>
			<?php endforeach; ?>
		</select>
	</div>

	<div class="item-fields__field">
		<label class="label">Rating <span class="label__desc">(out of <?=$module_collection['rating_system']?>)</span></label>
		<input class="input input--thin js-autofill" name="score" type="number" min="0" max="<?=$module_collection['rating_system']?>" <?php if( isset($module_item['score']) ){ echo 'data-autofill="'.score_extrapolate($module_item['score'], $module_collection['rating_system']).'"'; } ?>>
	</div>

	<div class="item-fields__field">
		<label class="label">Completed Episodes</label>
		<input class="input input--thin js-autofill" name="progress" type="number" min="0" <?php if( isset($module_item['progress']) ){ echo 'data-autofill="'.$module_item['progress'].'"'; } ?>>
	</div>

	<div class="item-fields__field">
		<label class="label">
			Rewatched Episodes
			<div class="label__desc">for a 25 episode show, input 25</div>
		</label>
		<input class="input input--thin js-autofill" name="rewatched" type="number" min="0" <?php if( isset($module_item['rewatched']) ){ echo 'data-autofill="'.$module_item['rewatched'].'"'; } ?>>
	</div>

	<div class="item-fields__field item-fields__field--date">
		<label class="label">User Started At</label>
		<input id="js-set-today-1" class="input input--auto js-autofill" name="user_started_at" type="date" max="" <?php if( isset($module_item['user_started_at']) ){ echo 'data-autofill="'.$module_item['user_started_at'].'"'; } ?>>
		<a class="subtext" onclick="setToday('js-set-today-1')">Today</a>
	</div>

	<div class="item-fields__field item-fields__field--date">
		<label class="label">User Finished At</label>
		<input id="js-set-today-2" class="input input--auto js-autofill" name="user_finished_at" type="date" <?php if( isset($module_item['user_finished_at']) ){ echo 'data-autofill="'.$module_item['user_finished_at'].'"'; } ?>>
		<a class="subtext" onclick="setToday('js-set-today-2')">Today</a>
	</div>

	<div class="item-fields__field">
		<label class="label">Flags</label>
		<div class="checkbox-group">
			<label class="checkbox-group__item">
				<input type="hidden" name="favourite" value="0"> <!-- fallback value -->
				<input class="checkbox" type="checkbox" name="favourite" value="1" <?php if( isset($module_item['favourite']) && $module_item['favourite'] === 1 ){ echo 'checked'; } ?>>
				Favourite
			</label>
			
			<label class="checkbox-group__item">
				<input type="hidden" name="private" value="0"> <!-- fallback value -->
				<input class="checkbox" type="checkbox" name="private" value="1" <?php if( isset($module_item['private']) && $module_item['private'] === 1 ){ echo 'checked'; } ?>>
				Private
			</label>
		</div>
	</div>
	
	<div class="item-fields__divider"></div>

	<div class="item-fields__field">
		<label class="label">Comments</label>
		<textarea class="text-input js-autofill" name="comments" <?php if( isset($module_item['comments']) ){ echo 'data-autofill="'.$module_item['comments'].'"'; } ?>></textarea>
	</div>
</div>

<div class="item-fields">
	<div class="item-fields__divider">
		<span class="item-fields__header">Connections</span>
	</div>

	<div class="item-fields__field">
		<?php
		if( isset($module_item['anilist']) && strlen($module_item['anilist']) > 0 ){
			$anilist_parts = explode('/', $module_item['anilist']);
			$anilist_type = $anilist_parts[0];
			$anilist_id = $anilist_parts[1];
		} else {
			$anilist_type = 'anime';
			$anilist_id = '';
		}
		?>
		<label class="label">Anilist</label>
		<div class="c-input-list">
			<div class="c-switch">
				<input id="al1" type="radio" value="anime" name="anilist_type" <?=$anilist_type === 'anime' ? 'checked' : ''?>>
				<label for="al1" class="c-switch__option">
					Anime
				</label>
				<input id="al2" type="radio" value="manga" name="anilist_type" <?=$anilist_type === 'manga' ? 'checked' : ''?>>
				<label for="al2" class="c-switch__option">
					Manga
				</label>
			</div>
			<input class="input input--thin js-autofill" type="text" name="anilist_id" placeholder="123456" <?=autofill_if_set($anilist_id)?>>
		</div>
	</div>

	<div class="item-fields__field">
		<?php
		if( isset($module_item['myanimelist']) && strlen($module_item['myanimelist']) > 0 ){
			$myanimelist_parts = explode('/', $module_item['myanimelist']);
			$myanimelist_type = $myanimelist_parts[0];
			$myanimelist_id = $myanimelist_parts[1];
		} else {
			$myanimelist_type = 'anime';
			$myanimelist_id = '';
		}
		?>
		<label class="label">MyAnimeList</label>
		<div class="c-input-list">
			<div class="c-switch">
				<input id="mal1" type="radio" value="anime" name="myanimelist_type" <?=$myanimelist_type === 'anime' ? 'checked' : ''?>>
				<label for="mal1" class="c-switch__option">
					Anime
				</label>
				<input id="mal2" type="radio" value="manga" name="myanimelist_type" <?=$myanimelist_type === 'manga' ? 'checked' : ''?>>
				<label for="mal2" class="c-switch__option">
					Manga
				</label>
			</div>
			<input class="input input--thin js-autofill" type="text" name="myanimelist_id" placeholder="123456" <?=autofill_if_set($myanimelist_id)?>>
		</div>
	</div>

	<div class="item-fields__field">
		<label class="label">IMDB</label>
		<div class="c-input-list">
			<input class="input input--thin  js-autofill" type="text" name="imdb_id" placeholder="tt1234567" <?autofill_if_set($module_item['imdb'] ?? null)?>>
		</div>
	</div>

	<div class="item-fields__field">
		<?php
		if( isset($module_item['tmdb']) && strlen($module_item['tmdb']) > 0 ){
			$tmdb_parts = explode('/', $module_item['tmdb']);
			$tmdb_type = $tmdb_parts[0];
			$tmdb_id = $tmdb_parts[1];
		} else {
			$tmdb_type = 'movie';
			$tmdb_id = '';
		}
		?>
		<label class="label">TMDB</label>
		<div class="c-input-list">
			<div class="c-switch">
				<input id="tmdb1" type="radio" value="movie" name="tmdb_type" <?=$tmdb_type === 'movie' ? 'checked' : ''?>>
				<label for="tmdb1" class="c-switch__option">
					Movie
				</label>
				<input id="tmdb2" type="radio" value="tv" name="tmdb_type" <?=$tmdb_type === 'tv' ? 'checked' : ''?>>
				<label for="tmdb2" class="c-switch__option">
					TV
				</label>
			</div>
			<input class="input input--thin js-autofill" type="text" name="tmdb_id" placeholder="12345" <?=autofill_if_set($tmdb_id)?>>
		</div>
	</div>

	<div class="item-fields__field">
		<label class="label">Auto-update from</label>
		<select class="select" name="auto_update_source" disabled></select>
		<span class="subtext">Not implemented yet.</span>
	</div>
</div>