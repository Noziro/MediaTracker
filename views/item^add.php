<?php declare(strict_types=1);
if( !$has_session ){
	soft_error(403);
	return;
}

$inherit_from_id = isset($_GET['from']) ? $_GET['from'] : false;
if( $inherit_from_id ){
	if( !preg_eval('/\d+/', $inherit_from_id) ){
		bailout('/404');
	}
	$inherit_from_id = intval($inherit_from_id);
	$stmt = sql('SELECT id, user_id, collection_id, status, image, name, score, episodes, progress, rewatched, user_started_at, user_finished_at, started_at, finished_at, comments, anilist, myanimelist, imdb, tmdb, adult, favourite, private FROM media WHERE id=? LIMIT 1', ['s', $inherit_from_id]);
	if( $stmt->row_count < 1 ){
		bailout('/404');
	}
	$item = $stmt->rows[0];
	if( $item['private'] > 0 && $user['id'] !== $item['id'] ){
		soft_error(403);
		return;
	}
}
else {
	$item = [];
}

$collections = sql('SELECT id, user_id, name, type, display_image, display_score, display_progress, display_user_started, display_user_finished, display_days, rating_system, private FROM collections WHERE user_id=? AND deleted=0', ['s', $user['id']]);
if( $collections->row_count < 1 ){
	bailout($_GET['return_to'] ?? '/', ['must_complete_prior', 'generic', 'You need to have at least one collection to add an item to it.']);
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<form id="collection-item-add" action="/interface/media/create" method="POST" enctype="multipart/form-data">
			<input type="hidden" name="rating_system" value="100">
			<?php
			if( isset($_GET['return_to']) ){
				echo '<input type="hidden" name="return_to" value="'.$_GET['return_to'].'">';
			}
			?>

			<div class="item-fields">
				<div class="item-fields__field">
					<label class="label">Collection to Add To</label>
					<select class="select" name="collection_id" required>
						<?php foreach( $collections->rows as $collection ){
							echo '<option value="'.$collection['id'].'">'.$collection['name'].'</option>';
						} ?>
					</select>
				</div>
			</div>
			

			<?php
			$module_item = $item;
			$module_collection = ['rating_system' => 100];
			require PATH.'modules/item_fields.inc';
			?>
		</form>

		<div class="l-button-list">
			<button form="collection-item-add" class="l-button-list__button button button--spaced" type="submit">Add</button>
		</div>

		<div class="dialog-box dialog-box--subcontent">
			Not implemented yet - search for other users' items to take data from
		</div>
	</div>



	<?php require PATH.'modules/confirmation_modal.inc'; ?>
</main>