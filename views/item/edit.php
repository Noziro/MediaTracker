<?php
if(isset($_GET['id'])) {
    $item__id = $_GET['id'];

    $item = sqli_result_bindvar('SELECT id, user_id, collection_id, status, name, score, episodes, progress, rewatched, user_started_at, user_finished_at, release_date, started_at, finished_at, comments FROM media WHERE id=? LIMIT 1', 's', $item__id);
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
			
			<label class="label">Name <span class="label__desc">(required)</span></label>
			<input class="input input--wide js-autofill" type="text" name="name" data-autofill="<?=$item['name']?>" required>
			
			<label class="label">Status</label>
			<select class="select" type="text" name="status" required>
				<?php foreach($valid_status as $status) : ?>
				<option <?php if($status === $item['status']) { echo "selected"; }?>><?=$status?></option>
				<?php endforeach; ?>
			</select>

			<label class="label">Rating <span class="label__desc">(out of <?=$collection['rating_system']?>)</span></label>
			<input class="input input--thin js-autofill" name="score" type="number" min="0" max="<?=$collection['rating_system']?>" <?php if(isset($item['score'])) { echo 'data-autofill="'.score_extrapolate($item['score'], $collection['rating_system']).'"'; } ?>>

			<label class="label">Completed Episodes</label>
			<input class="input input--thin js-autofill" name="progress" type="number" min="0" <?php if(isset($item['progress'])) { echo 'data-autofill="'.$item['progress'].'"'; } ?>>

			<label class="label">Total Episodes</label>
			<input class="input input--thin js-autofill" name="episodes" type="number" min="0" <?php if(isset($item['episodes'])) { echo 'data-autofill="'.$item['episodes'].'"'; } ?>>

			<label class="label">Rewatched Episodes <span class="label__desc">(if you rewatched a 25 episode show, input 25)</span></label>
			<input class="input input--thin js-autofill" name="rewatched" type="number" min="0" <?php if(isset($item['rewatched'])) { echo 'data-autofill="'.$item['rewatched'].'"'; } ?>>

			<label class="label">User Started At</label>
			<input class="input input--auto js-autofill" name="user_started_at" type="date" max="" <?php if(isset($item['user_started_at'])) { echo 'data-autofill="'.$item['user_started_at'].'"'; } ?>>

			<label class="label">User Finished At</label>
			<input class="input input--auto js-autofill" name="user_finished_at" type="date" <?php if(isset($item['user_finished_at'])) { echo 'data-autofill="'.$item['user_finished_at'].'"'; } ?>>

			<!-- <label class="label">Media Release Date</label>
			<input class="input input--auto" name="release_date" type="date"> -->

			<label class="label">Media Started At</label>
			<input class="input input--auto js-autofill" name="started_at" type="date" <?php if(isset($item['started_at'])) { echo 'data-autofill="'.$item['started_at'].'"'; } ?>>

			<label class="label">Media Finished At</label>
			<input class="input input--auto js-autofill" name="finished_at" type="date" <?php if(isset($item['finished_at'])) { echo 'data-autofill="'.$item['finished_at'].'"'; } ?>>

			<label class="label">Comments</label>
			<textarea class="text-input js-autofill" name="comments" <?php if(isset($item['comments'])) { echo 'data-autofill="'.$item['comments'].'"'; } ?>></textarea>
		</form>

		<div class="button-list">
			<button form="collection-item-edit" class="button-list__button button button--spaced" type="submit">Edit</button>
			<button class="button-list__button button button--spaced" onclick="modalConfirmation('Are you sure you wish to delete this item?', 'collection_item_delete', 'item', <?=$item['id']?>)">Delete</button>
		</div>

		<div class="dialog-box dialog-box--subcontent">
			Not implemented yet - search for other users' items to add
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