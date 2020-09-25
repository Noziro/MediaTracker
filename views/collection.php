<main id="content" class="wrapper wrapper--content">
	<div class="wrapper__inner">
		<?php
		if(isset($_GET['id'])) :
			$collection = sql('SELECT id, user_id, name, type, display_score, display_progress, display_user_started, display_user_finished, display_days, rating_system, private, deleted FROM collections WHERE id=?', ['i', $_GET['id']]);
			if($collection['rows'] < 1) {
				finalize('/404');
			}
			$collection = $collection['result'][0];

			$items = sql('SELECT id, status, name, score, episodes, progress, rewatched, user_started_at, user_finished_at, release_date, started_at, finished_at, comments, favourite FROM media WHERE collection_id=? AND deleted=0 ORDER BY status ASC, name ASC', ['i', $collection['id']]);

			$page_user = sql('SELECT id, nickname FROM users WHERE id=?', ['i', $collection['user_id']])['result'][0];
			
			// $page_user_prefs = sql('SELECT rating_system FROM user_preferences WHERE user_id=?', 's', $page_user['id']);
			// $page_user_prefs = $page_user_prefs->fetch_assoc();

			$columns = [
				'display_score' => 'Score',
				'display_progress' => 'Progress',
				'display_user_started' => 'Started',
				'display_user_finished' => 'Finished',
				'display_days' => 'Days'
			];
		?>



		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?=FILEPATH."user?id=".$page_user['id']?>"><?=$page_user['nickname']?></a> >
				<a href="<?=FILEPATH."collection?user=".$page_user['id']?>">Collection</a> >
				<span><?=$collection['name']?></span>
			</div>
			
			<h2 class="content-header__title"><?=$collection['name']?></h2>
		</div>



		<?php if($has_session && $user['id'] === $page_user['id']) : ?>
		<div class="page-actions">
			<div class="page-actions__button-list">
				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--collection-edit', true)">
					Edit Collection Details
				</button>
				
				<button class="page-actions__action button" type="button" onclick="toggleModal('modal--item-add', true)">
					Add New Item
				</button>
				
				<button class="page-actions__action button button--disabled" type="button" disabled>
					Mass Edit <!-- TODO - will activate a multi-selection mode with checkboxes for each item in which you can edit attributes or delete -->
				</button>

				<button class="page-actions__action button" type="button" onclick="modalConfirmation('Are you sure you wish to delete this collection?', 'collection_delete', 'collection_id', <?=$collection['id']?>)">
					Delete Collection
				</button>
			</div>
		</div>
		<?php endif ?>



		<?php
		if($items['rows'] < 1) :
		?>

		<div class="dialog-box dialog-box--fullsize">No items yet. Add one?</div>

		<?php
		else :
		?>

		<?php if($collection['deleted'] === 1) : ?>

		<div class="dialog-box">This collection and its items are marked for deletion and will be permanently lost within X months. <!-- TODO - specify months once feature is implemented --></div>

		<?php endif; ?>

		<table class="table">
			<thead>
				<tr>
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
				<?php foreach($items['result'] as $item) : ?>

				<tr id="item-<?=$item['id']?>" class="table__body-row">
					<td class="table__cell">
						<?php if($collection['user_id'] === $user['id']) : ?>
						<a class="js-item-edit" href="item/edit?id=<?=$item['id']?>&frame=1" onclick="editItem(<?=$item['id']?>)">
							<?=$item['name']?>
						</a>
						<?php else : ?>
						<span><?=$item['name']?></span>
						<?php endif; ?>
					</td>

					<?php if($collection['display_score'] === 1) : ?>

					<td class="table__cell">
						<?php
						if($item['score'] !== 0) {
							echo score_extrapolate($item['score'], $collection['rating_system']);
						} else {
							echo "-";
						}

						if($item['favourite'] === 1) {
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

						if($w === $t) {
							echo $t;
						} else {
							echo $w.' / '.$t;
						}

						if($r > 0) {
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
						$s = $item['user_started_at'];
						$e = $item['user_finished_at'];
						$sd = date_create($s);
						$ed = date_create($e);
						$n = date_create();

						if(isset($s) && isset($e)) {
							$days = date_diff($sd,$ed)->format('%a');
							if($days < 1) {
								$days = 1;
							}
							echo $days;
						} elseif(isset($s)) {
							$days = date_diff($sd,$n)->format('%a');
							if($sd > $n) {
								echo 'N/A';
							} else {
								if($days < 1) {
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
					<input class="input js-autofill" type="text" name="name" data-autofill="<?=$collection['name']?>" required>
					
					<label class="label">Type</label>
					<select class="select" name="type">
						<?php foreach($valid_coll_types as $type) : ?>
						<option <?php if($type === $collection['type']) { echo "selected"; } ?>><?=$type?></option>
						<?php endforeach; ?>
					</select>

					<label class="label">Privacy</label>
					<select class="select" name="private">
						<option value="0" <?php if($collection['private'] === 0) { echo "selected"; } ?>>Public</option>
						<option value="9" <?php if($collection['private'] === 9) { echo "selected"; } ?>>Only Me</option>
					</select>

					<label class="label">Display Columns</label>
					<div class="checkbox-group">
						<?php
						foreach($columns as $col => $label) :
						?>
						<label class="checkbox-group__item">
							<input type="hidden" name="<?=$col?>" value="0">
							<input class="checkbox" type="checkbox" name="<?=$col?>" value="1" <?php if($collection[$col] === 1) { echo "checked"; } ?>>
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

						foreach($rating_systems as $value => $label) {
							echo '<option value="'.$value.'"';
							
							if($value === $collection['rating_system']) {
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
				<form action="/interface/generic" method="POST">
					<input type="hidden" name="action" value="collection_item_create">
					<input type="hidden" name="return_to" value="<?=$_SERVER['REQUEST_URI']?>">
					<input type="hidden" name="collection_id" value="<?=$collection['id']?>">

					<div class="item-fields">
						<div class="item-fields__divider">
							<span class="item-fields__header">Item Information</span>
						</div>

						<div class="item-fields__field">
							<label class="label">Name <span class="label__desc">(required)</span></label>
							<input class="input input--wide" type="text" name="name" required>
						</div>

						<div class="item-fields__field">
							<label class="label">Episodes</label>
							<input class="input input--thin" name="episodes" type="number" min="0">
						</div>

						<!--<div class="item-fields__field item-fields__field--date">
							<label class="label">Media Release Date</label>
							<input class="input input--auto" name="release_date" type="date">
						</div>-->

						<div class="item-fields__field item-fields__field--date">
							<label class="label">Media Started At</label>
							<input id="js-set-today-3" class="input input--auto" name="started_at" type="date">
							<a class="subtext" onclick="setToday('js-set-today-3')">Today</a>
						</div>

						<div class="item-fields__field item-fields__field--date">
							<label class="label">Media Finished At</label>
							<input id="js-set-today-4" class="input input--auto" name="finished_at" type="date">
							<a class="subtext" onclick="setToday('js-set-today-4')">Today</a>
						</div>

						<!--<div class="item-fields__field">
							<label class="label">Credits</label>
							<input class="input" type="text" name="credits" required>
						</div>-->

						<!--<div class="item-fields__field">
							<label class="label">Image</label>
							<input type="file" name="image">
						</div>--->

						<div class="item-fields__field">
							<label class="label">Flags</label>
							<div class="checkbox-group">
								<label class="checkbox-group__item">
									<input type="hidden" name="adult" value="0"> <!-- fallback value -->
									<input class="checkbox" type="checkbox" name="adult" value="1">
									Adult
								</label>

								<label class="checkbox-group__item">
									<input type="hidden" name="favourite" value="0"> <!-- fallback value -->
									<input class="checkbox" type="checkbox" name="favourite" value="1">
									Favourite
								</label>
							</div>
						</div>

						<div class="item-fields__field">
							<label class="label">Links</label>
							<input class="input" type="text" name="links[]">
							<input class="input" type="text" name="links[]">
							<input class="input" type="text" name="links[]">
						</div>


						<div class="item-fields__divider">
							<span class="item-fields__header">User Data</span>
						</div>

						<div class="item-fields__field">
							<label class="label">Status</label>
							<select class="select" type="text" name="status" required>
								<?php foreach($valid_status as $status) : ?>
								<option <?php if($status === 'completed') { echo "selected"; }?>><?=$status?></option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="item-fields__field">
							<label class="label">Rating <span class="label__desc">(out of <?=$collection['rating_system']?>)</span></label>
							<input class="input input--thin" name="score" type="number" min="0" max="<?=$collection['rating_system']?>">
						</div>

						<div class="item-fields__field">
							<label class="label">Completed Episodes</label>
							<input class="input input--thin" name="progress" type="number" min="0">
						</div>

						<div class="item-fields__field">
							<label class="label">
								Rewatched Episodes
								<div class="label__desc">for a 25 episode show, input 25</div>
							</label>
							<input class="input input--thin" name="rewatched" type="number" min="0">
						</div>

						<div class="item-fields__field item-fields__field--date">
							<label class="label">User Started At</label>
							<input id="js-set-today-1" class="input input--auto" name="user_started_at" type="date">
							<a class="subtext" onclick="setToday('js-set-today-1')">Today</a>
						</div>

						<div class="item-fields__field item-fields__field--date">
							<label class="label">User Finished At</label>
							<input id="js-set-today-2" class="input input--auto" name="user_finished_at" type="date">
							<a class="subtext" onclick="setToday('js-set-today-2')">Today</a>
						</div>

						<div class="item-fields__divider"></div>

						<div class="item-fields__field">
							<label class="label">Comments</label>
							<textarea class="text-input" name="comments"></textarea>
						</div>

						<div class="item-fields__divider"></div>

						<div class="item-fields__field">
							<input class="button button--spaced" type="submit" value="Add">
						</div>
					</div>
				</form>

				<div>
					Not implemented yet - search for other users' items to add
				</div>
			</div>
		</div>



		<div id="modal--item-edit" class="modal modal--hidden" role="dialog" aria-modal="true">
			<button class="modal__background" onclick="toggleModal('modal--item-edit', false)"></button>
			<div class="modal__inner modal__inner--wide">
				<a class="modal__close" onclick="toggleModal('modal--item-edit', false)">Close</a>
				<h3 class="modal__header">
					Edit Item
				</h3>
			</div>
		</div>
		<?php endif; ?>





		<?php
		// If user is not specified, redirect to own page.
		elseif(!isset($_GET['user']) && $has_session || isset($_GET['user'])) :
			if(!isset($_GET['user'])) {
				$page_user__id = $user['id'];
			} else {
				$page_user__id = $_GET['user'];
			}

			$page_user = sql('SELECT id, nickname FROM users WHERE id=?', ['i', $page_user__id])['result'][0];

			if($user['id'] === $page_user['id']) {
				// TODO - once friend system implemented, move this to a function
				// such as evaluate_friendship($user1, $user2) and have more nuance to levels
				$friendship = 9;
			} else {
				$friendship = 0;
			}

			$collections = sql('SELECT id, user_id, name, type, private FROM collections WHERE user_id=? AND deleted=0 AND private<=? ORDER BY name ASC', ['ii', $page_user['id'], $friendship]);
			$deleted_collections = sql('SELECT id, user_id, name, type, private FROM collections WHERE user_id=? AND deleted=1 AND private<=? ORDER BY name ASC', ['ii', $page_user['id'], $friendship]);
		?>



		<div class="content-header">
			<div class="content-header__breadcrumb">
				<a href="<?=FILEPATH."user?id=".$page_user['id']?>"><?=$page_user['nickname']?></a> >
				<span>Collection</span>
			</div>
			
			<h2 class="content-header__title"><?=$page_user['nickname']?>'s Collection</h2>
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
		if($collections['rows'] < 1) :
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
				<?php foreach($collections['result'] as $collection) : ?>

				<tr class="table__body-row">
					<td class="table__cell">
						<a class="u-bold" href="?id=<?=$collection['id']?>">
							<?=$collection['name']?>
						</a>
					</td>
					<td class="table__cell">
						<?php
						echo reset(sql('SELECT COUNT(id) FROM media WHERE collection_id=?', ['i', $collection['id']])['result'][0]);
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
		if($deleted_collections['rows'] > 0 && $user['id'] === $page_user['id']) :
		?>

		<h2 class="c-heading">Deleted Collections</h2>

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
				<?php foreach($deleted_collections['result'] as $collection) : ?>

				<tr class="table__body-row">
					<td class="table__cell">
						<a class="u-bold" href="?id=<?=$collection['id']?>">
							<?=$collection['name']?>
						</a>
					</td>
					<td class="table__cell">
						<?php
						echo reset(sql('SELECT COUNT(id) FROM media WHERE collection_id=?', ['i', $collection['id']])['result'][0]);
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
					<input id="collection-name" class="input" type="text" name="name" required>
					
					<label class="label" for="collection-type">Type</label>
					<select id="collection-type" class="select" name="type">
						<?php foreach($valid_coll_types as $type) : ?>
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




		<?php if($has_session && $user['id'] === $page_user['id']) {
			include PATH.'server/includes/modal-confirmation.inc';
		} ?>
	</div>
</main>