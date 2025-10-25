<?php
# Determine which page to load based on URL
if( URL['PATH_ARRAY'][0] === 'collection' ){
	$page = 'specific_collection';
	$is_orphanage = nth_last(URL['PATH_ARRAY']) === 'orphans';
	if( !$is_orphanage ){
		if( count(URL['PATH_ARRAY']) < 2 ){
			finalize('/404');
		}
		$collection_id = URL['PATH_ARRAY'][1];
		if( !preg_eval('/\d+/', $collection_id) ){
			finalize('/404');
		}
		$collection_id = intval($collection_id);
	}
}
elseif( URL['PATH_STRING'] === '/my/collection' ||
	URL['PATH_ARRAY'][0] === 'user' && URL['PATH_ARRAY'][2] === 'collection' ){
	$page = 'entire_collection';
	if( URL['PATH_ARRAY'][0] === 'my' ){
		$page_user_id = $user['id'];
	}
	else {
		$page_user_id = URL['PATH_ARRAY'][1];
		if( !preg_eval('/\d+/', $page_user_id) ){
			finalize('/404');
		}
		$page_user_id = intval($page_user_id);
	}
}
else {
	finalize('/404');
}

if( (isset($is_orphanage) && $is_orphanage) || $page === 'entire_collection' ){
	$orphaned_items = sql('
		SELECT COUNT(m.id)
		FROM media AS m
		JOIN collections AS c ON m.collection_id = c.id
		WHERE m.user_id=? AND c.deleted=1',
		['i', $user['id']], false);
}
?>

