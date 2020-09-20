<?php
if(isset($_GET['id'])) {
    $item__id = $_GET['id'];

    $item = sqli_result_bindvar('SELECT id, user_id, collection_id, status, name, score, episodes, progress, rewatched, user_started_at, user_finished_at, release_date, started_at, finished_at, comments, links, adult, favourite FROM media WHERE id=? LIMIT 1', 's', $item__id);
	$item_count = $item->num_rows;
	$item = $item->fetch_assoc();

	$collection = sqli_result_bindvar('SELECT id, user_id, name, type, display_score, display_progress, display_user_started, display_user_finished, display_days, rating_system, private FROM collections WHERE id=?', 's', $item['collection_id']);
	$collection = $collection->fetch_assoc();

	// Item exists
	if($item_count < 1) {
		finalize('/404');
	}

	// User authority
	elseif(!$has_session || $user['id'] !== $item['user_id']) {
		finalize('/403');
	}
} else {
	finalize('/404');
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<form id="collection-item-edit" action="/interface" method="POST">
			<input type="hidden" name="action" value="collection_item_edit">
			<input type="hidden" name="item" value="<?=$item['id']?>">

			<div class="item-fields">
				<div class="item-fields__divider">
					<span class="item-fields__header">Item Information</span>
				</div>

				<div class="item-fields__field">
					<label class="label">Name <span class="label__desc">(required)</span></label>
					<input class="input input--wide js-autofill" type="text" name="name" data-autofill="<?=$item['name']?>" required>
				</div>

				<div class="item-fields__field">
					<label class="label">Total Episodes</label>
					<input class="input input--thin js-autofill" name="episodes" type="number" min="0" <?php if(isset($item['episodes'])) { echo 'data-autofill="'.$item['episodes'].'"'; } ?>>
				</div>

				<!--<div class="item-fields__field item-fields__field--date">
					<label class="label">Media Release Date</label>
					<input class="input input--auto" name="release_date" type="date">
				</div>-->

				<div class="item-fields__field item-fields__field--date">
					<label class="label">Media Started At</label>
					<input id="js-set-today-3" class="input input--auto js-autofill" name="started_at" type="date" <?php if(isset($item['started_at'])) { echo 'data-autofill="'.$item['started_at'].'"'; } ?>>
					<a class="subtext" onclick="setToday('js-set-today-3')">Today</a>
				</div>

				<div class="item-fields__field item-fields__field--date">
					<label class="label">Media Finished At</label>
					<input id="js-set-today-4" class="input input--auto js-autofill" name="finished_at" type="date" <?php if(isset($item['finished_at'])) { echo 'data-autofill="'.$item['finished_at'].'"'; } ?>>
					<a class="subtext" onclick="setToday('js-set-today-4')">Today</a>
				</div>

				<div class="item-fields__field">
					<label class="label">Flags</label>
					<div class="checkbox-group">
						<label class="checkbox-group__item">
							<input type="hidden" name="adult" value="0"> <!-- fallback value -->
							<input class="checkbox" type="checkbox" name="adult" value="1" <?php if($item['adult'] === 1) { echo 'checked'; } ?>>
							Adult
						</label>

						<label class="checkbox-group__item">
							<input type="hidden" name="favourite" value="0"> <!-- fallback value -->
							<input class="checkbox" type="checkbox" name="favourite" value="1" <?php if($item['favourite'] === 1) { echo 'checked'; } ?>>
							Favourite
						</label>
					</div>
				</div>

				<div class="item-fields__field">
					<label class="label">Links</label>
					<?php
					$links = json_decode($item['links']);
					if(is_array($links) && count($links) > 0) :
						foreach($links as $link) :
					?>
					<input class="input js-autofill" type="text" name="links[]" data-autofill="<?=$link?>">
					<?php
						endforeach;
					else :
					?>

					<input class="input" type="text" name="links[]">
					<input class="input" type="text" name="links[]">
					<input class="input" type="text" name="links[]">

					<?php
					endif;
					?>
				</div>


				<div class="item-fields__divider">
					<span class="item-fields__header">User Data</span>
				</div>

				<div class="item-fields__field">
					<label class="label">Status</label>
					<select class="select" type="text" name="status" required>
						<?php foreach($valid_status as $status) : ?>
						<option <?php if($status === $item['status']) { echo "selected"; }?>><?=$status?></option>
						<?php endforeach; ?>
					</select>
				</div>

				<div class="item-fields__field">
					<label class="label">Rating <span class="label__desc">(out of <?=$collection['rating_system']?>)</span></label>
					<input class="input input--thin js-autofill" name="score" type="number" min="0" max="<?=$collection['rating_system']?>" <?php if(isset($item['score'])) { echo 'data-autofill="'.score_extrapolate($item['score'], $collection['rating_system']).'"'; } ?>>
				</div>

				<div class="item-fields__field">
					<label class="label">Completed Episodes</label>
					<input class="input input--thin js-autofill" name="progress" type="number" min="0" <?php if(isset($item['progress'])) { echo 'data-autofill="'.$item['progress'].'"'; } ?>>
				</div>

				<div class="item-fields__field">
					<label class="label">
						Rewatched Episodes
						<div class="label__desc">for a 25 episode show, input 25</div>
					</label>
					<input class="input input--thin js-autofill" name="rewatched" type="number" min="0" <?php if(isset($item['rewatched'])) { echo 'data-autofill="'.$item['rewatched'].'"'; } ?>>
				</div>

				<div class="item-fields__field item-fields__field--date">
					<label class="label">User Started At</label>
					<input id="js-set-today-1" class="input input--auto js-autofill" name="user_started_at" type="date" max="" <?php if(isset($item['user_started_at'])) { echo 'data-autofill="'.$item['user_started_at'].'"'; } ?>>
					<a class="subtext" onclick="setToday('js-set-today-1')">Today</a>
				</div>

				<div class="item-fields__field item-fields__field--date">
					<label class="label">User Finished At</label>
					<input id="js-set-today-2" class="input input--auto js-autofill" name="user_finished_at" type="date" <?php if(isset($item['user_finished_at'])) { echo 'data-autofill="'.$item['user_finished_at'].'"'; } ?>>
					<a class="subtext" onclick="setToday('js-set-today-2')">Today</a>
				</div>
				
				<div class="item-fields__divider"></div>

				<div class="item-fields__field">
					<label class="label">Comments</label>
					<textarea class="text-input js-autofill" name="comments" <?php if(isset($item['comments'])) { echo 'data-autofill="'.$item['comments'].'"'; } ?>></textarea>
				</div>

				<div class="item-fields__divider"></div>
			</div>
		</form>

		<div class="button-list">
			<button form="collection-item-edit" class="button-list__button button button--spaced" type="submit">Edit</button>
			<button class="button-list__button button button--spaced" onclick="modalConfirmation('Are you sure you wish to delete this item?', 'collection_item_delete', 'item', <?=$item['id']?>)">Delete</button>
		</div>

		<div class="dialog-box dialog-box--subcontent">
			Not implemented yet - search for other users' items to take data from
		</div>
	</div>



	<div id="modal--confirmation" class="modal modal--hidden" role="dialog" aria-modal="true">
		<button class="modal__background" onclick="toggleModal('modal--confirmation', false)"></button>
		<div class="modal__inner">
			<h3 id="js-confirmation-msg" class="modal__header"></h3>
			<div class="js-confirmation-preview"><!-- TODO - unused atm - plan to put post content here to display what user is deleting --></div>
			<form id="form-confirmation" action="/interface" method="POST" style="display:none">
				<input id="js-confirmation-action" type="hidden" name="action">
				<input id="js-confirmation-data" type="hidden">
			</form>
			<div class="button-list">
				<button form="form-confirmation" class="button-list__button button button--medium button--negative" type="submit">Confirm</a>
				<button class="button-list__button button button--medium" onclick="toggleModal('modal--confirmation', false)">Cancel</a>
			</div>
		</div>
	</div>
</main>