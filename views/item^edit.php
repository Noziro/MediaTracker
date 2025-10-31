<?php declare(strict_types=1);
$item_id = URL['PATH_ARRAY'][1];
if( !preg_eval('/\d+/', $item_id) ){
	bailout('/404');
}
$item_id = intval($item_id);

$item = sql('SELECT id, user_id, collection_id, status, image, name, score, episodes, progress, rewatched, user_started_at, user_finished_at, started_at, finished_at, comments, anilist, myanimelist, imdb, tmdb, adult, favourite, private FROM media WHERE id=? LIMIT 1', ['s', $item_id]);
if( $item->row_count < 1 ){
	bailout('/404');
}
$item = $item->rows[0];

$collection = sql('SELECT id, user_id, name, type, display_image, display_score, display_progress, display_user_started, display_user_finished, display_days, rating_system, private FROM collections WHERE id=?', ['s', $item['collection_id']])->rows[0];

// User authority
if( !$has_session || $user['id'] !== $item['user_id'] ){
	bailout('/403');
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<form id="collection-item-edit" action="/interface/media/edit" method="POST" enctype="multipart/form-data">
			<?php
			if( isset($_GET['return_to']) ){
				echo '<input type="hidden" name="return_to" value="'.$_GET['return_to'].'">';
			}
			?>
			<input type="hidden" name="item_id" value="<?=$item['id']?>">

			<?php
			$module_item = $item;
			$module_collection = $collection;
			require PATH.'modules/item_fields.inc';
			?>
		</form>

		<div class="l-button-list">
			<button form="collection-item-edit" class="l-button-list__button button button--spaced" type="submit">Edit</button>
			<button class="l-button-list__button button button--spaced" onclick="modalConfirmation('Are you sure you wish to delete this item?', '/collection/delete', 'item_id', <?=$item['id']?>)">Delete</button>
		</div>

		<div class="dialog-box dialog-box--subcontent">
			Not implemented yet - search for other users' items to take data from
		</div>
	</div>



	<?php require PATH.'modules/confirmation_modal.inc'; ?>
</main>