<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<?php
		if( $page === 'specific_collection' ) :

		if( $is_orphanage ){
			$collection = [
				'name' => 'Orphaned Items',
				'user_id' => $user['id'],
				'deleted' => 0,
				'display_image' => 1,
				'display_score' => 1,
				'display_progress' => 1,
				'display_user_started' => 1,
				'display_user_finished' => 1,
				'display_days' => 1,
				'rating_system' => 100
			];
			$items = sql('
				SELECT m.id, m.status, m.name, m.image, m.score, m.episodes, m.progress, m.rewatched, m.user_started_at, m.user_finished_at, m.release_date, m.started_at, m.finished_at, m.comments, m.favourite
				FROM media AS m
				JOIN collections AS c ON m.collection_id = c.id
				WHERE m.user_id=? AND c.deleted=1
				ORDER BY status ASC, name ASC',
				['i', $user['id']]);

			$page_user = $user;
		}
		else {
			$stmt = sql('
				SELECT id, user_id, name, type, display_image, display_score, display_progress, display_user_started, display_user_finished, display_days, rating_system, private, deleted
				FROM collections
				WHERE id=?',
				['i', $collection_id]);
			if( $stmt->row_count < 1 ){
				finalize('/404');
			}
			$collection = $stmt->rows[0];
			if( $collection['deleted'] === 1 ){
				finalize('/403');
			}

			$items = sql('
				SELECT id, status, name, image, score, episodes, progress, rewatched, user_started_at, user_finished_at, release_date, started_at, finished_at, comments, favourite
				FROM media
				WHERE collection_id=? AND deleted=0
				ORDER BY status ASC, name ASC',
				['i', $collection['id']]);
		
			$page_user = sql('SELECT id, nickname FROM users WHERE id=?', ['i', $collection['user_id']])->rows[0];
		}



		$columns = [
			'display_image' => 'Image',
			'display_score' => 'Score',
			'display_progress' => 'Progress',
			'display_user_started' => 'Started',
			'display_user_finished' => 'Finished',
			'display_days' => 'Days'
		];
		?>

		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?="/user/".$page_user['id']?>"><?=$page_user['nickname']?></a> >
				<a href="/user/<?=$page_user['id']?>/collection">Collection</a> >
				<span><?=$collection['name']?></span>
			</div>
			
			<h2 class="content-header__title"><?=$collection['name']?></h2>
		</div>



		<?php if($has_session && $user['id'] === $page_user['id']) : ?>
		<div class="page-actions">
			<div class="page-actions__button-list">
				<?php if( isset($collection_id) ) : ?>
				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--collection-edit', true)">
					Edit Collection Details
				</button>
				
				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--item-add', true)">
					Add New Item
				</button>
				<?php endif; ?>
				
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Mass Edit <!-- TODO - will activate a multi-selection mode with checkboxes for each item in which you can edit attributes or delete -->
				</button>

				<?php if( isset($collection_id) ) : ?>
				<button class="page-actions__action button" type="button" onclick="modalConfirmation('Are you sure you wish to delete this collection?', 'collection_delete', 'collection_id', <?=$collection['id']?>)">
					Delete Collection
				</button>
				<?php endif; ?>
			</div>
		</div>
		<?php endif ?>



		<?php
		if($items->row_count < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No items yet. Add one?</div>

		<?php
		else :
		?>

		<?php if( $is_orphanage ) : ?>

		<div class="dialog-box">These items belong to deleted collections and have no home! You may want to re-house them.</div>

		<?php endif; ?>

		<table class="table">
			<thead>
				<tr>
					<?php if($collection['display_image'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Image</b></span></th>
					<?php endif; ?>
					<th class="table__cell table__cell--one-half"><b class="table__heading">Name</b></th>
					<?php if($collection['display_score'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Score</b><br /><span class="table__subheading">of <?=$collection['rating_system']?></span></th>
					<?php endif; if($collection['display_progress'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Progress</b></th>
					<?php endif; if($collection['display_user_started'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Started</b></th>
					<?php endif; if($collection['display_user_finished'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Finished</b></th>
					<?php endif; if($collection['display_days'] === 1) : ?>
					<th class="table__cell"><b class="table__heading">Days</b></th>
					<?php endif; ?>
					<th class="table__cell"><b class="table__heading">Status</b></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($items->rows as $item) : ?>

				<tr id="item-<?=$item['id']?>" class="table__body-row">

					<?php if($collection['display_image'] === 1) : ?>

					<td class="table__cell">
						<?php if(!empty($item['image'])) : ?>
						<img src="<?=$item['image']?>" style="width: 40px; height: 60px; object-fit: cover;" />
						<?php endif; ?>
					</td>

					<?php endif; ?>

					<td class="table__cell">
						<a href="/item/<?=$item['id']?>"><?=$item['name']?></a>

						<?php if($collection['user_id'] === $user['id']) : ?>
						<a href="/item/<?=$item['id']?>/edit?return_to=/collection/<?=$is_orphanage ? 'orphans' : $collection['id']?>#item-<?=$item['id']?>" style="float:right;">
							Edit
						</a>
						<?php endif; ?>
					</td>

					<?php if($collection['display_score'] === 1) : ?>

					<td class="table__cell">
						<?php
						if( $item['score'] !== 0 ){
							echo score_extrapolate($item['score'], $collection['rating_system']);
						} else {
							echo "-";
						}

						if( $item['favourite'] === 1 ){
							echo " ♥";
						}
						?>
					</td>
					
					<?php endif; if($collection['display_progress'] === 1) : ?>

					<td class="table__cell">
						<?php
						$w = $item['progress'];
						$t = $item['episodes'];
						$r = $item['rewatched'];

						if( $w === $t ){
							echo $t;
						} else {
							echo $w.' / '.$t;
						}

						if( $r > 0 ){
							echo '<br />Rewatched: '.$r / $t.'x ('.$r.'eps)';
						}
						?>
					</td>

					<?php endif; if($collection['display_user_started'] === 1) : ?>
					
					<td class="table__cell">
						<?=$item['user_started_at']?>
					</td>

					<?php endif; if($collection['display_user_finished'] === 1) : ?>
					
					<td class="table__cell">
						<?=$item['user_finished_at']?>
					</td>

					<?php endif; if($collection['display_days'] === 1) : ?>
					
					<td class="table__cell">
						<?php
						$started = $item['user_started_at'];
						$ended = $item['user_finished_at'];

						if( isset($started) && isset($ended) ){
							$days = date_diff(date_create($started),date_create($ended))->format('%a');
							if( $days < 1 ){
								$days = 1;
							}
							echo $days;
						} elseif( isset($started) ){
							$days = date_diff(date_create($started),date_create())->format('%a');
							if( date_create($started) > date_create() ){
								echo 'N/A';
							} else {
								if( $days < 1 ){
									$days = 1;
								}
								echo '>'.$days;
							}
						}
						?>
					</td>

					<?php endif; ?>

					<td class="table__cell">
						<?=$item['status']?>
					</td>
				</tr>

				<!--<?php if(isset($item['comments'])) : ?>
				
				<tr class="table__body-row">
					<td class="table__cell" colspan="7">
						<?=format_user_text($item['comments'])?>
					</td>
				</tr>

				<?php endif; ?>-->

				<?php endforeach; ?>
			</tbody>
		</table>

		<?php endif; ?>



		<?php if($has_session && $user['id'] === $page_user['id']) : ?>
		<div id="modal--collection-edit" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--collection-edit', false)"></button>
			<div class="modal__inner">
				<a class="modal__close" onclick="toggleModal('modal--collection-edit', false)">Close</a>
				<h3 class="modal__header">
					Edit Collection
				</h3>
				<form action="/interface/generic" method="POST">
					<input type="hidden" name="action" value="collection_edit">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="collection_id" value="<?=$collection['id']?>">
					
					<label class="label">Name</label>
					<input class="input js-autofill js-modal-focus" type="text" name="name" data-autofill="<?=$collection['name']?>" required>
					
					<label class="label">Type</label>
					<select class="select" name="type">
						<?php foreach(VALID_MEDIA_TYPES as $type) : ?>
						<option <?php if( $type === $collection['type'] ){ echo "selected"; } ?>><?=$type?></option>
						<?php endforeach; ?>
					</select>

					<label class="label">Privacy</label>
					<select class="select" name="private">
						<option value="0" <?php if( $collection['private'] === 0 ){ echo "selected"; } ?>>Public</option>
						<option value="9" <?php if( $collection['private'] === 9 ){ echo "selected"; } ?>>Only Me</option>
					</select>

					<label class="label">Display Columns</label>
					<div class="checkbox-group">
						<?php
						foreach($columns as $col => $label) :
						?>
						<label class="checkbox-group__item">
							<input type="hidden" name="<?=$col?>" value="0">
							<input class="checkbox" type="checkbox" name="<?=$col?>" value="1" <?php if( $collection[$col] === 1 ){ echo "checked"; } ?>>
							<?=$label?>
						</label>
						<?php endforeach; ?>
					</div>

					<label class="label">Rating System</label>
					<select class="select" name="rating_system">
						<?php
						$rating_systems = [
							3 => '3 Star',
							5 => '5 Star',
							10 => '10 Point',
							20 => '20 Point',
							100 => '100 Point'
						];

						foreach( $rating_systems as $value => $label ){
							echo '<option value="'.$value.'"';
							
							if( $value === $collection['rating_system'] ){
								echo 'selected';
							}

							echo '>'.$label.'</option>';
						}
						?>
					</select>

					<input class="button button--spaced" type="submit" value="Edit">
				</form>
			</div>
		</div>


		
		<div id="modal--item-add" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--item-add', false)"></button>
			<div class="modal__inner modal__inner--wide">
				<a class="modal__close" onclick="toggleModal('modal--item-add', false)">Close</a>
				<h3 class="modal__header">
					Add Item
				</h3>
				<form id="collection-item-add" action="/interface/generic" method="POST" enctype="multipart/form-data">
					<input type="hidden" name="action" value="collection_item_create">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="collection_id" value="<?=$collection['id']?>">

					<?php
					$module_item = [];
					$module_collection = $collection;
					include(PATH.'modules/item_fields.php');
					?>
				</form>

				<div class="l-button-list">
					<button form="collection-item-add" class="l-button-list__button button button--spaced" type="submit">Add</button>
				</div>

				<div class="dialog-box dialog-box--subcontent">
					Not implemented yet - search for other users' items to take data from
				</div>
			</div>
		</div>
		<?php endif; ?>





		<?php
		// If user is not specified, redirect to own page.
		elseif(!isset($_GET['u']) && $has_session || isset($_GET['u'])) :
			$stmt = sql('SELECT id, nickname FROM users WHERE id=?', ['i', $page_user_id]);
			if( !$stmt->ok || $stmt->row_count < 1 ){ finalize('/404'); }
			$page_user = $stmt->rows[0];

			if( $user['id'] === $page_user['id'] ){
				// TODO - once friend system implemented, move this to a function
				// such as evaluate_friendship($user1, $user2) and have more nuance to levels
				$friendship = 9;
			} else {
				$friendship = 0;
			}

			$collections = sql('SELECT id, user_id, name, type, private FROM collections WHERE user_id=? AND deleted=0 AND private<=? ORDER BY name ASC', ['ii', $page_user['id'], $friendship]);
		?>



		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?="/user/".$page_user['id']?>"><?=$page_user['nickname']?></a> >
				<span>Collection</span>
			</div>
			
			<h2 class="content-header__title"><?=$has_session && $page_user['id'] === $user['id'] ? 'Your' : $page_user['nickname']."'s"?> Collection</h2>
		</div>



		<?php if($has_session) : ?>
		<div class="page-actions">
			<div class="page-actions__button-list">
				<?php if($user['id'] === $page_user['id']) : ?>

				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--collection-create', true)">
					New Collection
				</button>
				
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Mass Edit <!-- TODO - will activate a multi-selection mode with checkboxes for each item in which you can edit attributes or delete -->
				</button>

				<?php else : ?>

				<button class="page-actions__action button button--disabled" type="button" disabled>
					Compare Collections
				</button>

				<?php endif ?>
			</div>
		</div>
		<?php endif ?>



		<?php
		if($collections->row_count < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No collections yet. Create one?</div>

		<?php
		else :
		?>

		<table class="table">
			<thead>
				<tr>
					<th class="table__cell"><b class="table__heading">Name</b></th>
					<th class="table__cell table__cell--extra-small"><b class="table__heading">Items</b></th>
					<th class="table__cell table__cell--small"><b class="table__heading">Type</b></th>
					<th class="table__cell table__cell--small"><b class="table__heading">Privacy</b></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach($collections->rows as $collection) : ?>

				<tr class="table__body-row">
					<td class="table__cell">
						<a class="u-bold" href="/collection/<?=$collection['id']?>">
							<?=$collection['name']?>
						</a>
					</td>
					<td class="table__cell">
						<?php
						echo reset(sql('SELECT COUNT(id) FROM media WHERE collection_id=?', ['i', $collection['id']])->rows[0]);
						?>
					</td>
					<td class="table__cell">
						<?=$collection['type']?>
					</td>
					<td class="table__cell">
						<?php if($collection['private'] === 9) : ?>
						Private
						<?php else : ?>
						Public
						<?php endif; ?>
					</td>
				</tr>

				<?php endforeach; ?>
			</tbody>
		</table>

		<?php endif; ?>



		<?php
		$orphaned_items_count = $orphaned_items->row_count > 0 ? $orphaned_items->rows[0][0] : 0;
		if($orphaned_items_count > 0 && $user['id'] === $page_user['id']) :
		?>

		<h2 class="c-heading">Automatic Collections</h2>

		<table class="table">
			<thead>
				<tr>
					<th class="table__cell"><b class="table__heading">Name</b></th>
					<th class="table__cell table__cell--extra-small"><b class="table__heading">Items</b></th>
					<th class="table__cell table__cell--small"><b class="table__heading">Type</b></th>
					<th class="table__cell table__cell--small"><b class="table__heading">Privacy</b></th>
				</tr>
			</thead>
			<tbody>
				<tr class="table__body-row">
					<td class="table__cell">
						<a class="u-bold" href="/collection/orphans">
							Orphaned Items
						</a>
					</td>
					<td class="table__cell">
						<?=$orphaned_items_count?>
					</td>
					<td class="table__cell">
						Mixed
					</td>
					<td class="table__cell">
						Private
					</td>
				</tr>
			</tbody>
		</table>

		<?php endif; ?>



		<?php if($has_session && $user['id'] === $page_user['id']) : ?>

		<div id="modal--collection-create" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--collection-create', false)"></button>
			<div class="modal__inner">
				<a class="modal__close" onclick="toggleModal('modal--collection-create', false)">Close</a>
				<h3 class="modal__header">
					Create Collection
				</h3>

				<form action="/interface/generic" method="POST">
					<input type="hidden" name="action" value="collection_create">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					
					<label class="label" for="collection-name">Name</label>
					<input id="collection-name" class="input js-modal-focus" type="text" name="name" required>
					
					<label class="label" for="collection-type">Type</label>
					<select id="collection-type" class="select" name="type">
						<?php foreach(VALID_MEDIA_TYPES as $type) : ?>
						<option><?=$type?></option>
						<?php endforeach; ?>
					</select>

					<label class="label" for="collection-privacy">Privacy</label>
					<select id="collection-privacy" class="select" name="private">
						<option value="0">Public</option>
						<option value="9">Only Me</option>
					</select>

					<input class="button button--spaced" type="submit" value="Create">
				</form>
			</div>
		</div>

		<?php endif; ?>





		<?php
		else :
			header('Location: /404');
			exit();
		
		endif;
		?>




		<?php if( $has_session && $user['id'] === $page_user['id'] ){
			include PATH.'server/includes/modal-confirmation.inc';
		} ?>
	</div>
</main>